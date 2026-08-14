<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(private AuditService $audit) {}

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
        if (! in_array($decision,['APPROVED','REJECTED'],true)) throw ValidationException::withMessages(['approval'=>'Unsupported decision.']);

        return DB::transaction(function () use ($request,$decision,$note) {
            $before=$request->toArray();
            $request->update(['status'=>$decision,'decided_by'=>Auth::id(),'decision_note'=>$note,'decided_at'=>now()]);
            $this->audit->record('workflow.approval.'.strtolower($decision),$request,$before,$request->fresh()->toArray());
            return $request->fresh();
        });
    }
}
