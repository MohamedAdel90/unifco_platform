<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\{Asset,WorkOrder};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function index(): View { return view('maintenance.work-orders.index',['orders'=>WorkOrder::with('asset')->latest('id')->paginate(25)]); }
    public function create(): View { return view('maintenance.work-orders.form',['order'=>new WorkOrder(),'assets'=>Asset::whereIn('status',['REGISTERED','CAPITALIZED'])->orderBy('asset_code')->get()]); }
    public function edit(WorkOrder $workOrder): View { return view('maintenance.work-orders.form',['order'=>$workOrder,'assets'=>Asset::whereIn('status',['REGISTERED','CAPITALIZED'])->orderBy('asset_code')->get()]); }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $order=WorkOrder::create([...$this->validated($request),'organization_id'=>Auth::user()->organization_id,'status'=>'OPEN']);
        $audit->record('maintenance.work_order.created',$order,[],$order->toArray());
        return redirect()->route('maintenance.work-orders.index')->with('status','Work order created.');
    }

    public function update(Request $request, WorkOrder $workOrder, AuditService $audit): RedirectResponse
    {
        if ($workOrder->status !== 'OPEN') throw ValidationException::withMessages(['work_order'=>'Only OPEN work orders can be edited.']);
        $before=$workOrder->toArray(); $workOrder->update($this->validated($request,$workOrder));
        $audit->record('maintenance.work_order.updated',$workOrder,$before,$workOrder->fresh()->toArray());
        return redirect()->route('maintenance.work-orders.index')->with('status','Work order updated.');
    }

    public function complete(WorkOrder $workOrder, AuditService $audit): RedirectResponse
    {
        if ($workOrder->status !== 'OPEN') throw ValidationException::withMessages(['work_order'=>'Only OPEN work orders can be completed.']);
        $before=$workOrder->toArray(); $workOrder->update(['status'=>'COMPLETED']);
        $audit->record('maintenance.work_order.completed',$workOrder,$before,$workOrder->fresh()->toArray());
        return back()->with('status','Work order completed.');
    }

    private function validated(Request $request, ?WorkOrder $order=null): array
    {
        $tenant=Auth::user()->tenant_id;
        return $request->validate([
            'work_order_no'=>['required','string','max:50',Rule::unique('work_orders')->where(fn($q)=>$q->where('tenant_id',$tenant))->ignore($order?->id)],
            'asset_id'=>['required',Rule::exists('assets','id')->where(fn($q)=>$q->where('tenant_id',$tenant))],
            'maintenance_type'=>['required','in:PREVENTIVE,CORRECTIVE'],'priority'=>['required','in:LOW,NORMAL,HIGH,CRITICAL'],'planned_start'=>['nullable','date'],
        ]);
    }
}
