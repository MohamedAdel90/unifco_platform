<?php

namespace App\Http\Controllers;

use App\Models\{Asset,Customer,FinancialDocument,ServiceContract,ServiceRequest,WorkOrder};
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerPortalSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user=$request->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);
        $customer=Customer::whereKey($user->customer_id)->where('tenant_id',$user->tenant_id)->firstOrFail();
        $query=trim((string)$request->query('q',''));
        $like='%'.$query.'%';

        $requests=$query===''?collect():ServiceRequest::where('tenant_id',$user->tenant_id)->where('customer_id',$customer->id)->where(function($builder)use($like){$builder->where('request_no','like',$like)->orWhere('subject','like',$like)->orWhere('service_category','like',$like);})->latest()->limit(20)->get();
        $assets=$query===''?collect():Asset::where('tenant_id',$user->tenant_id)->where('customer_id',$customer->id)->where(function($builder)use($like){$builder->where('asset_code','like',$like)->orWhere('name','like',$like)->orWhere('serial_no','like',$like);})->orderBy('asset_code')->limit(20)->get();
        $assetIds=Asset::where('tenant_id',$user->tenant_id)->where('customer_id',$customer->id)->pluck('id');
        $workOrders=$query===''?collect():WorkOrder::with('asset')->whereIn('asset_id',$assetIds)->where(function($builder)use($like){$builder->where('work_order_no','like',$like)->orWhereHas('asset',fn($asset)=>$asset->where('asset_code','like',$like)->orWhere('name','like',$like));})->latest()->limit(20)->get();
        $contracts=$query===''?collect():ServiceContract::where('tenant_id',$user->tenant_id)->where('customer_id',$customer->id)->where(function($builder)use($like){$builder->where('contract_no','like',$like)->orWhere('title','like',$like);})->latest()->limit(20)->get();
        $invoices=$query===''?collect():FinancialDocument::where('tenant_id',$user->tenant_id)->where('customer_id',$customer->id)->where('document_type','AR_INVOICE')->where(function($builder)use($like){$builder->where('document_no','like',$like)->orWhere('status','like',$like);})->latest()->limit(20)->get();

        $total=$requests->count()+$assets->count()+$workOrders->count()+$contracts->count()+$invoices->count();
        return view('customer.search',compact('customer','query','requests','assets','workOrders','contracts','invoices','total'));
    }
}
