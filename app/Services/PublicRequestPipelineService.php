<?php

namespace App\Services;

use App\Models\{Asset,CrmLead,CrmOpportunity,CrmQuotation,Organization,PublicServiceRequest,ServiceContract,ServiceRequest,Tenant,WorkOrder};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PublicRequestPipelineService
{
    public function __construct(private CustomerLifecycleService $customers, private ServiceRequestWorkflowService $workflow) {}

    public function convert(PublicServiceRequest $public): PublicServiceRequest
    {
        return DB::transaction(function () use ($public) {
            $public=PublicServiceRequest::query()->lockForUpdate()->findOrFail($public->id);
            if($public->converted_at) return $public;

            $tenant=Tenant::firstOrCreate(['code'=>'UNIFCO'],['name'=>'UNIFCO','status'=>'ACTIVE']);
            $org=Organization::firstOrCreate(['tenant_id'=>$tenant->id,'code'=>'HQ'],['name'=>'UNIFCO HQ','status'=>'ACTIVE']);
            $customer=$this->customers->resolveForPublicRequest($public,$tenant,$org);
            $links=['tenant_id'=>$tenant->id,'organization_id'=>$org->id];

            $requestType=match(strtoupper((string)$public->request_type)){'QUOTATION'=>'QUOTATION','CONSULTATION'=>'CONSULTATION',default=>'MAINTENANCE'};
            $priority=match($public->urgency){'EMERGENCY'=>'EMERGENCY','URGENT'=>'HIGH','PRIORITY'=>'MEDIUM',default=>'NORMAL'};
            $plannedStart=$public->requested_date?Carbon::parse($public->requested_date->format('Y-m-d').' '.($public->requested_time?:'00:00')):now();

            $asset=$public->asset_id?Asset::where('customer_id',$customer->id)->find($public->asset_id):null;
            if(!$asset && $requestType==='MAINTENANCE') {
                $asset=Asset::firstOrCreate(
                    ['tenant_id'=>$tenant->id,'asset_code'=>'PUBLIC-SERVICE-INBOX'],
                    ['organization_id'=>$org->id,'name'=>$priority==='EMERGENCY'?'Public Emergency Service Intake':'Public Service Intake','status'=>'REGISTERED']
                );
            }

            $contract=ServiceContract::where('customer_id',$customer->id)->where('status','ACTIVE')
                ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
                ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))->orderByDesc('starts_on')->first();
            $eligibility=$contract?'IN_CONTRACT':'CHARGEABLE';

            $meta=[
                'نوع الطلب: '.($public->request_intent?:$public->request_type),'مجموعة الخدمة: '.($public->service_family?:'-'),'الأصل/المعدة: '.($public->asset_type?:'-'),
                'مسؤول الطلب: '.($public->responsible_person?:'-'),'الموقع: '.($public->site_address?:$public->site_city?:'-'),
                'الإحداثيات: '.($public->latitude&&$public->longitude?$public->latitude.', '.$public->longitude:'-'),'الموعد المطلوب: '.$plannedStart->format('Y-m-d H:i'),
            ];

            $serviceRequest=ServiceRequest::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'service_contract_id'=>$contract?->id,'asset_id'=>$asset?->id,
                'request_no'=>'SR-'.$public->reference_no,'request_type'=>$requestType,'company_name'=>$public->company_name,'commercial_registration'=>$public->commercial_registration,
                'email'=>$public->email,'mobile'=>$public->mobile,'service_category'=>$public->service_category,'subject'=>$public->subject,'details'=>$public->details."\n\n".implode("\n",$meta),
                'site_city'=>$public->site_city,'priority'=>$priority,'status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>$eligibility,
                'response_sla_minutes'=>$priority==='EMERGENCY'?10:120,'resolution_sla_minutes'=>$priority==='EMERGENCY'?240:1440,
            ]);
            $links['service_request_id']=$serviceRequest->id;
            $this->customers->record($customer,'SERVICE_REQUEST_CREATED','Service request '.$serviceRequest->request_no.' created',$public->subject,$serviceRequest,['request_type'=>$requestType,'priority'=>$priority,'eligibility'=>$eligibility]);

            if(in_array($requestType,['QUOTATION','CONSULTATION'],true)||($requestType==='MAINTENANCE'&&!$contract&&$priority!=='EMERGENCY')) {
                $lead=CrmLead::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'lead_no'=>'LEAD-'.$public->reference_no,'name'=>$public->responsible_person?:$public->company_name,'company'=>$public->company_name,'email'=>$public->email,'mobile'=>$public->mobile,'commercial_registration'=>$public->commercial_registration,'source'=>'PUBLIC_WEBSITE','status'=>'NEW']);
                $opportunity=CrmOpportunity::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'lead_id'=>$lead->id,'opportunity_no'=>'OPP-'.$public->reference_no,'name'=>$public->subject,'stage'=>'QUALIFICATION','expected_value'=>0,'probability'=>10,'status'=>'OPEN']);
                $links+=['crm_lead_id'=>$lead->id,'crm_opportunity_id'=>$opportunity->id];
                $needsQuotation=$requestType==='QUOTATION'||($requestType==='MAINTENANCE'&&!$contract);
                if($needsQuotation) {
                    $quotation=CrmQuotation::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'opportunity_id'=>$opportunity->id,'customer_id'=>$customer->id,'quotation_no'=>'QT-'.$public->reference_no,'revision_no'=>0,'quotation_date'=>now()->toDateString(),'currency'=>'SAR','amount'=>0,'cost_amount'=>0,'risk_level'=>'NORMAL','status'=>'DRAFT']);
                    $serviceRequest->update(['quotation_id'=>$quotation->id,'workflow_stage'=>'TECHNICAL_REVIEW']);
                    $links+=['crm_quotation_id'=>$quotation->id,'status'=>'CONVERTED_TO_QUOTATION'];
                    $this->customers->record($customer,'QUOTATION_DRAFT_CREATED','Quotation '.$quotation->quotation_no.' created','Commercial preparation started.',$quotation);
                } else $links['status']='CONVERTED_TO_OPPORTUNITY';
            }

            if($requestType==='MAINTENANCE'&&($contract||$priority==='EMERGENCY')) {
                $workOrder=WorkOrder::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'service_contract_id'=>$contract?->id,'work_order_no'=>'WO-'.$public->reference_no,'asset_id'=>$asset->id,'maintenance_type'=>'CORRECTIVE','priority'=>$priority,'status'=>'OPEN','planned_start'=>$plannedStart]);
                $serviceRequest->update(['work_order_id'=>$workOrder->id,'workflow_stage'=>$priority==='EMERGENCY'?'IN_PROGRESS':'PLANNING']);
                $links+=['work_order_id'=>$workOrder->id,'status'=>'CONVERTED_TO_WORK_ORDER'];
                $this->customers->record($customer,'WORK_ORDER_CREATED','Work order '.$workOrder->work_order_no.' created','Maintenance execution record created.',$workOrder);
            }

            $this->workflow->start($serviceRequest->fresh(),['estimated_value'=>0,'margin_pct'=>null,'payment_terms_days'=>0,'risk_level'=>'NORMAL','procurement_required'=>false,'paid'=>$requestType==='QUOTATION']);
            $public->update($links+['converted_at'=>now()]);
            return $public->fresh();
        });
    }
}
