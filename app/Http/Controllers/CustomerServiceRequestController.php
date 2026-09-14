<?php

namespace App\Http\Controllers;

use App\Models\{Asset,Customer,CustomerActivityEvent,CustomerSite,MaintenanceAttachment,ServiceContract,ServiceRequest,WorkOrder};
use App\Services\CustomerPortalAccessService;
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
        $query=ServiceRequest::query()
            ->where('tenant_id',$user->tenant_id)
            ->where('customer_id',$user->customer_id);

        $assetIds=$access->accessibleAssetIds($user);
        $contractIds=$access->accessibleContractIds($user);
        $siteIds=$access->accessibleSiteIds($user);

        if($assetIds!==null) $query->where(function($q)use($assetIds){$q->whereNull('asset_id')->orWhereIn('asset_id',$assetIds);});
        if($contractIds!==null) $query->where(function($q)use($contractIds){$q->whereNull('service_contract_id')->orWhereIn('service_contract_id',$contractIds);});
        if($siteIds!==null) $query->where(function($q)use($siteIds){$q->whereNull('customer_site_id')->orWhereIn('customer_site_id',$siteIds);});

        return $query;
    }

    public function index(Request $request, CustomerPortalAccessService $access): View
    {
        [$user,$customer]=$this->portalUser($access);
        $base=$this->scopedQuery($user,$access);

        $filters=[
            'q'=>trim((string)$request->query('q','')),
            'type'=>trim((string)$request->query('type','')),
            'priority'=>trim((string)$request->query('priority','')),
            'stage'=>trim((string)$request->query('stage','')),
            'status'=>trim((string)$request->query('status','')),
            'site_id'=>$request->integer('site_id')?:null,
        ];

        $query=clone $base;
        if($filters['q']!==''){
            $needle=$filters['q'];
            $query->where(function($q)use($needle){
                $q->where('request_no','like','%'.$needle.'%')
                    ->orWhere('subject','like','%'.$needle.'%')
                    ->orWhere('details','like','%'.$needle.'%')
                    ->orWhere('service_category','like','%'.$needle.'%')
                    ->orWhere('site_city','like','%'.$needle.'%');
            });
        }
        foreach(['request_type'=>'type','priority'=>'priority','workflow_stage'=>'stage','status'=>'status'] as $column=>$key){
            if($filters[$key]!=='') $query->where($column,$filters[$key]);
        }
        if($filters['site_id']) $query->where('customer_site_id',$filters['site_id']);

        $requests=$query->latest('id')->paginate(15)->withQueryString();
        $types=(clone $base)->whereNotNull('request_type')->distinct()->orderBy('request_type')->pluck('request_type');
        $priorities=(clone $base)->whereNotNull('priority')->distinct()->orderBy('priority')->pluck('priority');
        $stages=(clone $base)->whereNotNull('workflow_stage')->distinct()->orderBy('workflow_stage')->pluck('workflow_stage');
        $statuses=(clone $base)->whereNotNull('status')->distinct()->orderBy('status')->pluck('status');
        $siteIds=(clone $base)->whereNotNull('customer_site_id')->distinct()->pluck('customer_site_id');
        $sites=CustomerSite::where('customer_id',$customer->id)->whereIn('id',$siteIds)->orderBy('name')->get();

        return view('customer.service-requests.index',[
            'customer'=>$customer,'requests'=>$requests,'filters'=>$filters,'types'=>$types,'priorities'=>$priorities,
            'stages'=>$stages,'statuses'=>$statuses,'sites'=>$sites,'portalRole'=>$access->role($user),
            'allowedSections'=>$access->allowedSections($user),'canManageUsers'=>$access->canManageUsers($user),'readOnly'=>$access->isReadOnly($user),
        ]);
    }

    public function show(ServiceRequest $serviceRequest, CustomerPortalAccessService $access): View
    {
        [$user,$customer]=$this->portalUser($access);
        abort_unless((int)$serviceRequest->tenant_id===(int)$user->tenant_id && (int)$serviceRequest->customer_id===(int)$user->customer_id,404);

        if($serviceRequest->asset_id) $access->assertAsset($user,(int)$serviceRequest->asset_id);
        if($serviceRequest->service_contract_id) $access->assertContract($user,(int)$serviceRequest->service_contract_id);
        $siteIds=$access->accessibleSiteIds($user);
        if($serviceRequest->customer_site_id && $siteIds!==null) abort_unless($siteIds->contains((int)$serviceRequest->customer_site_id),404);

        $asset=$serviceRequest->asset_id?Asset::where('customer_id',$customer->id)->find($serviceRequest->asset_id):null;
        $site=$serviceRequest->customer_site_id?CustomerSite::where('customer_id',$customer->id)->find($serviceRequest->customer_site_id):null;
        $contract=$serviceRequest->service_contract_id?ServiceContract::where('customer_id',$customer->id)->find($serviceRequest->service_contract_id):null;
        $workOrder=$serviceRequest->work_order_id?WorkOrder::find($serviceRequest->work_order_id):null;

        $events=CustomerActivityEvent::query()
            ->where('customer_id',$customer->id)
            ->where('reference_type',ServiceRequest::class)
            ->where('reference_id',$serviceRequest->id)
            ->whereIn('visibility',['BOTH','CUSTOMER'])
            ->orderByDesc('created_at')->limit(100)->get();

        $attachments=collect();
        if($workOrder){
            $attachments=MaintenanceAttachment::where('customer_id',$customer->id)
                ->where('work_order_id',$workOrder->id)->latest()->get();
        }

        $canAccept=$serviceRequest->workflow_stage==='CUSTOMER_ACCEPTANCE'
            && $workOrder && $workOrder->status==='COMPLETED'
            && $access->canAcceptWork($user);

        return view('customer.service-requests.show',[
            'customer'=>$customer,'serviceRequest'=>$serviceRequest,'asset'=>$asset,'site'=>$site,'contract'=>$contract,
            'workOrder'=>$workOrder,'events'=>$events,'attachments'=>$attachments,'canAccept'=>$canAccept,
            'portalRole'=>$access->role($user),'allowedSections'=>$access->allowedSections($user),
            'canManageUsers'=>$access->canManageUsers($user),'readOnly'=>$access->isReadOnly($user),
        ]);
    }
}
