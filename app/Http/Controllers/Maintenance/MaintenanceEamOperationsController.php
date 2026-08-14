<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Item,MaintenancePlan,WorkOrder};
use App\Services\AuditService;
use App\Services\Maintenance\MaintenanceEamService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceEamOperationsController extends Controller
{
    public function index(): View
    {
        return view('maintenance.operations.index',[
            'assets'=>Asset::with('parent')->orderBy('asset_code')->get(),
            'plans'=>MaintenancePlan::with('asset')->latest('id')->get(),
            'orders'=>WorkOrder::with(['asset','plan'])->latest('id')->limit(30)->get(),
            'items'=>Item::orderBy('item_code')->get(),
        ]);
    }

    public function storePlan(Request $request, AuditService $audit): RedirectResponse
    {
        $tenant=Auth::user()->tenant_id;
        $data=$request->validate([
            'asset_id'=>['required',Rule::exists('assets','id')->where(fn($q)=>$q->where('tenant_id',$tenant))],
            'plan_no'=>['required','string','max:50',Rule::unique('maintenance_plans')->where(fn($q)=>$q->where('tenant_id',$tenant))],
            'name'=>'required|string|max:160','frequency_type'=>'required|in:DAYS,MONTHS,METER','frequency_value'=>'required|integer|min:1',
            'next_due_date'=>'nullable|date','next_due_meter'=>'nullable|numeric|min:0','priority'=>'required|in:LOW,NORMAL,HIGH,CRITICAL'
        ]);
        $plan=MaintenancePlan::create([...$data,'organization_id'=>Auth::user()->organization_id,'status'=>'ACTIVE']);
        $audit->record('maintenance.plan.created',$plan,[],$plan->toArray());
        return back()->with('status','Preventive maintenance plan created.');
    }

    public function generate(MaintenanceEamService $service): RedirectResponse
    { $count=$service->generateDueWorkOrders(); return back()->with('status',"Generated {$count} preventive work order(s)."); }

    public function meter(Request $request, Asset $asset, MaintenanceEamService $service): RedirectResponse
    {
        $data=$request->validate(['reading'=>'required|numeric|min:0','reading_date'=>'required|date','notes'=>'nullable|string|max:500']);
        $service->recordMeter($asset,(float)$data['reading'],$data['reading_date'],$data['notes']??null); return back()->with('status','Meter reading recorded.');
    }

    public function start(WorkOrder $workOrder, MaintenanceEamService $service): RedirectResponse
    { $service->start($workOrder); return back()->with('status','Work order started.'); }

    public function labor(Request $request, WorkOrder $workOrder, MaintenanceEamService $service): RedirectResponse
    { $d=$request->validate(['hours'=>'required|numeric|gt:0','hourly_rate'=>'required|numeric|min:0']); $service->addLabor($workOrder,(float)$d['hours'],(float)$d['hourly_rate']); return back()->with('status','Labor added.'); }

    public function material(Request $request, WorkOrder $workOrder, MaintenanceEamService $service): RedirectResponse
    {
        $tenant=Auth::user()->tenant_id;
        $d=$request->validate(['item_id'=>['required',Rule::exists('items','id')->where(fn($q)=>$q->where('tenant_id',$tenant))],'warehouse_code'=>'required|string|max:50','quantity'=>'required|numeric|gt:0','unit_cost'=>'required|numeric|min:0']);
        $service->issueMaterial($workOrder,Item::findOrFail($d['item_id']),$d['warehouse_code'],(float)$d['quantity'],(float)$d['unit_cost']); return back()->with('status','Maintenance material issued.');
    }

    public function complete(Request $request, WorkOrder $workOrder, MaintenanceEamService $service): RedirectResponse
    { $d=$request->validate(['downtime_minutes'=>'nullable|integer|min:0','failure_code'=>'nullable|string|max:100','external_cost'=>'nullable|numeric|min:0']); $service->complete($workOrder,(int)($d['downtime_minutes']??0),$d['failure_code']??null,(float)($d['external_cost']??0)); return back()->with('status','Work order completed.'); }

    public function transfer(Request $request, Asset $asset, MaintenanceEamService $service): RedirectResponse
    { $d=$request->validate(['to_location'=>'required|string|max:80','transfer_date'=>'required|date']); $service->transferAsset($asset,$d['to_location'],$d['transfer_date']); return back()->with('status','Asset transferred.'); }

    public function depreciate(Request $request, Asset $asset, MaintenanceEamService $service): RedirectResponse
    { $d=$request->validate(['posting_date'=>'required|date']); $service->depreciate($asset,$d['posting_date']); return back()->with('status','Asset depreciation posted.'); }

    public function dispose(Asset $asset, MaintenanceEamService $service): RedirectResponse
    { $service->dispose($asset); return back()->with('status','Asset disposed.'); }
}
