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
            if($authority->amount_limit!==null && $amount>(float)$authority->amount_limit) return false;
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
