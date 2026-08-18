<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\{MaintenanceAttachment,MaintenanceVisitReport,ServiceRequest};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CustomerPortalServiceAdminController extends Controller
{
    public function updateRequest(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $data=$request->validate([
            'status'=>['required','in:OPEN,ACKNOWLEDGED,IN_PROGRESS,RESOLVED,CLOSED'],
        ]);
        $updates=['status'=>$data['status']];
        if ($data['status']==='ACKNOWLEDGED' && !$serviceRequest->responded_at) $updates['responded_at']=now();
        if (in_array($data['status'],['RESOLVED','CLOSED'],true) && !$serviceRequest->resolved_at) $updates['resolved_at']=now();
        $serviceRequest->update($updates);
        return back()->with('status','Service request status updated.');
    }

    public function storeVisit(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'customer_id'=>['required','integer','exists:customers,id'],
            'service_contract_id'=>['nullable','integer','exists:service_contracts,id'],
            'asset_id'=>['required','integer','exists:assets,id'],
            'work_order_id'=>['nullable','integer','exists:work_orders,id'],
            'visit_date'=>['required','date'],'visit_type'=>['required','string','max:30'],
            'findings'=>['nullable','string','max:10000'],'work_performed'=>['nullable','string','max:10000'],
            'recommendations'=>['nullable','string','max:10000'],'technician_name'=>['nullable','string','max:180'],
            'customer_acknowledgement'=>['nullable','string','max:255'],
        ]);
        MaintenanceVisitReport::create($data+[
            'tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,
            'report_no'=>'TVR-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
        ]);
        return back()->with('status','Technical visit report created.');
    }

    public function storeAttachment(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'customer_id'=>['required','integer','exists:customers,id'],'asset_id'=>['required','integer','exists:assets,id'],
            'work_order_id'=>['nullable','integer','exists:work_orders,id'],'visit_report_id'=>['nullable','integer','exists:maintenance_visit_reports,id'],
            'attachment_type'=>['required','in:BEFORE,AFTER,REPORT,OTHER'],'file'=>['required','file','max:10240','mimes:jpg,jpeg,png,pdf'],
        ]);
        $file=$request->file('file');
        $path=$file->store('customer-maintenance/'.Auth::user()->tenant_id,'local');
        MaintenanceAttachment::create([
            'tenant_id'=>Auth::user()->tenant_id,'customer_id'=>$data['customer_id'],'asset_id'=>$data['asset_id'],
            'work_order_id'=>$data['work_order_id']??null,'visit_report_id'=>$data['visit_report_id']??null,'attachment_type'=>$data['attachment_type'],
            'original_name'=>$file->getClientOriginalName(),'storage_path'=>$path,'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize(),
        ]);
        return back()->with('status','Maintenance attachment uploaded.');
    }
}
