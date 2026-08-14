<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\{Item,PurchaseOrder,PurchaseRequisition,Supplier};
use App\Services\AuditService;
use App\Services\Procurement\ProcurementFlowService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class ProcurementOperationsController extends Controller
{
    public function index(): View
    {
        return view('procurement.operations.index',[
            'suppliers'=>Supplier::orderBy('supplier_code')->get(),
            'requisitions'=>PurchaseRequisition::with('lines')->latest()->get(),
            'orders'=>PurchaseOrder::with('lines')->latest()->limit(30)->get(),
            'items'=>Item::orderBy('item_code')->get(),
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
        $d=$r->validate(['requisition_no'=>['required','string','max:50'],'requested_date'=>['required','date'],'purpose'=>['nullable','string'],'lines'=>['required','array','min:1'],'lines.*.item_id'=>['required','integer'],'lines.*.quantity'=>['required','numeric','gt:0'],'lines.*.estimated_unit_price'=>['required','numeric','min:0']]);
        $req=PurchaseRequisition::create(['organization_id'=>$r->user()->organization_id,'requisition_no'=>$d['requisition_no'],'requested_date'=>$d['requested_date'],'purpose'=>$d['purpose']??null,'status'=>'DRAFT','created_by'=>$r->user()->id]);
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
