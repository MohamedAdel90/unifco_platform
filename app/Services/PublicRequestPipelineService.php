<?php

namespace App\Services;

use App\Models\{Asset,CrmOpportunity,CrmQuotation,Customer,CustomerSite,Organization,PublicServiceRequest,ServiceContract,ServiceRequest,Tenant,WorkOrder};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{DB,Log};
use Throwable;

class PublicRequestPipelineService
{
    public function __construct(
        private CustomerLifecycleService $customers,
        private ServiceRequestWorkflowService $workflow,
        private CustomerAcquisitionService $acquisition,
    ) {}

    public function convert(PublicServiceRequest $public): PublicServiceRequest
    {
        $public->increment('conversion_attempts');
        $public->forceFill(['last_conversion_attempt_at'=>now()])->save();

        if(!$public->converted_at) $public=DB::transaction(function () use ($public) {
            $public=PublicServiceRequest::query()->lockForUpdate()->findOrFail($public->id);
            if($public->converted_at) return $public;

            $tenant=Tenant::firstOrCreate(['code'=>'UNIFCO'],['name'=>'UNIFCO','status'=>'ACTIVE']);
            $org=Organization::firstOrCreate(['tenant_id'=>$tenant->id,'code'=>'HQ'],['name'=>'UNIFCO HQ','status'=>'ACTIVE']);

            $acquisition=$this->acquisition->capture((int)$tenant->id,(int)$org->id,null,[
                'name'=>$public->responsible_person ?: $public->company_name,
                'company'=>$public->company_name,
                'email'=>$public->email,
                'mobile'=>$public->mobile,
                'commercial_registration'=>$public->commercial_registration,
                'source_channel'=>'WEBSITE',
                'source_detail'=>'PUBLIC_SERVICE_REQUEST',
                'service_interest'=>$public->service_category ?: $public->request_type,
                'city'=>$public->site_city,
                'inquiry_notes'=>$public->subject,
            ]);

            $customer=$this->customers->resolveForPublicRequest($public,$tenant,$org);
            $lead=$acquisition['type']==='LEAD' ? $acquisition['lead'] : null;
            if($lead && !$lead->converted_customer_id) $lead=$this->acquisition->linkSystemCustomer($lead,$customer);

            $links=['tenant_id'=>$tenant->id,'organization_id'=>$org->id];
            if($lead) $links['crm_lead_id']=$lead->id;

            $intent=strtoupper((string)($public->request_intent ?: $public->request_type));
            $requestType=match($intent){'QUOTATION'=>'QUOTATION','CONSULTATION'=>'CONSULTATION',default=>'MAINTENANCE'};
            $requestSubtype=strtoupper((string)($public->request_subtype ?: match($requestType){
                'QUOTATION'=>'SPARE_PARTS_QUOTE',
                'CONSULTATION'=>'TECHNICAL_CONSULTATION',
                default=>(strtoupper((string)$public->urgency)==='EMERGENCY'?'URGENT_MAINTENANCE':'ROUTINE_MAINTENANCE'),
            }));
            $priority=match($public->urgency){'EMERGENCY'=>'EMERGENCY','URGENT'=>'HIGH','PRIORITY'=>'MEDIUM',default=>'NORMAL'};
            $plannedStart=$public->requested_date?Carbon::parse($public->requested_date->format('Y-m-d').' '.($public->requested_time?:'00:00')):now();

            $contract=ServiceContract::where('customer_id',$customer->id)->where('status','ACTIVE')
                ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
                ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))->orderByDesc('starts_on')->first();
            $eligibility=$contract?'IN_CONTRACT':'CHARGEABLE';
            $site=CustomerSite::where('customer_id',$customer->id)
                ->where(function($q)use($public){$q->where('name',$public->site_name)->orWhere('city',$public->site_city);})
                ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END',[$public->site_name])
                ->first();

            $asset=$public->asset_id?Asset::where('customer_id',$customer->id)->find($public->asset_id):null;
            if(!$asset && $requestType==='MAINTENANCE') {
                $asset=Asset::firstOrCreate(
                    ['tenant_id'=>$tenant->id,'asset_code'=>'INTAKE-'.$public->reference_no],
                    [
                        'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site?->id,
                        'name'=>$public->asset_type?:($priority==='EMERGENCY'?'Emergency intake asset':'Service intake asset'),
                        'asset_category'=>$public->asset_type?:'GENERAL','manufacturer'=>$public->equipment_brand,'model_no'=>$public->equipment_model,
                        'contract_reference'=>$contract?->contract_no,'status'=>'REGISTERED','verification_status'=>'DRAFT',
                    ]
                );
                $public->update(['asset_id'=>$asset->id]);
            }

            $meta=[
                'نوع الطلب: '.($public->request_intent?:$public->request_type),'مسار الطلب: '.$requestSubtype,'مجموعة الخدمة: '.($public->service_family?:'-'),'الأصل/المعدة: '.($public->asset_type?:'-'),
                'مسؤول الطلب: '.($public->responsible_person?:'-'),'الموقع: '.($public->site_address?:$public->site_city?:'-'),
                'الإحداثيات: '.($public->latitude&&$public->longitude?$public->latitude.', '.$public->longitude:'-'),'الموعد المطلوب: '.$plannedStart->format('Y-m-d H:i'),
            ];

            $serviceRequest=ServiceRequest::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site?->id,'service_contract_id'=>$contract?->id,'asset_id'=>$asset?->id,
                'request_no'=>'SR-'.$public->reference_no,'request_type'=>$requestType,'request_subtype'=>$requestSubtype,'company_name'=>$public->company_name,'commercial_registration'=>$public->commercial_registration,
                'email'=>$public->email,'mobile'=>$public->mobile,'service_category'=>$public->service_category,'subject'=>$public->subject,'details'=>$public->details."\n\n".implode("\n",$meta),
                'site_city'=>$public->site_city,'priority'=>$priority,'status'=>'OPEN','workflow_stage'=>'NEW','approval_state'=>'PENDING','eligibility'=>$eligibility,
                'response_sla_minutes'=>$priority==='EMERGENCY'?10:120,'resolution_sla_minutes'=>$priority==='EMERGENCY'?240:1440,
            ]);
            $links['service_request_id']=$serviceRequest->id;
            $this->customers->record($customer,'SERVICE_REQUEST_CREATED','Service request '.$serviceRequest->request_no.' created',$public->subject,$serviceRequest,['request_type'=>$requestType,'request_subtype'=>$requestSubtype,'priority'=>$priority,'eligibility'=>$eligibility]);

            if(in_array($requestType,['QUOTATION','CONSULTATION'],true)||($requestType==='MAINTENANCE'&&!$contract&&$priority!=='EMERGENCY')) {
                $opportunity=CrmOpportunity::create([
                    'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'lead_id'=>$lead?->id,'customer_id'=>$customer->id,
                    'opportunity_no'=>'OPP-'.$public->reference_no,'name'=>$public->subject,'stage'=>'QUALIFICATION','expected_value'=>0,'probability'=>10,'status'=>'OPEN'
                ]);
                $links['crm_opportunity_id']=$opportunity->id;
                $needsQuotation=$requestType==='QUOTATION'||($requestType==='MAINTENANCE'&&!$contract);
                if($needsQuotation) {
                    $quotation=CrmQuotation::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'opportunity_id'=>$opportunity->id,'customer_id'=>$customer->id,'quotation_no'=>'QT-'.$public->reference_no,'revision_no'=>0,'quotation_date'=>now()->toDateString(),'currency'=>'SAR','amount'=>0,'cost_amount'=>0,'risk_level'=>'NORMAL','status'=>'DRAFT']);
                    $serviceRequest->update(['quotation_id'=>$quotation->id]);
                    $links+=['crm_quotation_id'=>$quotation->id,'status'=>'CONVERTED_TO_QUOTATION'];
                    $this->customers->record($customer,'QUOTATION_DRAFT_CREATED','Quotation '.$quotation->quotation_no.' created','Commercial preparation started.',$quotation);
                } else $links['status']='CONVERTED_TO_OPPORTUNITY';
            }

            if($requestType==='MAINTENANCE') {
                $workOrder=WorkOrder::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'service_contract_id'=>$contract?->id,'work_order_no'=>'WO-'.$public->reference_no,'asset_id'=>$asset->id,'maintenance_type'=>'CORRECTIVE','priority'=>$priority,'status'=>'OPEN','planned_start'=>$plannedStart]);
                $serviceRequest->update(['work_order_id'=>$workOrder->id]);
                $links+=['work_order_id'=>$workOrder->id,'status'=>'CONVERTED_TO_WORK_ORDER'];
                $this->customers->record($customer,'WORK_ORDER_CREATED','Work order '.$workOrder->work_order_no.' created','Maintenance execution record created.',$workOrder);
            }

            $public->update($links+['converted_at'=>now(),'conversion_error'=>null]);
            return $public->fresh();
        });

        $public=PublicServiceRequest::query()->useWritePdo()->findOrFail($public->id);
        $serviceRequest=$public->service_request_id?ServiceRequest::find($public->service_request_id):null;
        $convertedStatus=$public->work_order_id?'CONVERTED_TO_WORK_ORDER':($public->crm_quotation_id?'CONVERTED_TO_QUOTATION':($public->crm_opportunity_id?'CONVERTED_TO_OPPORTUNITY':'CONVERTED'));
        if(!$serviceRequest || $serviceRequest->workflow_started_at){
            if($public->status==='WORKFLOW_PENDING') $public->update(['status'=>$convertedStatus,'conversion_error'=>null]);
            return $public->fresh();
        }

        $workflowContext=[
                'estimated_value'=>0,
                'margin_pct'=>null,
                'payment_terms_days'=>0,
                'risk_level'=>'NORMAL',
                'procurement_required'=>in_array($serviceRequest->request_subtype,['SPARE_PARTS_QUOTE','SPARE_PARTS'],true),
                'quality_required'=>false,
                'hse_required'=>false,
                'has_cost'=>$serviceRequest->eligibility==='CHARGEABLE',
                'chargeable'=>$serviceRequest->eligibility==='CHARGEABLE',
                'administrative_approval_required'=>true,
            ];
        try {
            $steps=$this->workflow->start($serviceRequest->fresh(),$workflowContext);
            $serviceRequest=$serviceRequest->fresh();
            $customer=Customer::find($serviceRequest->customer_id);
            if($customer) {
            $this->customers->record($customer,'SERVICE_REQUEST_WORKFLOW_STARTED','Request workflow started',$serviceRequest->workflow_key,$serviceRequest,[
                'workflow_key'=>$serviceRequest->workflow_key,
                'current_stage'=>$serviceRequest->workflow_stage,
                'assigned_department'=>$serviceRequest->assigned_department,
                'steps'=>$steps->count(),
            ]);
            }
            $public->update(['status'=>$convertedStatus,'conversion_error'=>null]);
        } catch (Throwable $exception) {
            $public->update(['status'=>'WORKFLOW_PENDING','conversion_error'=>mb_substr($exception->getMessage(),0,4000)]);
            Log::error('Public request workflow initialization is pending.',['public_service_request_id'=>$public->id,'reference_no'=>$public->reference_no,'exception'=>$exception]);
        }

        return $public->fresh();
    }
}
