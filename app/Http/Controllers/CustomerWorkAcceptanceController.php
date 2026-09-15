<?php

namespace App\Http\Controllers;

use App\Models\{Asset,Customer,CustomerActivityEvent,ServiceRequest,WorkOrder};
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
        abort_unless($access->canAcceptWork($user),403,'This customer account cannot accept completed work.');
        $assetIds=$access->accessibleAssetIds($user);
        if($assetIds===null) $assetIds=Asset::where('customer_id',$user->customer_id)->pluck('id');

        return view('customer.work-acceptance',[
            'customer'=>Customer::whereKey($user->customer_id)->where('tenant_id',$user->tenant_id)->firstOrFail(),
            'pending'=>WorkOrder::whereIn('asset_id',$assetIds)->where('status','COMPLETED')->whereNull('customer_accepted_at')->whereNull('customer_rejected_at')->latest('completed_at')->get(),
            'history'=>WorkOrder::whereIn('asset_id',$assetIds)->where(function($q){$q->whereNotNull('customer_accepted_at')->orWhereNotNull('customer_rejected_at');})->latest()->limit(50)->get(),
            'satisfactionRequests'=>ServiceRequest::where('tenant_id',$user->tenant_id)->where('customer_id',$user->customer_id)->where('workflow_stage','CSAT')->latest('id')->get(),
        ]);
    }

    public function decide(Request $request, WorkOrder $workOrder, CustomerPortalAccessService $access, ServiceRequestWorkflowService $workflow): RedirectResponse
    {
        $user=$this->customerUser();
        abort_unless($access->canAcceptWork($user),403,'This customer account cannot accept completed work.');
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

    public function satisfaction(Request $request, ServiceRequest $serviceRequest, ServiceRequestWorkflowService $workflow): RedirectResponse
    {
        $user=$this->customerUser();
        abort_unless((int)$serviceRequest->tenant_id===(int)$user->tenant_id && (int)$serviceRequest->customer_id===(int)$user->customer_id,404);
        abort_unless($serviceRequest->workflow_stage==='CSAT',422,'This request is not waiting for customer satisfaction.');
        abort_if(CustomerActivityEvent::where('customer_id',$user->customer_id)->where('event_type','CUSTOMER_SATISFACTION')->where('reference_type',ServiceRequest::class)->where('reference_id',$serviceRequest->id)->exists(),422,'Customer satisfaction was already submitted.');

        $data=$request->validate([
            'rating'=>['required','integer','between:1,5'],
            'nps'=>['nullable','integer','between:0,10'],
            'comment'=>['nullable','string','max:2000'],
        ]);

        CustomerActivityEvent::create([
            'tenant_id'=>$serviceRequest->tenant_id,
            'organization_id'=>$serviceRequest->organization_id,
            'customer_id'=>$serviceRequest->customer_id,
            'event_type'=>'CUSTOMER_SATISFACTION',
            'reference_type'=>ServiceRequest::class,
            'reference_id'=>$serviceRequest->id,
            'title'=>'Customer satisfaction submitted',
            'description'=>$data['comment']??null,
            'visibility'=>'BOTH',
            'metadata'=>['rating'=>$data['rating'],'nps'=>$data['nps']??null,'submitted_by'=>$user->id,'submitted_at'=>now()->toIso8601String()],
        ]);

        $workflow->advance($serviceRequest,'CSAT',$user->id,$data['comment']??'Customer satisfaction submitted.');
        $context=(array)($serviceRequest->fresh()->workflow_context??[]);
        $context['fully_closed_at']=now()->toIso8601String();
        $context['csat_rating']=$data['rating'];
        $context['csat_nps']=$data['nps']??null;
        $serviceRequest->update(['status'=>'CLOSED','workflow_context'=>$context]);

        return back()->with('status','شكراً لك. تم تسجيل تقييمك وإغلاق الطلب بالكامل.');
    }
}
