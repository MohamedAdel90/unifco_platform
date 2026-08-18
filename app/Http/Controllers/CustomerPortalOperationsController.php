<?php

namespace App\Http\Controllers;

use App\Models\{Asset,CrmQuotation,FinancialDocument,MaintenanceVisitReport,ServiceContract,ServiceRequest};
use App\Services\CustomerPdfService;
use Illuminate\Http\{RedirectResponse,Request,Response};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerPortalOperationsController extends Controller
{
    private function customerId(): int
    {
        $user = auth()->user();
        abort_unless($user && $user->role === 'CUSTOMER' && $user->customer_id, 403);
        return (int) $user->customer_id;
    }

    public function requestService(Request $request): RedirectResponse
    {
        $customerId = $this->customerId();
        $data = $request->validate([
            'service_contract_id'=>['nullable','integer','exists:service_contracts,id'],
            'asset_id'=>['nullable','integer','exists:assets,id'],
            'service_category'=>['required','string','max:120'],
            'subject'=>['required','string','max:180'],
            'details'=>['required','string','max:5000'],
            'site_city'=>['nullable','string','max:120'],
            'priority'=>['required','in:NORMAL,HIGH,EMERGENCY'],
        ]);
        if (!empty($data['asset_id'])) abort_unless(Asset::whereKey($data['asset_id'])->where('customer_id',$customerId)->exists(),403);
        if (!empty($data['service_contract_id'])) abort_unless(ServiceContract::whereKey($data['service_contract_id'])->where('customer_id',$customerId)->exists(),403);
        $customer = DB::table('customers')->find($customerId);
        ServiceRequest::create($data + [
            'tenant_id'=>auth()->user()->tenant_id,'organization_id'=>auth()->user()->organization_id,'customer_id'=>$customerId,
            'request_no'=>'CP-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)), 'company_name'=>$customer->name,
            'email'=>$customer->email,'mobile'=>null,'priority'=>$data['priority'],'status'=>'OPEN',
            'response_sla_minutes'=>$data['priority']==='EMERGENCY'?30:240,
            'resolution_sla_minutes'=>$data['priority']==='EMERGENCY'?240:1440,
        ]);
        return back()->with('status','تم إرسال طلب الخدمة ومتابعته من لوحة العميل.');
    }

    public function decideQuotation(Request $request, CrmQuotation $quotation): RedirectResponse
    {
        $customerId=$this->customerId();
        abort_unless((int)$quotation->customer_id===$customerId,403);
        $data=$request->validate(['decision'=>['required','in:APPROVE,REJECT'],'notes'=>['nullable','string','max:2000']]);
        $quotation->update($data['decision']==='APPROVE' ? [
            'status'=>'CUSTOMER_APPROVED','customer_approved_at'=>now(),'customer_rejected_at'=>null,'customer_decision_notes'=>$data['notes']??null,
        ] : [
            'status'=>'CUSTOMER_REJECTED','customer_rejected_at'=>now(),'customer_approved_at'=>null,'customer_decision_notes'=>$data['notes']??null,
        ]);
        return back()->with('status','تم تسجيل قرار عرض السعر.');
    }

    public function invoicePdf(FinancialDocument $invoice, CustomerPdfService $pdf): Response
    {
        abort_unless((int)$invoice->customer_id===$this->customerId() && $invoice->document_type==='AR_INVOICE',403);
        return $this->pdfResponse($pdf->make('UNIFCO INVOICE',[
            'Invoice: '.$invoice->document_no,'Date: '.$invoice->document_date?->format('Y-m-d'),'Due: '.$invoice->due_date?->format('Y-m-d'),
            'Amount: '.$invoice->amount.' '.$invoice->currency,'Open amount: '.$invoice->open_amount,'Status: '.$invoice->status,
        ]),'invoice-'.$invoice->document_no.'.pdf');
    }

    public function contractPdf(ServiceContract $contract, CustomerPdfService $pdf): Response
    {
        abort_unless((int)$contract->customer_id===$this->customerId(),403);
        return $this->pdfResponse($pdf->make('UNIFCO SERVICE CONTRACT',[
            'Contract: '.$contract->contract_no,'Title: '.$contract->title,'Starts: '.$contract->starts_on?->format('Y-m-d'),
            'Ends: '.$contract->ends_on?->format('Y-m-d'),'Value: '.$contract->contract_value.' '.$contract->currency,
            'Billing: '.$contract->billing_cycle,'Status: '.$contract->status,'Scope: '.($contract->scope??''),'SLA: '.($contract->sla_summary??''),
        ]),'contract-'.$contract->contract_no.'.pdf');
    }

    public function visitPdf(MaintenanceVisitReport $report, CustomerPdfService $pdf): Response
    {
        abort_unless((int)$report->customer_id===$this->customerId(),403);
        return $this->pdfResponse($pdf->make('UNIFCO TECHNICAL VISIT REPORT',[
            'Report: '.$report->report_no,'Visit date: '.$report->visit_date?->format('Y-m-d'),'Type: '.$report->visit_type,
            'Technician: '.$report->technician_name,'Findings: '.$report->findings,'Work performed: '.$report->work_performed,
            'Recommendations: '.$report->recommendations,
        ]),'technical-'.$report->report_no.'.pdf');
    }

    public function attachment(int $id)
    {
        $customerId=$this->customerId();
        $row=DB::table('maintenance_attachments')->where('id',$id)->where('customer_id',$customerId)->first();
        abort_unless($row,404);
        abort_unless(storage_path('app/'.$row->storage_path) && file_exists(storage_path('app/'.$row->storage_path)),404);
        return response()->download(storage_path('app/'.$row->storage_path),$row->original_name);
    }

    private function pdfResponse(string $content,string $filename): Response
    {
        return response($content,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
    }
}
