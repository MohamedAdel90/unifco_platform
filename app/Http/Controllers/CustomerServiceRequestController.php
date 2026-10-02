<?php

namespace App\Http\Controllers;

use App\Models\{Asset,CrmQuotation,Customer,CustomerActivityEvent,CustomerSite,FinancialDocument,MaintenanceAttachment,Payment,ServiceContract,ServiceRequest,WorkOrder};
use App\Services\{CustomerPortalAccessService,CustomerRequestStatusPresenter};
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerServiceRequestController extends Controller
{
    private function portalUser(CustomerPortalAccessService $access): array
    {
        $user=auth()->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403,'Customer portal access is not configured for this user.');
        abort_unless($access->canSection($user,'requests'),403,'Service Requests are not available for your customer portal role.');

        return [$user,Customer::findOrFail($user->customer_id)];
    }

    private function scopedQuery($user, CustomerPortalAccessService $access)
    {
        $query=ServiceRequest::query()->where('tenant_id',$user->tenant_id)->where('customer_id',$user->customer_id);
        $assetIds=$access->accessibleAssetIds($user); $contractIds=$access->accessibleContractIds($user); $siteIds=$access->accessibleSiteIds($user);
        if($assetIds!==null) $query->where(function($q)use($assetIds){$q->whereNull('asset_id')->orWhereIn('asset_id',$assetIds);});
        if($contractIds!==null) $query->where(function($q)use($contractIds){$q->whereNull('service_contract_id')->orWhereIn('service_contract_id',$contractIds);});
        if($siteIds!==null) $query->where(function($q)use($siteIds){$q->whereNull('customer_site_id')->orWhereIn('customer_site_id',$siteIds);});
        return $query;
    }

    public function index(Request $request, CustomerPortalAccessService $access, CustomerRequestStatusPresenter $statusPresenter): View
    {
        [$user,$customer]=$this->portalUser($access); $base=$this->scopedQuery($user,$access);
        // Customer-facing request filters must never expose or accept internal workflow/status codes.
        $filters=['q'=>trim((string)$request->query('q','')),'bucket'=>trim((string)$request->query('bucket','')),'type'=>trim((string)$request->query('type','')),'priority'=>trim((string)$request->query('priority','')),'site_id'=>$request->integer('site_id')?:null];
        $closedStatuses=['COMPLETED','CLOSED','CANCELLED','REJECTED'];
        $summary=['all'=>(clone $base)->count(),'open'=>(clone $base)->whereNotIn('status',$closedStatuses)->count(),'emergency'=>(clone $base)->where('priority','EMERGENCY')->whereNotIn('status',$closedStatuses)->count(),'overdue'=>(clone $base)->whereNotNull('current_stage_due_at')->where('current_stage_due_at','<',now())->whereNotIn('status',$closedStatuses)->count(),'in_progress'=>(clone $base)->where('workflow_stage','IN_PROGRESS')->whereNotIn('status',$closedStatuses)->count(),'awaiting_customer'=>(clone $base)->where('workflow_stage','CUSTOMER_ACCEPTANCE')->whereNotIn('status',$closedStatuses)->count(),'completed'=>(clone $base)->whereIn('status',['COMPLETED','CLOSED'])->count()];
        $query=clone $base;
        if($filters['q']!==''){ $needle=$filters['q']; $query->where(function($q)use($needle){$q->where('request_no','like','%'.$needle.'%')->orWhere('subject','like','%'.$needle.'%')->orWhere('details','like','%'.$needle.'%')->orWhere('service_category','like','%'.$needle.'%')->orWhere('site_city','like','%'.$needle.'%');}); }
        $allowedBuckets=['','all','open','emergency','overdue','in_progress','awaiting_customer','completed']; if(!in_array($filters['bucket'],$allowedBuckets,true)) $filters['bucket']='';
        match($filters['bucket']){'open'=>$query->whereNotIn('status',$closedStatuses),'emergency'=>$query->where('priority','EMERGENCY')->whereNotIn('status',$closedStatuses),'overdue'=>$query->whereNotNull('current_stage_due_at')->where('current_stage_due_at','<',now())->whereNotIn('status',$closedStatuses),'in_progress'=>$query->where('workflow_stage','IN_PROGRESS')->whereNotIn('status',$closedStatuses),'awaiting_customer'=>$query->where('workflow_stage','CUSTOMER_ACCEPTANCE')->whereNotIn('status',$closedStatuses),'completed'=>$query->whereIn('status',['COMPLETED','CLOSED']),default=>null};
        foreach(['request_type'=>'type','priority'=>'priority'] as $column=>$key){if($filters[$key]!=='')$query->where($column,$filters[$key]);}
        if($filters['site_id']) $query->where('customer_site_id',$filters['site_id']);
        $requests=$query->orderByRaw("CASE WHEN current_stage_due_at IS NOT NULL AND current_stage_due_at < ? AND status NOT IN ('COMPLETED','CLOSED','CANCELLED','REJECTED') THEN 0 ELSE 1 END",[now()])->orderByRaw("CASE priority WHEN 'EMERGENCY' THEN 0 WHEN 'HIGH' THEN 1 WHEN 'NORMAL' THEN 2 ELSE 3 END")->orderByRaw('CASE WHEN current_stage_due_at IS NULL THEN 1 ELSE 0 END')->orderBy('current_stage_due_at')->latest('id')->paginate(15)->withQueryString();
        $types=(clone $base)->whereNotNull('request_type')->distinct()->orderBy('request_type')->pluck('request_type'); $priorities=(clone $base)->whereNotNull('priority')->distinct()->orderBy('priority')->pluck('priority');
        $siteIds=(clone $base)->whereNotNull('customer_site_id')->distinct()->pluck('customer_site_id'); $sites=CustomerSite::where('customer_id',$customer->id)->whereIn('id',$siteIds)->orderBy('name')->get();
        $pageSiteIds=$requests->getCollection()->pluck('customer_site_id')->filter()->unique(); $pageAssetIds=$requests->getCollection()->pluck('asset_id')->filter()->unique(); $sitesById=CustomerSite::where('customer_id',$customer->id)->whereIn('id',$pageSiteIds)->get()->keyBy('id'); $assetsById=Asset::where('customer_id',$customer->id)->whereIn('id',$pageAssetIds)->get()->keyBy('id');
        return view('customer.service-requests.index',['customer'=>$customer,'requests'=>$requests,'filters'=>$filters,'types'=>$types,'priorities'=>$priorities,'sites'=>$sites,'sitesById'=>$sitesById,'assetsById'=>$assetsById,'summary'=>$summary,'statusPresenter'=>$statusPresenter,'portalRole'=>$access->role($user),'allowedSections'=>$access->allowedSections($user),'canManageUsers'=>$access->canManageUsers($user),'readOnly'=>$access->isReadOnly($user)]);
    }

    public function show(ServiceRequest $serviceRequest, CustomerPortalAccessService $access, CustomerRequestStatusPresenter $statusPresenter): View
    {
        [$user,$customer]=$this->portalUser($access);
        abort_unless((int)$serviceRequest->tenant_id===(int)$user->tenant_id && (int)$serviceRequest->customer_id===(int)$user->customer_id,404);
        $assetIds=$access->accessibleAssetIds($user); if($serviceRequest->asset_id && $assetIds!==null) abort_unless($assetIds->contains((int)$serviceRequest->asset_id),404);
        $contractIds=$access->accessibleContractIds($user); if($serviceRequest->service_contract_id && $contractIds!==null) abort_unless($contractIds->contains((int)$serviceRequest->service_contract_id),404);
        $siteIds=$access->accessibleSiteIds($user); if($serviceRequest->customer_site_id && $siteIds!==null) abort_unless($siteIds->contains((int)$serviceRequest->customer_site_id),404);

        $asset=$serviceRequest->asset_id?Asset::where('customer_id',$customer->id)->find($serviceRequest->asset_id):null;
        $site=$serviceRequest->customer_site_id?CustomerSite::where('customer_id',$customer->id)->find($serviceRequest->customer_site_id):null;
        $contract=$serviceRequest->service_contract_id?ServiceContract::where('customer_id',$customer->id)->find($serviceRequest->service_contract_id):null;
        $workOrder=$asset && $serviceRequest->work_order_id?WorkOrder::where('tenant_id',$user->tenant_id)->where('asset_id',$asset->id)->find($serviceRequest->work_order_id):null;
        $quotation=$serviceRequest->quotation_id?CrmQuotation::where('tenant_id',$user->tenant_id)->where('customer_id',$customer->id)->find($serviceRequest->quotation_id):null;
        $invoice=FinancialDocument::query()->where('tenant_id',$user->tenant_id)->where('customer_id',$customer->id)->where('document_type','AR_INVOICE')->where(function($q)use($serviceRequest){$q->where('service_request_id',$serviceRequest->id); $legacyId=data_get($serviceRequest->workflow_context,'invoice_id'); if($legacyId)$q->orWhere('id',$legacyId);})->latest('id')->first();
        $payments=$invoice?Payment::where('tenant_id',$user->tenant_id)->where('financial_document_id',$invoice->id)->orderBy('payment_date')->get():collect();
        $customerStatus=$statusPresenter->present($serviceRequest);
        // Intake used to copy workflow metadata into the free-text description.
        // Use the live subtype as the authority and leave the original record untouched.
        $customerDetails=trim((string)preg_replace('/^مسار الطلب:\s*[^\r\n]*(?:\r?\n|$)/mu','',(string)$serviceRequest->details));

        $events=CustomerActivityEvent::query()->where('customer_id',$customer->id)->where('reference_type',ServiceRequest::class)->where('reference_id',$serviceRequest->id)->whereIn('visibility',['BOTH','CUSTOMER'])->orderByDesc('created_at')->limit(100)->get();
        $attachments=collect(); if($workOrder){$attachments=MaintenanceAttachment::where('customer_id',$customer->id)->where('work_order_id',$workOrder->id)->latest()->get();}
        $canAccept=$serviceRequest->workflow_stage==='CUSTOMER_ACCEPTANCE' && $workOrder && $workOrder->status==='COMPLETED' && $access->canAcceptWork($user);
        $canDecideDelivery=$serviceRequest->workflow_stage==='CUSTOMER_DELIVERY';

        return view('customer.service-requests.show',['customer'=>$customer,'serviceRequest'=>$serviceRequest,'customerDetails'=>$customerDetails,'asset'=>$asset,'site'=>$site,'contract'=>$contract,'workOrder'=>$workOrder,'quotation'=>$quotation,'invoice'=>$invoice,'payments'=>$payments,'customerStatus'=>$customerStatus,'events'=>$events,'attachments'=>$attachments,'canAccept'=>$canAccept,'canDecideDelivery'=>$canDecideDelivery,'portalRole'=>$access->role($user),'allowedSections'=>$access->allowedSections($user),'canManageUsers'=>$access->canManageUsers($user),'readOnly'=>$access->isReadOnly($user)]);
    }
}
