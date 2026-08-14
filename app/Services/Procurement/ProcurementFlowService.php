<?php

namespace App\Services\Procurement;

use App\Models\{FinancialDocument,PurchaseOrder,PurchaseRequisition,Supplier,SupplierInvoice};
use App\Services\AuditService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class ProcurementFlowService
{
    public function __construct(private AuditService $audit) {}

    public function approveRequisition(PurchaseRequisition $requisition): PurchaseRequisition
    {
        if ($requisition->status !== 'DRAFT') throw ValidationException::withMessages(['requisition'=>'Only DRAFT requisitions can be approved.']);
        if ((int)$requisition->created_by === (int)Auth::id()) throw ValidationException::withMessages(['requisition'=>'Segregation of duties: creator cannot approve their own requisition.']);
        $before=$requisition->toArray();
        $requisition->update(['status'=>'APPROVED','approved_by'=>Auth::id(),'approved_at'=>now()]);
        $this->audit->record('procurement.requisition.approved',$requisition,$before,$requisition->fresh()->toArray());
        return $requisition->fresh('lines');
    }

    public function convertToPurchaseOrder(PurchaseRequisition $requisition, Supplier $supplier, string $poNumber): PurchaseOrder
    {
        if ($requisition->status !== 'APPROVED') throw ValidationException::withMessages(['requisition'=>'Only APPROVED requisitions can be converted.']);
        return DB::transaction(function () use ($requisition,$supplier,$poNumber) {
            $requisition->load('lines');
            $total=$requisition->lines->sum(fn($l)=>(float)$l->quantity*(float)$l->estimated_unit_price);
            $po=PurchaseOrder::create(['organization_id'=>$requisition->organization_id,'created_by'=>Auth::id(),'supplier_id'=>$supplier->id,'purchase_requisition_id'=>$requisition->id,'po_number'=>$poNumber,'supplier_name'=>$supplier->name,'order_date'=>now()->toDateString(),'total'=>$total,'status'=>'DRAFT']);
            foreach ($requisition->lines as $line) $po->lines()->create(['line_no'=>$line->line_no,'item_id'=>$line->item_id,'quantity'=>$line->quantity,'unit_price'=>$line->estimated_unit_price]);
            $this->audit->record('procurement.purchase_order.created_from_requisition',$po,[], $po->fresh('lines')->toArray());
            return $po->fresh('lines');
        });
    }

    public function matchSupplierInvoice(PurchaseOrder $po, Supplier $supplier, array $data): SupplierInvoice
    {
        if ($po->status !== 'APPROVED') throw ValidationException::withMessages(['purchase_order'=>'Only APPROVED purchase orders can be matched.']);
        $received=(float)DB::table('goods_receipt_lines')->join('purchase_order_lines','purchase_order_lines.id','=','goods_receipt_lines.purchase_order_line_id')->where('purchase_order_lines.purchase_order_id',$po->id)->sum(DB::raw('goods_receipt_lines.quantity * goods_receipt_lines.unit_cost'));
        $amount=(float)$data['amount'];
        if ($amount <= 0 || $amount > $received + 0.01) throw ValidationException::withMessages(['amount'=>'Supplier invoice cannot exceed the value of received goods.']);
        return DB::transaction(function () use ($po,$supplier,$data,$amount) {
            $invoice=SupplierInvoice::create(['organization_id'=>$po->organization_id,'supplier_id'=>$supplier->id,'purchase_order_id'=>$po->id,'invoice_no'=>$data['invoice_no'],'invoice_date'=>$data['invoice_date'],'amount'=>$amount,'status'=>'MATCHED','created_by'=>Auth::id()]);
            $fin=FinancialDocument::create(['organization_id'=>$po->organization_id,'document_no'=>'AP-'.$data['invoice_no'],'document_type'=>'AP_INVOICE','counterparty_name'=>$supplier->name,'document_date'=>$data['invoice_date'],'currency'=>$data['currency'] ?? 'USD','amount'=>$amount,'control_account_code'=>$data['control_account_code'],'offset_account_code'=>$data['offset_account_code'],'status'=>'DRAFT','created_by'=>Auth::id(),'open_amount'=>0]);
            $invoice->update(['financial_document_id'=>$fin->id]);
            $this->audit->record('procurement.supplier_invoice.matched',$invoice,[],['financial_document_id'=>$fin->id,'amount'=>$amount]);
            return $invoice->fresh();
        });
    }
}
