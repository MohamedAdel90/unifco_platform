<?php

namespace App\Services;

use App\Models\{ApprovalRequest,ServiceRequest};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RequestWorkflowHealthService
{
    private const TERMINAL_STATUSES = ['CLOSED','COMPLETED','CANCELLED','REJECTED'];
    private const TERMINAL_STAGES = ['COMPLETED','REJECTED'];
    private const QUOTATION_WORKFLOWS = [
        'QUOTATION',
        'SPARE_PARTS_QUOTATION',
        'TECHNICAL_VISIT',
        'MAINTENANCE_CONTRACT_QUOTATION',
    ];

    /**
     * Inspect a pre-scoped ServiceRequest query. Passing the caller's scoped
     * query means the same service can power dashboards without bypassing RBAC.
     */
    public function summary(Builder $requests): array
    {
        $active=(clone $requests)->whereNotIn('status',self::TERMINAL_STATUSES);
        $rows=(clone $active)->get([
            'id','request_no','status','workflow_key','workflow_stage','next_action',
            'work_order_id','quotation_id','workflow_started_at','current_stage_due_at',
        ]);

        $pending=$this->pendingApprovalKeys($rows);
        $exceptions=collect();

        foreach($rows as $request){
            if($request->current_stage_due_at && $request->current_stage_due_at->isPast()){
                $exceptions->push($this->exception($request,'SLA_OVERDUE','HIGH'));
            }
            if($request->workflow_started_at && !in_array((string)$request->workflow_stage,self::TERMINAL_STAGES,true) && !$request->next_action){
                $exceptions->push($this->exception($request,'MISSING_NEXT_ACTION','CRITICAL'));
            }
            if($request->workflow_stage==='EXECUTION' && !$request->work_order_id){
                $exceptions->push($this->exception($request,'EXECUTION_WITHOUT_WORK_ORDER','CRITICAL'));
            }
            if(in_array((string)$request->workflow_key,self::QUOTATION_WORKFLOWS,true) && !$request->quotation_id){
                $exceptions->push($this->exception($request,'QUOTATION_ARTIFACT_MISSING','CRITICAL'));
            }
            if($request->workflow_started_at && !in_array((string)$request->workflow_stage,self::TERMINAL_STAGES,true)){
                $key=$request->id.'|'.$request->workflow_stage;
                if(!$pending->has($key)){
                    $exceptions->push($this->exception($request,'PENDING_APPROVAL_MISSING','CRITICAL'));
                }
            }
        }

        $exceptions=$exceptions->unique(fn(array $item)=>$item['request_id'].'|'.$item['code'])->values();
        $byCode=$exceptions->countBy('code')->sortDesc()->all();

        return [
            'healthy'=>$exceptions->isEmpty(),
            'active_requests'=>$rows->count(),
            'exception_count'=>$exceptions->count(),
            'critical_count'=>$exceptions->where('severity','CRITICAL')->count(),
            'high_count'=>$exceptions->where('severity','HIGH')->count(),
            'by_code'=>$byCode,
            'exceptions'=>$exceptions->take(50)->values(),
        ];
    }

    private function pendingApprovalKeys(Collection $requests): Collection
    {
        $ids=$requests->pluck('id')->all();
        if(!$ids) return collect();

        return ApprovalRequest::query()
            ->where('entity_type',ServiceRequest::class)
            ->whereIn('entity_id',$ids)
            ->where('status','PENDING')
            ->get(['entity_id','action'])
            ->mapWithKeys(fn($row)=>[$row->entity_id.'|'.$row->action=>true]);
    }

    private function exception(ServiceRequest $request,string $code,string $severity): array
    {
        return [
            'request_id'=>$request->id,
            'request_no'=>$request->request_no,
            'workflow_key'=>$request->workflow_key,
            'stage'=>$request->workflow_stage,
            'status'=>$request->status,
            'code'=>$code,
            'severity'=>$severity,
        ];
    }
}
