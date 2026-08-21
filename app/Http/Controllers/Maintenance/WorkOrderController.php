<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Item,WorkOrder};
use App\Services\AuditService;
use App\Services\Inventory\StockService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB,Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function index(): View
    {
        return view('maintenance.work-orders.index',[
            'orders'=>WorkOrder::with(['asset','plan','contract'])->latest('id')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('maintenance.work-orders.form',[
            'order'=>new WorkOrder(),
            'assets'=>Asset::whereIn('status',['REGISTERED','CAPITALIZED'])->orderBy('asset_code')->get(),
        ]);
    }

    public function edit(WorkOrder $workOrder): View
    {
        return view('maintenance.work-orders.form',[
            'order'=>$workOrder,
            'assets'=>Asset::whereIn('status',['REGISTERED','CAPITALIZED'])->orderBy('asset_code')->get(),
        ]);
    }

    public function show(WorkOrder $workOrder): View
    {
        $workOrder->load(['asset.customer','asset.site','plan','contract']);

        $tasks = collect();
        if ($workOrder->maintenance_plan_id) {
            $tasks = DB::table('maintenance_plan_tasks')
                ->leftJoin('work_order_checklist_results', function ($join) use ($workOrder) {
                    $join->on('work_order_checklist_results.maintenance_plan_task_id','=','maintenance_plan_tasks.id')
                        ->where('work_order_checklist_results.work_order_id','=',$workOrder->id);
                })
                ->where('maintenance_plan_tasks.maintenance_plan_id',$workOrder->maintenance_plan_id)
                ->orderBy('maintenance_plan_tasks.sort_order')
                ->select('maintenance_plan_tasks.*','work_order_checklist_results.result_status','work_order_checklist_results.numeric_value','work_order_checklist_results.text_value','work_order_checklist_results.technician_notes','work_order_checklist_results.completed_at')
                ->get();
        }

        $attachments = DB::table('work_order_attachments')->where('work_order_id',$workOrder->id)->latest()->get();
        $materials = DB::table('maintenance_materials')
            ->join('items','items.id','=','maintenance_materials.item_id')
            ->where('maintenance_materials.work_order_id',$workOrder->id)
            ->select('maintenance_materials.*','items.item_code','items.name as item_name','items.uom')
            ->orderByDesc('maintenance_materials.id')->get();
        $failures = DB::table('asset_failures')->where('work_order_id',$workOrder->id)->orderByDesc('failed_at')->get();
        $compatibleParts = DB::table('asset_spare_parts')
            ->join('items','items.id','=','asset_spare_parts.item_id')
            ->where('asset_spare_parts.asset_id',$workOrder->asset_id)
            ->select('items.*','asset_spare_parts.manufacturer_part_no','asset_spare_parts.critical_spare')
            ->orderByDesc('asset_spare_parts.critical_spare')->orderBy('items.item_code')->get();
        $allItems = Item::where('status','ACTIVE')->orderBy('item_code')->get();
        $metrics = $this->reliabilityMetrics($workOrder->asset_id);

        return view('maintenance.work-orders.show',compact('workOrder','tasks','attachments','materials','failures','compatibleParts','allItems','metrics'));
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $order=WorkOrder::create([...$this->validated($request),'organization_id'=>Auth::user()->organization_id,'status'=>'OPEN']);
        $audit->record('maintenance.work_order.created',$order,[],$order->toArray());
        return redirect()->route('maintenance.work-orders.show',$order)->with('status','Work order created.');
    }

    public function update(Request $request, WorkOrder $workOrder, AuditService $audit): RedirectResponse
    {
        if ($workOrder->status !== 'OPEN') throw ValidationException::withMessages(['work_order'=>'Only OPEN work orders can be edited.']);
        $before=$workOrder->toArray();
        $workOrder->update($this->validated($request,$workOrder));
        $audit->record('maintenance.work_order.updated',$workOrder,$before,$workOrder->fresh()->toArray());
        return redirect()->route('maintenance.work-orders.show',$workOrder)->with('status','Work order updated.');
    }

    public function start(Request $request, WorkOrder $workOrder, AuditService $audit): RedirectResponse
    {
        if ($workOrder->status !== 'OPEN') throw ValidationException::withMessages(['work_order'=>'Only OPEN work orders can be started.']);
        $data=$request->validate(['execution_notes'=>['nullable','string','max:4000']]);
        $before=$workOrder->toArray();
        $workOrder->update([
            'status'=>'IN_PROGRESS','started_at'=>now(),'started_by'=>Auth::id(),'execution_notes'=>$data['execution_notes']??null,
        ]);
        $audit->record('maintenance.work_order.started',$workOrder,$before,$workOrder->fresh()->toArray());
        return back()->with('status','Work order started.');
    }

    public function saveChecklist(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        abort_unless($workOrder->maintenance_plan_id,422,'This work order has no maintenance plan checklist.');
        abort_if($workOrder->status==='COMPLETED',422,'Completed work orders cannot be changed.');
        $results=(array)$request->input('results',[]);
        $tasks=DB::table('maintenance_plan_tasks')->where('maintenance_plan_id',$workOrder->maintenance_plan_id)->get()->keyBy('id');

        DB::transaction(function () use ($results,$tasks,$workOrder): void {
            foreach($results as $taskId=>$result){
                $task=$tasks->get((int)$taskId);
                if(!$task) continue;
                $status=$result['result_status']??null;
                $numeric=isset($result['numeric_value']) && $result['numeric_value']!=='' ? (float)$result['numeric_value'] : null;
                $text=$result['text_value']??null;
                if($task->response_type==='PASS_FAIL' && !in_array($status,['PASS','FAIL','NA'],true)) $status=null;
                if($task->response_type==='NUMBER' && $numeric!==null){
                    if($task->min_value!==null && $numeric<(float)$task->min_value) $status='FAIL';
                    elseif($task->max_value!==null && $numeric>(float)$task->max_value) $status='FAIL';
                    else $status='PASS';
                }
                $hasValue=$status!==null || $numeric!==null || filled($text) || filled($result['technician_notes']??null);
                if(!$hasValue) continue;
                DB::table('work_order_checklist_results')->updateOrInsert(
                    ['work_order_id'=>$workOrder->id,'maintenance_plan_task_id'=>$task->id],
                    [
                        'result_status'=>$status,'numeric_value'=>$numeric,'text_value'=>$text,
                        'technician_notes'=>$result['technician_notes']??null,'completed_by'=>Auth::id(),'completed_at'=>now(),
                        'created_at'=>now(),'updated_at'=>now(),
                    ]
                );
            }
        });
        return back()->with('status','Checklist results saved.');
    }

    public function uploadAttachment(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data=$request->validate([
            'maintenance_plan_task_id'=>['nullable','integer','exists:maintenance_plan_tasks,id'],
            'attachment_type'=>['required','in:PHOTO,BEFORE_PHOTO,AFTER_PHOTO,THERMAL_IMAGE,DOCUMENT,OTHER'],
            'title'=>['nullable','string','max:255'],'file'=>['required','file','max:15360'],
        ]);
        if(!empty($data['maintenance_plan_task_id']) && $workOrder->maintenance_plan_id){
            abort_unless(DB::table('maintenance_plan_tasks')->where('id',$data['maintenance_plan_task_id'])->where('maintenance_plan_id',$workOrder->maintenance_plan_id)->exists(),422,'Checklist task does not belong to this work order plan.');
        }
        $path=$request->file('file')->store('work-orders/'.$workOrder->id,'public');
        DB::table('work_order_attachments')->insert([
            'work_order_id'=>$workOrder->id,'maintenance_plan_task_id'=>$data['maintenance_plan_task_id']??null,
            'attachment_type'=>$data['attachment_type'],'title'=>$data['title']??null,'file_path'=>$path,
            'original_name'=>$request->file('file')->getClientOriginalName(),'uploaded_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('status','Work order evidence uploaded.');
    }

    public function downloadAttachment(WorkOrder $workOrder, int $attachment)
    {
        $file=DB::table('work_order_attachments')->where('id',$attachment)->where('work_order_id',$workOrder->id)->first();
        abort_unless($file,404);
        return Storage::disk('public')->download($file->file_path,$file->original_name);
    }

    public function issueMaterial(Request $request, WorkOrder $workOrder, StockService $stock): RedirectResponse
    {
        abort_if($workOrder->status==='COMPLETED',422,'Cannot issue material to a completed work order.');
        $data=$request->validate([
            'item_id'=>['required','integer','exists:items,id'],'warehouse_code'=>['required','string','max:50'],
            'quantity'=>['required','numeric','gt:0'],'unit_cost'=>['nullable','numeric','min:0'],
        ]);
        $item=Item::findOrFail($data['item_id']);
        $unitCost=(float)($data['unit_cost']??0);
        $total=round((float)$data['quantity']*$unitCost,2);
        DB::transaction(function () use ($stock,$item,$data,$workOrder,$unitCost,$total): void {
            $stock->move($item,$data['warehouse_code'],'ISSUE',(float)$data['quantity'],'WO-'.$workOrder->id.'-'.Str::uuid(),'WORK_ORDER',$workOrder->id);
            DB::table('maintenance_materials')->insert([
                'tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,'work_order_id'=>$workOrder->id,
                'item_id'=>$item->id,'warehouse_code'=>$data['warehouse_code'],'quantity'=>$data['quantity'],'unit_cost'=>$unitCost,'total_cost'=>$total,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            $materialCost=(float)DB::table('maintenance_materials')->where('work_order_id',$workOrder->id)->sum('total_cost');
            $workOrder->update(['material_cost'=>$materialCost,'total_cost'=>$materialCost+(float)$workOrder->labor_cost+(float)$workOrder->external_cost]);
        });
        return back()->with('status','Spare part issued from inventory and charged to the work order.');
    }

    public function recordFailure(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data=$request->validate([
            'failure_code'=>['nullable','string','max:120'],'failure_mode'=>['required','string','max:180'],'failure_effect'=>['nullable','string','max:180'],
            'failure_cause'=>['nullable','string','max:180'],'root_cause'=>['nullable','string','max:4000'],'corrective_action'=>['nullable','string','max:4000'],
            'failed_at'=>['required','date'],'restored_at'=>['nullable','date','after_or_equal:failed_at'],'downtime_minutes'=>['nullable','integer','min:0'],
            'severity'=>['required','in:LOW,MEDIUM,HIGH,CRITICAL'],
        ]);
        $failedAt=now()->parse($data['failed_at']);
        $restoredAt=!empty($data['restored_at'])?now()->parse($data['restored_at']):null;
        $downtime=$data['downtime_minutes']??($restoredAt?$failedAt->diffInMinutes($restoredAt):0);
        DB::table('asset_failures')->insert([
            'tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,'asset_id'=>$workOrder->asset_id,'work_order_id'=>$workOrder->id,
            'failure_code'=>$data['failure_code']??null,'failure_mode'=>$data['failure_mode'],'failure_effect'=>$data['failure_effect']??null,'failure_cause'=>$data['failure_cause']??null,
            'root_cause'=>$data['root_cause']??null,'corrective_action'=>$data['corrective_action']??null,'failed_at'=>$failedAt,'restored_at'=>$restoredAt,
            'downtime_minutes'=>$downtime,'meter_at_failure'=>$workOrder->asset?->meter_value,'severity'=>$data['severity'],'status'=>$restoredAt?'CLOSED':'OPEN',
            'reported_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $workOrder->update(['failure_code'=>$data['failure_code']??$data['failure_mode'],'downtime_minutes'=>$downtime]);
        return back()->with('status','Failure history and reliability data recorded.');
    }

    public function complete(Request $request, WorkOrder $workOrder, AuditService $audit): RedirectResponse
    {
        if (! in_array($workOrder->status,['OPEN','IN_PROGRESS'],true)) throw ValidationException::withMessages(['work_order'=>'Only active work orders can be completed.']);
        $data=$request->validate([
            'labor_hours'=>['nullable','numeric','min:0'],'labor_cost'=>['nullable','numeric','min:0'],'external_cost'=>['nullable','numeric','min:0'],
            'completion_notes'=>['required','string','max:5000'],
        ]);

        if($workOrder->maintenance_plan_id){
            $requiredTasks=DB::table('maintenance_plan_tasks')->where('maintenance_plan_id',$workOrder->maintenance_plan_id)->where('required',true)->pluck('id');
            $completed=DB::table('work_order_checklist_results')->where('work_order_id',$workOrder->id)->whereIn('maintenance_plan_task_id',$requiredTasks)->whereNotNull('completed_at')->pluck('maintenance_plan_task_id');
            $missing=$requiredTasks->diff($completed);
            if($missing->isNotEmpty()) throw ValidationException::withMessages(['checklist'=>'Complete all required maintenance checklist tasks before closing the work order.']);

            $photoRequired=DB::table('maintenance_plan_tasks')->where('maintenance_plan_id',$workOrder->maintenance_plan_id)->where('photo_required',true)->pluck('id');
            if($photoRequired->isNotEmpty()){
                $withPhoto=DB::table('work_order_attachments')->where('work_order_id',$workOrder->id)->whereIn('maintenance_plan_task_id',$photoRequired)->pluck('maintenance_plan_task_id');
                if($photoRequired->diff($withPhoto)->isNotEmpty()) throw ValidationException::withMessages(['checklist'=>'Upload the required photo evidence before closing the work order.']);
            }
        }

        $before=$workOrder->toArray();
        $labor=(float)($data['labor_cost']??$workOrder->labor_cost??0);
        $external=(float)($data['external_cost']??$workOrder->external_cost??0);
        $material=(float)$workOrder->material_cost;
        $workOrder->update([
            'status'=>'COMPLETED','completed_at'=>now(),'completed_by'=>Auth::id(),'labor_hours'=>$data['labor_hours']??$workOrder->labor_hours,
            'labor_cost'=>$labor,'external_cost'=>$external,'total_cost'=>$labor+$external+$material,'completion_notes'=>$data['completion_notes'],
        ]);
        $audit->record('maintenance.work_order.completed',$workOrder,$before,$workOrder->fresh()->toArray());
        return back()->with('status','Work order completed with execution evidence and final cost.');
    }

    private function reliabilityMetrics(int $assetId): array
    {
        $failures=DB::table('asset_failures')->where('asset_id',$assetId)->orderBy('failed_at')->get();
        $count=$failures->count();
        $totalDowntime=(int)$failures->sum('downtime_minutes');
        $mttr=$count?round($totalDowntime/$count,1):null;
        $intervals=[];
        for($i=1;$i<$count;$i++){
            $previousRestored=$failures[$i-1]->restored_at;
            if($previousRestored){
                $start=now()->parse($previousRestored);
                $end=now()->parse($failures[$i]->failed_at);
                if($end->greaterThan($start)) $intervals[]=$start->diffInMinutes($end);
            }
        }
        $mtbf=count($intervals)?round(array_sum($intervals)/count($intervals),1):null;
        return ['failure_count'=>$count,'total_downtime_minutes'=>$totalDowntime,'mttr_minutes'=>$mttr,'mtbf_minutes'=>$mtbf];
    }

    private function validated(Request $request, ?WorkOrder $order=null): array
    {
        $tenant=Auth::user()->tenant_id;
        return $request->validate([
            'work_order_no'=>['required','string','max:50',Rule::unique('work_orders')->where(fn($q)=>$q->where('tenant_id',$tenant))->ignore($order?->id)],
            'asset_id'=>['required',Rule::exists('assets','id')->where(fn($q)=>$q->where('tenant_id',$tenant))],
            'maintenance_type'=>['required','in:PREVENTIVE,CORRECTIVE,PREDICTIVE,INSPECTION'],'priority'=>['required','in:LOW,NORMAL,HIGH,CRITICAL'],'planned_start'=>['nullable','date'],
        ]);
    }
}
