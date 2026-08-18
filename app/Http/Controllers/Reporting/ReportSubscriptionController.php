<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\ReportSubscription;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class ReportSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        return view('reporting.subscriptions',['subscriptions'=>ReportSubscription::where('user_id',$request->user()->id)->latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['frequency'=>['required','in:DAILY,WEEKLY,MONTHLY'],'delivery_channel'=>['required','in:IN_APP,EMAIL'],'recipient'=>['nullable','email','max:180']]);
        if($data['delivery_channel']==='EMAIL') abort_unless(!empty($data['recipient']),422,'Email recipient is required.');
        $next=match($data['frequency']){'DAILY'=>now()->addDay(),'WEEKLY'=>now()->addWeek(),default=>now()->addMonth()};
        ReportSubscription::create($data+['tenant_id'=>$request->user()->tenant_id,'user_id'=>$request->user()->id,'report_code'=>'EXECUTIVE_SUMMARY','next_delivery_at'=>$next,'is_active'=>true]);
        return back()->with('status','Scheduled report subscription created.');
    }

    public function destroy(Request $request, ReportSubscription $subscription): RedirectResponse
    {
        abort_unless((int)$subscription->user_id===(int)$request->user()->id,404); $subscription->delete();
        return back()->with('status','Scheduled report subscription removed.');
    }
}
