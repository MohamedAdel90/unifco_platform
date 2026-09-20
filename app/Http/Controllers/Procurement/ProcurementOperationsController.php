<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\{Item,Project,PurchaseOrder,PurchaseRequisition,Supplier};
use App\Services\{AuditService,ScopeService};
use App\Services\Procurement\ProcurementFlowService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class ProcurementOperationsController extends Controller
{
    public function index(Request $request, ScopeService $scopes): View
    {
        $user=$request->user();
        return view('procurement.operations.index',[
            'suppliers'=>Supplier::where('tenant_id',$user->tenant_id)->orderBy('supplier_code')->get(),
            'requisitions'=>$scopes->apply(PurchaseRequisition::with(['lines','project'])->where('tenant_id',$user->tenant_id),$user)->latest()->get(),
            'orders'=>$scopes->apply(PurchaseOrder::with(['lines','project'])->where('tenant_id',$user->tenant_id),$user)->latest()->limit(30)->get(),
            'items'=>Item::where('tenant_id',$user->tenant_id)->orderBy('item_code')->get(),
            'projects'=>$scopes->apply(Project::query()->where('tenant_id',$user->tenant_id)->where('status','ACTIVE'),$user)->orderBy('project_no')->get(['id','project_no','name']),
        ]);
    }

    public function storeSupplier(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['supplier_code'=>['required','string','max:40'],'name'=>['required','string','max:160'],'email'=>['nullable','email'],'tax_no'=>['nullable','string','max:80']]);
        $s=Supplier::create([...$d,'organization_id'=>$r->user()->organization_id,'status'=>'ACTIVE']);
        $audit->record('procurement.supplier.created',$s,[],$s->toArray()); return back()->with('status','Supplier created.');
    }

    public function storeRequisition(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['project_id'=>['required','integer'],'requisition_no'=>['required','string','max:50'],'requested_date'=>['required','date'],'purpose'=>['nullable','string'],'lines'=>['required','array','min:1'],'lines.*.item_id'=>['required','integer'],'lines.*.quantity'=>['required','numeric','gt:0'],'lines.*.estimated_unit_price'=>['required','numeric','min:0']]);
        $project=Project::where('tenant_id',$r->user()->tenant_id)->where('status','ACTIVE')->findOrFail($d['project_id']);
        abort_unless(app(ScopeService::class)->allows($r->user(),$project),403,'Project is outside your access scope.');
        $req=PurchaseRequisition::create(['organization_id'=>$r->user()->organization_id,'project_id'=>$project->id,'requisition_no'=>$d['requisition_no'],'requested_date'=>$d['requested_date'],'purpose'=>$d['purpose']??null,'status'=>'DRAFT','created_by'=>$r->user()->id]);
        foreach($d['lines'] as $i=>$line) $req->lines()->create([...$line,'line_no'=>$i+1]);
        $audit->record('procurement.requisition.created',$req,[],$req->fresh('lines')->toArray()); return back()->with('status','Requisition created.');
    }

    public function approve(PurchaseRequisition $requisition, ProcurementFlowService $svc): RedirectResponse { $svc->approveRequisition($requisition); return back()->with('status','Requisition approved.'); }

    public function convert(Request $r, PurchaseRequisition $requisition, ProcurementFlowService $svc): RedirectResponse
    {
        $d=$r->validate(['supplier_id'=>['required','integer'],'po_number'=>['required','string','max:50']]);
        $svc->convertToPurchaseOrder($requisition,Supplier::findOrFail($d['supplier_id']),$d['po_number']); return back()->with('status','Purchase order created from requisition.');
    }

    public function matchInvoice(Request $r, PurchaseOrder $purchaseOrder, ProcurementFlowService $svc): RedirectResponse
    {
        $d=$r->validate(['supplier_id'=>['required','integer'],'invoice_no'=>['required','string','max:50'],'invoice_date'=>['required','date'],'amount'=>['required','numeric','gt:0'],'currency'=>['required','string','size:3'],'control_account_code'=>['required','string','max:30'],'offset_account_code'=>['required','string','max:30']]);
        $svc->matchSupplierInvoice($purchaseOrder,Supplier::findOrFail($d['supplier_id']),$d); return back()->with('status','Supplier invoice matched and AP draft created.');
    }
}
