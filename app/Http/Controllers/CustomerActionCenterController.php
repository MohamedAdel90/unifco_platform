<?php

namespace App\Http\Controllers;

use App\Models\{Asset,CrmQuotation,Customer,CustomerPortalActionRequest,FinancialDocument,ServiceContract,WorkOrder};
use App\Services\CustomerPortalAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerActionCenterController extends Controller
{
    public function __invoke(Request $request, CustomerPortalAccessService $access): View
    {
        $user=$request->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);

        $customerId=(int)$user->customer_id;
        $role=$access->role($user);
        $customer=Customer::whereKey($customerId)->where('tenant_id',$user->tenant_id)->firstOrFail();
        $assetIds=$access->accessibleAssetIds($user);
        $contractIds=$access->accessibleContractIds($user);

        $quotationQuery=CrmQuotation::where('customer_id',$customerId)->whereIn('status',['SENT','UNDER_REVIEW','REVISION_REQUESTED']);
        $quotations=$quotationQuery->latest('quotation_date')->limit(20)->get();

        $workQuery=WorkOrder::with('asset')->where('status','COMPLETED')->whereNull('customer_accepted_at')->whereNull('customer_rejected_at');
        if($assetIds!==null) $workQuery->whereIn('asset_id',$assetIds);
        else $workQuery->whereIn('asset_id',Asset::where('customer_id',$customerId)->pluck('id'));
        $workOrders=$workQuery->latest('completed_at')->limit(20)->get();

        $invoiceQuery=FinancialDocument::where('customer_id',$customerId)->where('document_type','AR_INVOICE')->where('open_amount','>',0)
            ->whereNotNull('due_date')->where('due_date','<=',now()->addDays(14));
        $invoices=$invoiceQuery->orderBy('due_date')->limit(20)->get();

        $openRenewals=CustomerPortalActionRequest::where('customer_id',$customerId)->where('action_type','CONTRACT_RENEWAL')->where('status','OPEN')->pluck('reference_id');
        $contractQuery=ServiceContract::where('customer_id',$customerId)->whereNotNull('ends_on')->where('ends_on','<=',now()->addDays(60))->where('status','ACTIVE')->whereNotIn('id',$openRenewals);
        if($contractIds!==null) $contractQuery->whereIn('id',$contractIds);
        $contracts=$contractQuery->orderBy('ends_on')->limit(20)->get();

        $submittedActions=CustomerPortalActionRequest::where('customer_id',$customerId)->latest('submitted_at')->limit(20)->get();

        $unread=0;
        if(DB::getSchemaBuilder()->hasTable('customer_messages') && DB::getSchemaBuilder()->hasTable('customer_conversations')){
            $unread=DB::table('customer_messages')->join('customer_conversations','customer_conversations.id','=','customer_messages.conversation_id')
                ->where('customer_conversations.customer_id',$customerId)->where('customer_messages.sender_side','UNIFCO')->whereNull('customer_messages.read_at')->count();
        }

        $total=$quotations->count()+$workOrders->count()+$invoices->count()+$contracts->count()+$unread;

        return view('customer.action-center',compact('user','customer','role','quotations','workOrders','invoices','contracts','submittedActions','unread','total'));
    }
}
