<?php

namespace App\Services;

use App\Models\{ApprovalRequest,Customer,ServiceRequest};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(private AuditService $audit, private CustomerLifecycleService $customers) {}

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
            ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$serviceRequest->id)->where('status','WAITING')->update(['status'=>'CANCELLED']);
            $serviceRequest->update(['status'=>'REJECTED','workflow_stage'=>'REJECTED','current_stage_due_at'=>null]);
        } elseif($decision==='RETURNED'){
            $technical=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$serviceRequest->id)->orderBy('step_order')->first();
            ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$serviceRequest->id)->where('step_order','>',1)->whereNotIn('status',['REJECTED','CANCELLED'])->update(['status'=>'WAITING','due_at'=>null,'decided_by'=>null,'decided_at'=>null]);
            if($technical) $technical->update(['status'=>'PENDING','due_at'=>now()->addMinutes($technical->sla_minutes?:120),'decided_by'=>null,'decided_at'=>null,'decision_note'=>trim('Returned for correction: '.($note?:''))]);
            $serviceRequest->update(['status'=>'OPEN','workflow_stage'=>'TECHNICAL_REVIEW','current_stage_due_at'=>now()->addMinutes(120)]);
        } else {
            $next=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$serviceRequest->id)->where('status','WAITING')->orderBy('step_order')->first();
            if($next){
                $dueAt=now()->addMinutes($next->sla_minutes?:120);
                $next->update(['status'=>'PENDING','due_at'=>$dueAt]);
                $serviceRequest->update(['workflow_stage'=>$next->action,'current_stage_due_at'=>$dueAt]);
            } else {
                $nextStage=$serviceRequest->quotation_id?'COMMERCIAL_READY':($serviceRequest->work_order_id?'PLANNING':'QUALIFIED');
                $serviceRequest->update(['workflow_stage'=>$nextStage,'current_stage_due_at'=>null]);
            }
        }

        if($serviceRequest->customer_id){
            $customer=Customer::find($serviceRequest->customer_id);
            if($customer) $this->customers->record($customer,'APPROVAL_'.$decision,str_replace('_',' ',$approval->action).' '.$decision,$note,$serviceRequest,['approval_role'=>$approval->approval_role,'step_order'=>$approval->step_order],'INTERNAL');
        }
    }
}
