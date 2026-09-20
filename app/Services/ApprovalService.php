<?php

namespace App\Services;

use App\Models\{ApprovalRequest,Customer,ProjectUserAssignment,ServiceRequest};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(
        private AuditService $audit,
        private CustomerLifecycleService $customers,
        private ServiceRequestWorkflowService $workflow,
        private AuthorizationService $authorization,
        private ScopeService $scopes,
    ) {}

    public function request(Model $entity,string $action): ApprovalRequest
    {
        return ApprovalRequest::firstOrCreate([
            'tenant_id'=>Auth::user()->tenant_id,'entity_type'=>$entity::class,'entity_id'=>$entity->getKey(),'action'=>$action,'status'=>'PENDING',
        ],['organization_id'=>Auth::user()->organization_id,'requested_by'=>Auth::id()]);
    }

    public function decide(ApprovalRequest $request,string $decision,?string $note=null): ApprovalRequest
    {
        $user=Auth::user();
        if(!$user) throw ValidationException::withMessages(['approval'=>'Authentication is required.']);
        if($request->status!=='PENDING') throw ValidationException::withMessages(['approval'=>'Approval request is not currently actionable.']);

        $roles=$this->authorization->roleCodes($user)->push(strtoupper((string)$user->role))->filter()->unique();
        if($request->approval_role && !$roles->contains(strtoupper((string)$request->approval_role))){
            throw ValidationException::withMessages(['approval'=>'This approval belongs to '.$request->approval_role.'.']);
        }
        if($request->assigned_user_id && (int)$request->assigned_user_id!==(int)$user->id){
            throw ValidationException::withMessages(['approval'=>'This workflow stage is assigned to another user.']);
        }
        if($request->routing_status==='NEEDS_ASSIGNMENT'){
            throw ValidationException::withMessages(['approval'=>'This workflow stage has no resolved owner yet. Assign the responsible user before continuing.']);
        }

        if($request->entity_type===ServiceRequest::class){
            $serviceRequest=ServiceRequest::where('tenant_id',$user->tenant_id)->find($request->entity_id);
            if(!$serviceRequest) throw ValidationException::withMessages(['approval'=>'The service request is not available in your tenant.']);

            $visible=$this->scopes->apply(ServiceRequest::query()->whereKey($serviceRequest->id),$user)->exists();
            if(!$visible) throw ValidationException::withMessages(['approval'=>'This approval is outside your access scope.']);

            $this->assertProjectAssignment($user,$serviceRequest,(string)$request->approval_role);

            if(strtoupper((string)$request->approval_role)==='TECHNICIAN' && $serviceRequest->assigned_engineer_id){
                if((int)$serviceRequest->assigned_engineer_id!==(int)$user->id){
                    throw ValidationException::withMessages(['approval'=>'Only the technician assigned to this request can perform this stage.']);
                }
            }

            if($request->action==='TECHNICIAN_ASSIGNMENT'){
                throw ValidationException::withMessages(['approval'=>'Technician assignment must be completed from the request execution workspace.']);
            }
        }

        if((int)$request->requested_by===(int)$user->id) throw ValidationException::withMessages(['approval'=>'Segregation of duties: requester cannot decide their own request.']);
        if(!in_array($decision,['APPROVED','REJECTED','RETURNED'],true)) throw ValidationException::withMessages(['approval'=>'Unsupported decision.']);
        if(in_array($decision,['REJECTED','RETURNED'],true)&&blank($note)) throw ValidationException::withMessages(['note'=>'A note is required when rejecting or returning an approval.']);

        return DB::transaction(function() use($request,$decision,$note,$user){
            $before=$request->toArray();
            $request->update(['status'=>$decision,'decided_by'=>$user->id,'decision_note'=>$note,'decided_at'=>now()]);
            $this->audit->record('workflow.approval.'.strtolower($decision),$request,$before,$request->fresh()->toArray());
            if($request->entity_type===ServiceRequest::class){
                $serviceRequest=ServiceRequest::find($request->entity_id);
                if($serviceRequest) $this->advanceServiceRequest($serviceRequest,$request,$decision,$note);
            }
            return $request->fresh();
        });
    }

    private function assertProjectAssignment($user,ServiceRequest $serviceRequest,string $approvalRole): void
    {
        if(!$serviceRequest->project_id) return;

        $role=strtoupper($approvalRole);
        $projectBound=[
            'OPERATIONS_MANAGER','MAINTENANCE_MANAGER','PROJECT_MANAGER','MAINTENANCE_ENGINEER',
            'TECHNICAL_SUPERVISOR','TECHNICIAN','QUALITY','HSE',
        ];
        if(!in_array($role,$projectBound,true)) return;

        $assigned=ProjectUserAssignment::query()
            ->where('tenant_id',$user->tenant_id)
            ->where('project_id',$serviceRequest->project_id)
            ->where('user_id',$user->id)
            ->where('project_role',$role)
            ->where('status','ACTIVE')
            ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
            ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))
            ->exists();

        if(!$assigned) throw ValidationException::withMessages([
            'approval'=>'You are not the active '.$role.' assigned to this project.',
        ]);
    }

    private function advanceServiceRequest(ServiceRequest $serviceRequest,ApprovalRequest $approval,string $decision,?string $note): void
    {
        if($decision==='REJECTED'){
            $this->workflow->reject($serviceRequest,$approval->action,Auth::id(),$note);
        } elseif($decision==='RETURNED'){
            $this->workflow->returnToPrevious($serviceRequest,$approval->action,Auth::id(),$note);
        } else {
            $this->workflow->advance($serviceRequest,$approval->action,Auth::id(),$note);
        }

        if($serviceRequest->customer_id){
            $customer=Customer::find($serviceRequest->customer_id);
            if($customer) $this->customers->record($customer,'APPROVAL_'.$decision,str_replace('_',' ',$approval->action).' '.$decision,$note,$serviceRequest,['approval_role'=>$approval->approval_role,'step_order'=>$approval->step_order],'INTERNAL');
        }
    }
}
