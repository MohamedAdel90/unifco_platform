<?php

namespace App\Http\Middleware;

use App\Models\{Asset,CrmQuotation,FinancialDocument,ServiceContract,WorkOrder};
use App\Services\CustomerPortalAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
use Symfony\Component\HttpFoundation\Response;

class CustomerPortalDashboardPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response=$next($request);
        $user=$request->user();
        if(!$user || $user->role!=='CUSTOMER' || !$user->customer_id || !$request->routeIs('customer.portal','customer.section') || !method_exists($response,'getContent') || !method_exists($response,'setContent')) return $response;

        $html=(string)$response->getContent();
        if($html==='') return $response;

        $actionLink='<a href="'.e(route('customer.actions')).'"><span class="ico">⚑</span><span>Action Required</span></a>';
        $inboxNeedle='<a href="'.e(route('customer.inbox')).'">';
        if(str_contains($html,$inboxNeedle) && !str_contains($html,'href="'.e(route('customer.actions')).'"')) $html=str_replace($inboxNeedle,$actionLink.$inboxNeedle,$html);

        if($request->routeIs('customer.portal')){
            $access=app(CustomerPortalAccessService::class);
            $customerId=(int)$user->customer_id;
            $role=$access->role($user);
            $assetIds=$access->accessibleAssetIds($user);
            if($assetIds===null) $assetIds=Asset::where('customer_id',$customerId)->pluck('id');
            $contractIds=$access->accessibleContractIds($user);

            $count=0;
            if($access->canDecideQuotation($user)) $count+=CrmQuotation::where('customer_id',$customerId)->whereIn('status',['SENT','UNDER_REVIEW','REVISION_REQUESTED'])->count();
            if($access->canAcceptWork($user)) $count+=WorkOrder::whereIn('asset_id',$assetIds)->where('status','COMPLETED')->whereNull('customer_accepted_at')->whereNull('customer_rejected_at')->count();
            if(in_array($role,['CUSTOMER_ADMIN','FINANCE'],true)){
                $count+=FinancialDocument::where('customer_id',$customerId)->where('document_type','AR_INVOICE')->where('open_amount','>',0)->whereNotNull('due_date')->where('due_date','<=',now()->addDays(14))->count();
                $contracts=ServiceContract::where('customer_id',$customerId)->where('status','ACTIVE')->whereNotNull('ends_on')->where('ends_on','<=',now()->addDays(60));
                if($contractIds!==null) $contracts->whereIn('id',$contractIds);
                $count+=$contracts->count();
            }
            if(Schema::hasTable('customer_messages') && Schema::hasTable('customer_conversations')){
                $count+=DB::table('customer_messages')->join('customer_conversations','customer_conversations.id','=','customer_messages.conversation_id')
                    ->where('customer_conversations.customer_id',$customerId)->where('customer_messages.sender_side','UNIFCO')->whereNull('customer_messages.read_at')->count();
            }

            $panel='<a href="'.e(route('customer.actions')).'" class="card" style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px;margin-bottom:12px;border-left:4px solid #e20b24"><div><div class="title" style="font-size:13px">Action Required From You</div><div class="sub">Approvals, work acceptance, invoices, renewals and customer follow-up.</div></div><span class="pill red">'.$count.' OPEN</span></a>';
            $needle='<section class="stats">';
            $position=strpos($html,$needle);
            if($position!==false) $html=substr($html,0,$position).$panel.substr($html,$position);
        }

        $response->setContent($html);
        return $response;
    }
}
