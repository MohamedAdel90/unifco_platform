<?php

namespace App\Services;

use App\Models\{AccessScope,ApprovalAuthority,ApprovalRequest,ServiceRequest,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalAuthorityService
{
    public function assertAllows(User $user,ApprovalRequest $approval,?ServiceRequest $serviceRequest=null): void
    {
        $type=strtoupper((string)$approval->action);

        $authorities=ApprovalAuthority::query()
            ->where('tenant_id',$user->tenant_id)
            ->where('approval_type',$type)
            ->where('is_active',true)
            ->orderBy('level')
            ->get();

        // Compatibility: authority enforcement starts as soon as the tenant
        // configures at least one rule for this approval type.
        if($authorities->isEmpty()) return;

        $roleIds=DB::table('user_roles')
            ->where('tenant_id',$user->tenant_id)
            ->where('user_id',$user->id)
            ->whereNull('revoked_at')
            ->pluck('role_id');

        $amount=$this->amount($approval,$serviceRequest);

        $allowed=$authorities->contains(function(ApprovalAuthority $authority) use($roleIds,$amount,$serviceRequest){
            if(!$roleIds->contains((int)$authority->role_id)) return false;
            $from=$authority->amount_from!==null?(float)$authority->amount_from:0.0;
            $to=$authority->amount_to!==null?(float)$authority->amount_to:($authority->amount_limit!==null?(float)$authority->amount_limit:null);
            if($amount<$from) return false;
            if($to!==null && $amount>$to) return false;
            if(!$authority->access_scope_id) return true;

            $scope=AccessScope::query()->where('is_active',true)->find($authority->access_scope_id);
            if(!$scope) return false;

            return $this->scopeMatches($scope,$serviceRequest);
        });

        if(!$allowed){
            throw ValidationException::withMessages([
                'approval'=>'Your approval authority does not cover this transaction amount or scope.',
            ]);
        }
    }

    public function assertTransaction(User $user,string $approvalType,float $amount,array $scopeContext=[]): void
    {
        $type=strtoupper($approvalType);
        $authorities=ApprovalAuthority::query()
            ->where('tenant_id',$user->tenant_id)
            ->where('approval_type',$type)
            ->where('is_active',true)
            ->orderBy('level')
            ->get();

        if($authorities->isEmpty()) return;

        $roleIds=DB::table('user_roles')
            ->where('tenant_id',$user->tenant_id)
            ->where('user_id',$user->id)
            ->whereNull('revoked_at')
            ->pluck('role_id');

        $allowed=$authorities->contains(function(ApprovalAuthority $authority) use($roleIds,$amount,$scopeContext){
            if(!$roleIds->contains((int)$authority->role_id)) return false;

            $from=$authority->amount_from!==null?(float)$authority->amount_from:0.0;
            $to=$authority->amount_to!==null?(float)$authority->amount_to:($authority->amount_limit!==null?(float)$authority->amount_limit:null);
            if($amount<$from) return false;
            if($to!==null && $amount>$to) return false;

            if(!$authority->access_scope_id) return true;
            $scope=AccessScope::query()->where('is_active',true)->find($authority->access_scope_id);
            if(!$scope) return false;

            return match($scope->scope_type){
                'GLOBAL'=>true,
                'PROJECT'=>(int)($scopeContext['project_id']??0)===(int)$scope->scope_id,
                'SITE'=>(int)($scopeContext['site_id']??0)===(int)$scope->scope_id,
                'CUSTOMER'=>(int)($scopeContext['customer_id']??0)===(int)$scope->scope_id,
                'CONTRACT'=>(int)($scopeContext['contract_id']??0)===(int)$scope->scope_id,
                'ASSET'=>(int)($scopeContext['asset_id']??0)===(int)$scope->scope_id,
                default=>false,
            };
        });

        if(!$allowed){
            throw ValidationException::withMessages([
                'approval'=>'Your approval authority does not cover this transaction amount or scope.',
            ]);
        }
    }

    public function amount(ApprovalRequest $approval,?ServiceRequest $serviceRequest=null): float
    {
        if($serviceRequest){
            $context=(array)($serviceRequest->workflow_context??[]);
            $estimated=(float)($context['estimated_value']??0);
            if($estimated>0) return $estimated;

            if(!empty($context['invoice_id'])){
                return (float)(DB::table('financial_documents')->where('id',$context['invoice_id'])->value('amount')??0);
            }

            if($serviceRequest->work_order_id){
                return (float)(DB::table('work_orders')->where('id',$serviceRequest->work_order_id)->value('total_cost')??0);
            }
        }

        $metadata=(array)($approval->metadata??[]);
        return (float)($metadata['amount']??$metadata['estimated_value']??0);
    }

    private function scopeMatches(AccessScope $scope,?ServiceRequest $request): bool
    {
        if($scope->scope_type==='GLOBAL') return true;
        if(!$request) return false;

        return match($scope->scope_type){
            'PROJECT'=>(int)$request->project_id===(int)$scope->scope_id,
            'SITE'=>(int)$request->customer_site_id===(int)$scope->scope_id,
            'CUSTOMER'=>(int)$request->customer_id===(int)$scope->scope_id,
            'CONTRACT'=>(int)$request->service_contract_id===(int)$scope->scope_id,
            'ASSET'=>(int)$request->asset_id===(int)$scope->scope_id,
            default=>false,
        };
    }
}
