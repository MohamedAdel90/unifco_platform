<?php

namespace App\Services;

use App\Models\{ApprovalRequest,Customer,ServiceRequest};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(
        private AuditService $audit,
        private CustomerLifecycleService $customers,
        private ServiceRequestWorkflowService $workflow,
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
        if($user->role!=='ADMIN' && $request->approval_role && $request->approval_role!==$user->role) throw ValidationException::withMessages(['approval'=>'This approval belongs to '.$request->approval_role.'.']);
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
