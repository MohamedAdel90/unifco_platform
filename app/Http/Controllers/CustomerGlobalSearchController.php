<?php

namespace App\Http\Controllers;

use App\Models\{Asset,FinancialDocument,ServiceContract,ServiceRequest,WorkOrder};
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerGlobalSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user=$request->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);

        $q=trim((string)$request->query('q',''));
        $results=collect();
        if(mb_strlen($q)>=2){
            $like='%'.$q.'%';
            $customerId=(int)$user->customer_id;

            ServiceRequest::where('customer_id',$customerId)
                ->where(fn($x)=>$x->where('request_no','like',$like)->orWhere('subject','like',$like)->orWhere('details','like',$like))
                ->latest()->limit(8)->get()->each(function($item) use($results){
                    $results->push((object)['type'=>'Service Request','reference'=>$item->request_no,'title'=>$item->subject ?: 'Service Request','status'=>$item->status,'url'=>route('customer.section','requests').'?q='.urlencode($item->request_no)]);
                });

            Asset::where('customer_id',$customerId)
                ->where(fn($x)=>$x->where('asset_code','like',$like)->orWhere('customer_asset_code','like',$like)->orWhere('name','like',$like)->orWhere('serial_no','like',$like))
                ->orderBy('asset_code')->limit(8)->get()->each(function($item) use($results){
                    $results->push((object)['type'=>'Asset','reference'=>$item->asset_code,'title'=>$item->name,'status'=>$item->operational_status ?: $item->status,'url'=>route('customer.asset.show',$item)]);
                });

            WorkOrder::whereHas('asset',fn($x)=>$x->where('customer_id',$customerId))
                ->where(fn($x)=>$x->where('work_order_no','like',$like)->orWhere('execution_notes','like',$like)->orWhere('completion_notes','like',$like))
                ->latest()->limit(8)->get()->each(function($item) use($results){
                    $title=$item->execution_notes ?: $item->completion_notes ?: 'Work Order';
                    $results->push((object)['type'=>'Work Order','reference'=>$item->work_order_no,'title'=>$title,'status'=>$item->status,'url'=>route('customer.work-orders.show',$item)]);
                });

            FinancialDocument::where('customer_id',$customerId)->where('document_type','AR_INVOICE')
                ->where('document_no','like',$like)->latest('document_date')->limit(8)->get()->each(function($item) use($results){
                    $results->push((object)['type'=>'Invoice','reference'=>$item->document_no,'title'=>'Invoice · '.number_format((float)$item->amount,2).' '.$item->currency,'status'=>$item->status,'url'=>route('customer.section','invoices').'?q='.urlencode($item->document_no)]);
                });

            ServiceContract::where('customer_id',$customerId)
                ->where(fn($x)=>$x->where('contract_no','like',$like)->orWhere('title','like',$like))
                ->latest('starts_on')->limit(8)->get()->each(function($item) use($results){
                    $results->push((object)['type'=>'Contract','reference'=>$item->contract_no,'title'=>$item->title,'status'=>$item->status,'url'=>route('customer.section','contracts').'?q='.urlencode($item->contract_no)]);
                });
        }

        return view('customer.search',compact('q','results'));
    }
}
