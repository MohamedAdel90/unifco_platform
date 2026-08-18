<?php

namespace App\Http\Controllers;

use App\Models\{Asset,WorkOrder};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class CustomerWorkAcceptanceController extends Controller
{
    private function customerId(): int
    {
        $user=auth()->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);
        return (int)$user->customer_id;
    }

    public function index(): View
    {
        $customerId=$this->customerId();
        $assetIds=Asset::where('customer_id',$customerId)->pluck('id');
        return view('customer.work-acceptance',[
            'pending'=>WorkOrder::whereIn('asset_id',$assetIds)->where('status','COMPLETED')->whereNull('customer_accepted_at')->whereNull('customer_rejected_at')->latest('completed_at')->get(),
            'history'=>WorkOrder::whereIn('asset_id',$assetIds)->where(function($q){$q->whereNotNull('customer_accepted_at')->orWhereNotNull('customer_rejected_at');})->latest()->limit(50)->get(),
        ]);
    }

    public function decide(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $customerId=$this->customerId();
        abort_unless(Asset::whereKey($workOrder->asset_id)->where('customer_id',$customerId)->exists(),403);
        abort_unless($workOrder->status==='COMPLETED',422,'Only completed work can be accepted or rejected.');
        $data=$request->validate(['decision'=>['required','in:ACCEPT,REJECT'],'notes'=>['nullable','string','max:2000']]);
        $workOrder->update($data['decision']==='ACCEPT' ? [
            'customer_accepted_at'=>now(),'customer_rejected_at'=>null,'customer_acceptance_notes'=>$data['notes']??null,
        ] : [
            'customer_rejected_at'=>now(),'customer_accepted_at'=>null,'customer_acceptance_notes'=>$data['notes']??null,
        ]);
        return back()->with('status','تم تسجيل اعتماد العميل لأمر العمل.');
    }
}
