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
        $isCustomerWorkspace=$request->is('customer') || $request->is('customer/*');
        if(!$user || $user->role!=='CUSTOMER' || !$user->customer_id || !$isCustomerWorkspace || !method_exists($response,'getContent') || !method_exists($response,'setContent')) return $response;

        $html=(string)$response->getContent();
        if($html==='') return $response;

        $html=str_replace(
            ['CUSTOMER ADMIN · READ ONLY','CUSTOMER ADMIN','Scope-aware customer workspace','Authorized sites','Visible assets'],
            ['FULL CUSTOMER ACCESS','FULL CUSTOMER ACCESS','Unified technical · operational · commercial · financial workspace','Customer sites','Customer assets'],
            $html
        );

        if(str_contains($html,'<div class="side-search">')){
            $search='<form class="side-search" method="GET" action="'.e(route('customer.search')).'" style="padding:0;overflow:hidden"><span style="padding-left:9px">⌕</span><input name="q" aria-label="Search Customer 360" placeholder="Find request, asset or invoice" style="min-width:0;width:100%;height:33px;border:0;background:transparent;color:#fff;outline:0;font-size:9px;padding:0 8px"></form>';
            $html=preg_replace('/<div class="side-search">.*?<\/div>/s',$search,$html,1) ?? $html;
        }

        $actionLink='<a href="'.e(route('customer.actions')).'"><span class="ico">⚑</span><span>Action Required</span></a>';
        $inboxNeedle='<a href="'.e(route('customer.inbox')).'">';
        if(str_contains($html,$inboxNeedle) && !str_contains($html,'href="'.e(route('customer.actions')).'"')) $html=str_replace($inboxNeedle,$actionLink.$inboxNeedle,$html);

        if($request->is('customer')){
            $access=app(CustomerPortalAccessService::class);
            $customerId=(int)$user->customer_id;
            $assetIds=$access->accessibleAssetIds($user);
            if($assetIds===null) $assetIds=Asset::where('customer_id',$customerId)->pluck('id');
            $contractIds=$access->accessibleContractIds($user);

            $count=0;
            if($access->canDecideQuotation($user)) $count+=CrmQuotation::where('customer_id',$customerId)->whereIn('status',['SENT','UNDER_REVIEW','REVISION_REQUESTED'])->count();
            if($access->canAcceptWork($user)) $count+=WorkOrder::whereIn('asset_id',$assetIds)->where('status','COMPLETED')->whereNull('customer_accepted_at')->whereNull('customer_rejected_at')->count();
            $count+=FinancialDocument::where('customer_id',$customerId)->where('document_type','AR_INVOICE')->where('open_amount','>',0)->whereNotNull('due_date')->where('due_date','<=',now()->addDays(14))->count();
            $contracts=ServiceContract::where('customer_id',$customerId)->where('status','ACTIVE')->whereNotNull('ends_on')->where('ends_on','<=',now()->addDays(60));
            if($contractIds!==null) $contracts->whereIn('id',$contractIds);
            $count+=$contracts->count();

            if(Schema::hasTable('customer_messages') && Schema::hasTable('customer_conversations')){
                $count+=DB::table('customer_messages')->join('customer_conversations','customer_conversations.id','=','customer_messages.conversation_id')
                    ->where('customer_conversations.customer_id',$customerId)->where('customer_messages.sender_side','UNIFCO')->whereNull('customer_messages.read_at')->count();
            }

            if(!str_contains($html,'data-action-center-panel')){
                $panel='<a data-action-center-panel href="'.e(route('customer.actions')).'" class="card" style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px;margin-bottom:12px;border-left:4px solid #e20b24"><div><div class="title" style="font-size:13px">Action Required From You</div><div class="sub">Approvals, work acceptance, invoices, renewals and customer follow-up.</div></div><span class="pill red">'.$count.' OPEN</span></a>';
                $needle='<section class="stats">';
                $position=strpos($html,$needle);
                if($position!==false) $html=substr($html,0,$position).$panel.substr($html,$position);
            }

            if(!str_contains($html,'data-unified-customer-access')){
                $banner='<div data-unified-customer-access class="role-note" style="display:flex;align-items:center;justify-content:space-between;gap:12px;background:#eef5ff;border-color:#cfe0f4;color:#173d6b"><div><strong style="font-size:10px">Unified Customer 360 access</strong><div style="font-size:8px;margin-top:3px;color:#58708d">This single customer login covers technical, operational, commercial and financial information for the full customer account.</div></div><span class="pill green" style="white-space:nowrap">FULL CUSTOMER ACCESS</span></div>';
                $needle='<div class="page-head">';
                $position=strpos($html,$needle);
                if($position!==false) $html=substr($html,0,$position).$banner.substr($html,$position);
            }

            $oldBase=e(route('customer.section','work-orders'));
            $html=str_replace(
                [
                    'href="'.$oldBase.'#request-service"',
                    'href="'.$oldBase.'?priority=EMERGENCY#request-service"',
                    'href="'.$oldBase.'?service_category=Quotation#request-service"',
                    'href="'.$oldBase.'?service_category=Spare%20Parts#request-service"',
                ],
                [
                    'href="'.e(route('public.request-service')).'"',
                    'href="'.e(route('public.emergency')).'"',
                    'href="'.e(route('public.quote')).'"',
                    'href="'.e(route('public.request-service',['quotation'=>1,'service_category'=>'Spare Parts'])).'"',
                ],
                $html
            );
        }

        $response->setContent($html);
        return $response;
    }
}
