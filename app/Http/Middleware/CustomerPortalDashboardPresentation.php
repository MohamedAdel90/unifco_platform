<?php

namespace App\Http\Middleware;

use App\Models\{Asset,CrmQuotation,CustomerActivityEvent,FinancialDocument,ServiceContract,ServiceRequest,WorkOrder};
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

            // Explain why every Priority Today item is surfaced without exposing internal notes.
            $priorityOrders=WorkOrder::with('asset.site')->whereIn('asset_id',$assetIds)
                ->whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])
                ->where(function($query){
                    $query->whereIn('priority',['HIGH','URGENT','EMERGENCY','CRITICAL'])
                        ->orWhere('planned_start','<',now());
                })
                ->orderByRaw('CASE WHEN planned_start IS NULL THEN 1 ELSE 0 END')
                ->orderBy('planned_start')->limit(5)->get();
            foreach($priorityOrders as $workOrder){
                $status=strtoupper((string)$workOrder->status);
                $priority=strtoupper((string)$workOrder->priority);
                if($workOrder->planned_start && $workOrder->planned_start->isPast()){
                    $reason=in_array($status,['IN_PROGRESS','STARTED'],true)
                        ? 'In progress after the planned start — follow-up is required.'
                        : 'The planned start has passed while this work order is still open.';
                }elseif(in_array($priority,['EMERGENCY','CRITICAL','URGENT','HIGH'],true)){
                    $reason='High-priority work order requiring operational follow-up.';
                }else{
                    $reason='Operational follow-up is required.';
                }
                $number=e((string)$workOrder->work_order_no);
                $pattern='/(<div class="row-title">'.preg_quote($number,'/').'.*?<\/div><div class="row-sub">.*?<\/div>)/s';
                $replacement='$1<div class="row-reason">Reason: '.e($reason).'</div>';
                $html=preg_replace($pattern,$replacement,$html,1) ?? $html;
            }

            // Build one customer-visible 360 timeline from operational, commercial and financial activity.
            if(!str_contains($html,'data-v22-timeline')){
                $events=collect();
                if(Schema::hasTable('customer_activity_events')){
                    CustomerActivityEvent::where('customer_id',$customerId)->whereIn('visibility',['BOTH','CUSTOMER'])->latest()->limit(8)->get()->each(function($event) use($events){
                        $events->push(['at'=>$event->created_at,'title'=>$event->title,'meta'=>str_replace('_',' ',$event->event_type)]);
                    });
                }
                ServiceRequest::where('customer_id',$customerId)->latest()->limit(5)->get()->each(function($item) use($events){
                    $events->push(['at'=>$item->updated_at ?: $item->created_at,'title'=>'Service request '.($item->request_no ?: '#'.$item->id),'meta'=>'Service request · '.str_replace('_',' ',(string)($item->workflow_stage ?: $item->status))]);
                });
                WorkOrder::whereIn('asset_id',$assetIds)->latest()->limit(5)->get()->each(function($item) use($events){
                    $events->push(['at'=>$item->updated_at ?: $item->created_at,'title'=>'Work order '.($item->work_order_no ?: '#'.$item->id),'meta'=>'Work order · '.str_replace('_',' ',(string)$item->status)]);
                });
                CrmQuotation::where('customer_id',$customerId)->latest()->limit(4)->get()->each(function($item) use($events){
                    $events->push(['at'=>$item->updated_at ?: $item->created_at,'title'=>'Quotation '.($item->quotation_no ?: '#'.$item->id),'meta'=>'Quotation · '.str_replace('_',' ',(string)$item->status)]);
                });
                $contractQuery=ServiceContract::where('customer_id',$customerId);
                if($contractIds!==null) $contractQuery->whereIn('id',$contractIds);
                $contractQuery->latest()->limit(3)->get()->each(function($item) use($events){
                    $events->push(['at'=>$item->updated_at ?: $item->created_at,'title'=>'Contract '.($item->contract_no ?: '#'.$item->id),'meta'=>'Contract · '.str_replace('_',' ',(string)$item->status)]);
                });
                FinancialDocument::where('customer_id',$customerId)->where('document_type','AR_INVOICE')->latest()->limit(3)->get()->each(function($item) use($events){
                    $events->push(['at'=>$item->updated_at ?: $item->created_at,'title'=>'Invoice '.($item->document_no ?: '#'.$item->id),'meta'=>'Invoice · customer financial activity']);
                });

                $events=$events->filter(fn($event)=>$event['at'])->sortByDesc(fn($event)=>$event['at']->timestamp)->unique(fn($event)=>$event['title'].'|'.$event['meta'])->take(8)->values();
                $rows='';
                foreach($events as $event){
                    $rows.='<div class="list-row"><i class="indicator"></i><div><div class="row-title">'.e($event['title']).'</div><div class="row-sub">'.e($event['meta']).' · '.e($event['at']->format('d M Y, H:i')).'</div></div></div>';
                }
                if($rows==='') $rows='<div class="empty"><strong>No recent activity</strong>Customer-visible updates will appear here.</div>';
                $timeline='<section data-v22-timeline class="card panel activity-panel"><div class="panel-head"><h3>Recent Relationship Activity</h3><a href="'.e(route('customer.section','timeline')).'">Full timeline →</a></div><div class="activity-list">'.$rows.'</div></section>';
                $needle='<section class="card panel activity-panel">';
                $position=strpos($html,$needle);
                if($position!==false) $html=substr($html,0,$position).$timeline.substr($html,$position);
            }

            // Mobile closure: preserve information density instead of turning every dashboard card into a full-width block.
            $v22Css='<style data-customer-dashboard-v22>
                .row-reason{font-size:7px;color:#9b6500;margin-top:3px;line-height:1.35}
                section.activity-panel:not([data-v22-timeline]){display:none}
                @media(max-width:780px){
                    .sidebar{padding:6px 7px}.nav-scroll{gap:2px}.nav-link{min-width:68px;min-height:54px;flex-direction:column;gap:4px;padding:7px 5px}.nav-link span:nth-child(2){display:block!important;max-width:62px;font-size:7px;line-height:1.1;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.nav-link .ui-icon{width:17px;height:17px}.nav-badge{display:none!important}
                    .topbar{height:58px}.content{padding-top:14px}.page-head{gap:10px;margin-bottom:12px}.page-head h2{font-size:22px}.filters{display:grid;grid-template-columns:repeat(3,1fr) auto;gap:6px}.filter-button{padding:0 11px}
                    .executive-strip{grid-template-columns:1fr 1fr}.executive-status{grid-column:1/-1}.executive-cell{min-height:68px;padding:11px 12px}
                    .quick-actions{grid-template-columns:1fr 1fr;gap:8px}.quick-action{min-height:72px;padding:10px}.quick-action:last-child{grid-column:1/-1}.quick-action strong{font-size:10px}.quick-action small{font-size:8px}
                    .stats{grid-template-columns:1fr 1fr;gap:8px}.stat{min-height:104px;padding:12px}.stat .value{font-size:21px;margin:9px 0 4px}.stat .value.compact{font-size:18px}.comparison{margin-top:5px}
                    .command-grid,.lower-grid{grid-template-columns:1fr}.panel{padding:13px}.stage-grid.journey-grid{grid-template-columns:repeat(2,1fr)}.stage{min-height:68px}.list-row{padding:10px 0}.row-title{font-size:10px}.row-sub,.row-reason{font-size:8px}
                    .asset-health{grid-template-columns:112px 1fr;gap:12px}.health-ring{width:96px;height:96px}.health-ring:before{inset:15px}.health-legend{gap:6px}.health-item{padding:8px}
                }
                @media(max-width:520px){
                    .main{padding:0 10px 20px}.topbar{margin:0 -10px;padding:0 12px}.welcome h1{font-size:14px}.avatar{width:32px;height:32px}
                    .filters{grid-template-columns:1fr 1fr}.filter-button{width:100%}.executive-strip,.quick-actions,.stats{grid-template-columns:1fr 1fr!important}.executive-status{grid-column:1/-1}.quick-action:last-child{grid-column:1/-1}.stat{min-width:0}.action-center{padding:12px}.action-copy strong{font-size:11px}.action-breakdown{grid-template-columns:1fr 1fr}
                    .asset-health{grid-template-columns:1fr}.health-ring{margin:auto}.panel-head{margin-bottom:10px}.activity-panel .list-row{grid-template-columns:7px 1fr}.activity-panel .row-title{white-space:normal}
                }
                @media(max-width:390px){.filters{grid-template-columns:1fr 1fr}.quick-actions,.stats{grid-template-columns:1fr!important}.quick-action:last-child{grid-column:auto}.executive-strip{grid-template-columns:1fr 1fr!important}}
            </style>';
            if(!str_contains($html,'data-customer-dashboard-v22')) $html=str_replace('</head>',$v22Css.'</head>',$html);
            $response->headers->set('X-UNIFCO-Customer-Portal-Presentation','customer-dashboard-v2.2-final-closure-20260915');
        }

        $response->setContent($html);
        return $response;
    }
}
