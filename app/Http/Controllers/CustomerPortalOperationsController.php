<?php

namespace App\Http\Controllers;

use App\Models\{Asset,CrmQuotation,Customer,FinancialDocument,MaintenanceVisitReport,ServiceContract,ServiceRequest};
use App\Services\{CustomerLifecycleService,CustomerPdfService,CustomerPortalAccessService,ServiceRequestWorkflowService};
use Illuminate\Http\{RedirectResponse,Request,Response};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerPortalOperationsController extends Controller
{
    private function user()
    {
        $user=auth()->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);
        return $user;
    }

    public function requestService(Request $request, ServiceRequestWorkflowService $workflow, CustomerLifecycleService $lifecycle, CustomerPortalAccessService $access): RedirectResponse
    {
        $user=$this->user(); abort_unless($access->canCreateServiceRequest($user),403,'Your portal role cannot create service requests.');
        $customerId=(int)$user->customer_id;
        $data=$request->validate(['service_contract_id'=>['nullable','integer','exists:service_contracts,id'],'asset_id'=>['nullable','integer','exists:assets,id'],'service_category'=>['required','string','max:120'],'subject'=>['required','string','max:180'],'details'=>['required','string','max:5000'],'site_city'=>['nullable','string','max:120'],'priority'=>['required','in:NORMAL,HIGH,EMERGENCY']]);
        if(!empty($data['asset_id'])){$access->assertAsset($user,(int)$data['asset_id']);abort_unless(Asset::whereKey($data['asset_id'])->where('customer_id',$customerId)->exists(),403);}
        if(!empty($data['service_contract_id'])){$access->assertContract($user,(int)$data['service_contract_id']);abort_unless(ServiceContract::whereKey($data['service_contract_id'])->where('customer_id',$customerId)->exists(),403);}
        $customer=Customer::findOrFail($customerId);
        $serviceRequest=ServiceRequest::create($data+['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$customerId,'request_no'=>'CP-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),'request_type'=>'MAINTENANCE','company_name'=>$customer->name,'commercial_registration'=>$customer->commercial_registration,'email'=>$customer->email,'mobile'=>$customer->phone,'status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>!empty($data['service_contract_id'])?'IN_CONTRACT':'CHARGEABLE','response_sla_minutes'=>$data['priority']==='EMERGENCY'?10:120,'resolution_sla_minutes'=>$data['priority']==='EMERGENCY'?240:1440]);
        $workflow->start($serviceRequest,['procurement_required'=>false,'risk_level'=>'NORMAL','estimated_value'=>0,'payment_terms_days'=>0]);
        $lifecycle->record($customer,'SERVICE_REQUEST_CREATED','Service request '.$serviceRequest->request_no.' created',$serviceRequest->subject,$serviceRequest,['priority'=>$serviceRequest->priority,'eligibility'=>$serviceRequest->eligibility]);
        return redirect()->route('customer.section','requests')->with('status','تم إرسال طلب الخدمة وبدء مسار المراجعة والـSLA.');
    }

    public function decideQuotation(Request $request, CrmQuotation $quotation, CustomerLifecycleService $lifecycle, CustomerPortalAccessService $access): RedirectResponse
    {
        $user=$this->user(); abort_unless($access->canDecideQuotation($user),403,'Your portal role cannot decide quotations.');
        abort_unless((int)$quotation->customer_id===(int)$user->customer_id,403);
        $data=$request->validate(['decision'=>['required','in:APPROVE,REJECT,REVISION'],'notes'=>['nullable','string','max:2000']]);
        $quotation->update(match($data['decision']){'APPROVE'=>['status'=>'CUSTOMER_APPROVED','customer_approved_at'=>now(),'customer_rejected_at'=>null,'customer_decision_notes'=>$data['notes']??null],'REJECT'=>['status'=>'CUSTOMER_REJECTED','customer_rejected_at'=>now(),'customer_approved_at'=>null,'customer_decision_notes'=>$data['notes']??null],default=>['status'=>'REVISION_REQUESTED','customer_approved_at'=>null,'customer_rejected_at'=>null,'customer_decision_notes'=>$data['notes']??null]});
        $customer=Customer::findOrFail($user->customer_id);$lifecycle->record($customer,'QUOTATION_'.$data['decision'],'Quotation '.$quotation->quotation_no.' '.$data['decision'],$data['notes']??null,$quotation);
        return back()->with('status','تم تسجيل قرار عرض السعر وتحديث سجل العميل.');
    }

    public function invoicePdf(FinancialDocument $invoice, CustomerPdfService $pdf, CustomerPortalAccessService $access): Response
    {
        $user=$this->user();abort_unless($access->canSection($user,'invoices'),403);abort_unless((int)$invoice->customer_id===(int)$user->customer_id&&$invoice->document_type==='AR_INVOICE',403);
        return $this->pdfResponse($pdf->make('UNIFCO INVOICE',['Invoice: '.$invoice->document_no,'Date: '.$invoice->document_date?->format('Y-m-d'),'Due: '.$invoice->due_date?->format('Y-m-d'),'Amount: '.$invoice->amount.' '.$invoice->currency,'Open amount: '.$invoice->open_amount,'Status: '.$invoice->status]),'invoice-'.$invoice->document_no.'.pdf');
    }

    public function contractPdf(ServiceContract $contract, CustomerPdfService $pdf, CustomerPortalAccessService $access): Response
    {
        $user=$this->user();abort_unless($access->canSection($user,'contracts'),403);$access->assertContract($user,$contract->id);abort_unless((int)$contract->customer_id===(int)$user->customer_id,403);
        return $this->pdfResponse($pdf->make('UNIFCO SERVICE CONTRACT',['Contract: '.$contract->contract_no,'Title: '.$contract->title,'Starts: '.$contract->starts_on?->format('Y-m-d'),'Ends: '.$contract->ends_on?->format('Y-m-d'),'Value: '.$contract->contract_value.' '.$contract->currency,'Billing: '.$contract->billing_cycle,'Status: '.$contract->status,'Scope: '.($contract->scope??''),'SLA: '.($contract->sla_summary??'')]),'contract-'.$contract->contract_no.'.pdf');
    }

    public function visitPdf(MaintenanceVisitReport $report, CustomerPdfService $pdf, CustomerPortalAccessService $access): Response
    {
        $user=$this->user();abort_unless($access->canSection($user,'reports'),403);$access->assertAsset($user,(int)$report->asset_id);abort_unless((int)$report->customer_id===(int)$user->customer_id,403);
        return $this->pdfResponse($pdf->make('UNIFCO TECHNICAL VISIT REPORT',['Report: '.$report->report_no,'Visit date: '.$report->visit_date?->format('Y-m-d'),'Type: '.$report->visit_type,'Technician: '.$report->technician_name,'Findings: '.$report->findings,'Work performed: '.$report->work_performed,'Recommendations: '.$report->recommendations]),'technical-'.$report->report_no.'.pdf');
    }

    public function attachment(int $id, CustomerPortalAccessService $access)
    {
        $user=$this->user();abort_unless($access->canSection($user,'documents'),403);$row=DB::table('maintenance_attachments')->where('id',$id)->where('customer_id',$user->customer_id)->first();abort_unless($row,404);$access->assertAsset($user,(int)$row->asset_id);abort_unless(file_exists(storage_path('app/'.$row->storage_path)),404);return response()->download(storage_path('app/'.$row->storage_path),$row->original_name);
    }

    private function pdfResponse(string $content,string $filename): Response{return response($content,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);}
}
