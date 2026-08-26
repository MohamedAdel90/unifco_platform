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
    ) {}

    public function request(Model $entity, string $action): ApprovalRequest
    {
        return ApprovalRequest::firstOrCreate([
            'tenant_id'=>Auth::user()->tenant_id,'entity_type'=>$entity::class,'entity_id'=>$entity->getKey(),
            'action'=>$action,'status'=>'PENDING',
        ],['organization_id'=>Auth::user()->organization_id,'requested_by'=>Auth::id()]);
    }

    public function decide(ApprovalRequest $request, string $decision, ?string $note=null): ApprovalRequest
    {
        if ($request->status !== 'PENDING') throw ValidationException::withMessages(['approval'=>'Approval request is already decided.']);
        if ((int)$request->requested_by === (int)Auth::id()) throw ValidationException::withMessages(['approval'=>'Segregation of duties: requester cannot decide their own request.']);
        if (! in_array($decision,['APPROVED','REJECTED','RETURNED'],true)) throw ValidationException::withMessages(['approval'=>'Unsupported decision.']);
        if (in_array($decision, ['REJECTED','RETURNED'], true) && blank($note)) throw ValidationException::withMessages(['note'=>'A note is required when rejecting or returning an approval.']);

        return DB::transaction(function () use ($request,$decision,$note) {
            $before=$request->toArray();
            $request->update(['status'=>$decision,'decided_by'=>Auth::id(),'decision_note'=>$note,'decided_at'=>now()]);
            $this->audit->record('workflow.approval.'.strtolower($decision),$request,$before,$request->fresh()->toArray());

            if ($request->entity_type === ServiceRequest::class) {
                $serviceRequest = ServiceRequest::find($request->entity_id);
                if ($serviceRequest) {
                    if ($decision === 'REJECTED') {
                        $serviceRequest->update(['status'=>'REJECTED','workflow_stage'=>'REJECTED','current_stage_due_at'=>null]);
                    } elseif ($decision === 'RETURNED') {
                        $serviceRequest->update(['status'=>'OPEN','workflow_stage'=>'TECHNICAL_REVIEW','current_stage_due_at'=>now()->addMinutes(120)]);
                    } else {
                        $next = ApprovalRequest::where('entity_type', ServiceRequest::class)
                            ->where('entity_id', $serviceRequest->id)
                            ->where('status', 'PENDING')
                            ->orderBy('step_order')->first();
                        if ($next) {
                            $serviceRequest->update(['workflow_stage'=>$next->action,'current_stage_due_at'=>$next->due_at]);
                        } else {
                            $nextStage = $serviceRequest->quotation_id ? 'COMMERCIAL_READY' : ($serviceRequest->work_order_id ? 'PLANNING' : 'QUALIFIED');
                            $serviceRequest->update(['workflow_stage'=>$nextStage,'current_stage_due_at'=>null]);
                        }
                    }

                    if ($serviceRequest->customer_id) {
                        $customer = Customer::find($serviceRequest->customer_id);
                        if ($customer) {
                            $this->customers->record(
                                $customer,
                                'APPROVAL_'.$decision,
                                str_replace('_',' ', $request->action).' '.$decision,
                                $note,
                                $serviceRequest,
                                ['approval_role'=>$request->approval_role,'step_order'=>$request->step_order],
                                'INTERNAL'
                            );
                        }
                    }
                }
            }

            return $request->fresh();
        });
    }
}
