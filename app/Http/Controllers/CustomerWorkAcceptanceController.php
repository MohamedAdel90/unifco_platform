<?php

namespace App\Http\Controllers;

use App\Models\{Asset,ServiceRequest,WorkOrder};
use App\Services\{CustomerPortalAccessService,ServiceRequestWorkflowService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class CustomerWorkAcceptanceController extends Controller
{
    private function customerUser(): \App\Models\User
    {
        $user=auth()->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);
        return $user;
    }

    public function index(CustomerPortalAccessService $access): View
    {
        $user=$this->customerUser();
        abort_unless($access->canAcceptWork($user),403,'Your portal role cannot accept completed work.');
        $assetIds=$access->accessibleAssetIds($user);
        if($assetIds===null) $assetIds=Asset::where('customer_id',$user->customer_id)->pluck('id');

        return view('customer.work-acceptance',[
            'pending'=>WorkOrder::whereIn('asset_id',$assetIds)->where('status','COMPLETED')->whereNull('customer_accepted_at')->whereNull('customer_rejected_at')->latest('completed_at')->get(),
            'history'=>WorkOrder::whereIn('asset_id',$assetIds)->where(function($q){$q->whereNotNull('customer_accepted_at')->orWhereNotNull('customer_rejected_at');})->latest()->limit(50)->get(),
        ]);
    }

    public function decide(Request $request, WorkOrder $workOrder, CustomerPortalAccessService $access, ServiceRequestWorkflowService $workflow): RedirectResponse
    {
        $user=$this->customerUser();
        abort_unless($access->canAcceptWork($user),403,'Your portal role cannot accept completed work.');
        $access->assertAsset($user,(int)$workOrder->asset_id);
        abort_unless(Asset::whereKey($workOrder->asset_id)->where('customer_id',$user->customer_id)->exists(),403);
        abort_unless($workOrder->status==='COMPLETED',422,'Only completed work can be accepted or rejected.');
        $data=$request->validate(['decision'=>['required','in:ACCEPT,REJECT'],'notes'=>['nullable','string','max:2000']]);

        $serviceRequest=ServiceRequest::query()
            ->where('tenant_id',$user->tenant_id)
            ->where('customer_id',$user->customer_id)
            ->where('work_order_id',$workOrder->id)
            ->latest('id')->first();

        if($data['decision']==='ACCEPT'){
            $workOrder->update([
                'customer_accepted_at'=>now(),'customer_rejected_at'=>null,'customer_acceptance_notes'=>$data['notes']??null,
            ]);
            if($serviceRequest && $serviceRequest->workflow_stage==='CUSTOMER_ACCEPTANCE'){
                $workflow->advance($serviceRequest,'CUSTOMER_ACCEPTANCE',$user->id,$data['notes']??'Customer accepted completed work.');
            }
        }else{
            $workOrder->update([
                'customer_rejected_at'=>now(),'customer_accepted_at'=>null,'customer_acceptance_notes'=>$data['notes']??null,
                'status'=>'IN_PROGRESS',
            ]);
            if($serviceRequest && $serviceRequest->workflow_stage==='CUSTOMER_ACCEPTANCE'){
                $workflow->returnTo($serviceRequest,'EXECUTION',$user->id,$data['notes']??'Customer requested rework.');
            }
        }

        return back()->with('status',$data['decision']==='ACCEPT'?'تم اعتماد الأعمال ونقل الطلب للمرحلة التالية.':'تم طلب إعادة العمل وإرجاع الطلب للتنفيذ.');
    }
}
