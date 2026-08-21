<?php

namespace App\Http\Controllers;

use App\Models\{Asset,Customer,CustomerSite,Employee,FinancialDocument,MaintenancePlan,PlatformNotification,PurchaseOrder,ServiceContract,ServiceRequest,WorkOrder};
use App\Services\AuthorizationService;
use App\Services\Dashboard\ExecutiveAnalyticsService;
use App\Services\EAM\AssetSparePartReorderService;
use Carbon\Carbon;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AssetSparePartReorderService $reorder, AuthorizationService $authorization, ExecutiveAnalyticsService $analyticsService): View|RedirectResponse
    {
        $user=auth()->user();
        if($user->role==='CUSTOMER') return redirect()->route('customer.portal');

        $days=max(7,min(90,(int)$request->query('days',30)));
        $from=now()->subDays($days);

        $capabilities=[
            'maintenance'=>$authorization->allows($user,'maintenance.work_order.read'),
            'maintenance_manage'=>$authorization->allows($user,'maintenance.work_order.manage'),
            'eam'=>$authorization->allows($user,'eam.asset.read'),
            'eam_manage'=>$authorization->allows($user,'eam.asset.manage'),
            'finance'=>$authorization->allows($user,'finance.journal.read'),
            'procurement'=>$authorization->allows($user,'procurement.po.read'),
            'crm'=>$authorization->allows($user,'crm.customer.read'),
            'crm_manage'=>$authorization->allows($user,'crm.customer.manage'),
            'inventory'=>$authorization->allows($user,'inventory.stock.read'),
        ];
        $dashboardMode=$this->dashboardMode($user->role,$capabilities);

        $customers=Customer::orderBy('name')->get();
        $tenantCustomerIds=$customers->pluck('id');
        $customerId=$request->integer('customer_id') ?: null;
        $siteId=$request->integer('site_id') ?: null;
        $contractId=$request->integer('contract_id') ?: null;
        if($customerId && !$tenantCustomerIds->contains($customerId)) $customerId=null;
        if($siteId && !CustomerSite::whereKey($siteId)->whereIn('customer_id',$tenantCustomerIds)->when($customerId,fn($q)=>$q->where('customer_id',$customerId))->exists()) $siteId=null;
        if($contractId && !ServiceContract::whereKey($contractId)->when($customerId,fn($q)=>$q->where('customer_id',$customerId))->exists()) $contractId=null;

        $sites=CustomerSite::whereIn('customer_id',$tenantCustomerIds)->when($customerId,fn($q)=>$q->where('customer_id',$customerId))->orderBy('name')->get();
        $contractOptions=ServiceContract::query()->when($customerId,fn($q)=>$q->where('customer_id',$customerId))->orderByDesc('starts_on')->get();

        $assetQuery=Asset::with(['customer','site'])
            ->when($customerId,fn($q)=>$q->where('customer_id',$customerId))
            ->when($siteId,fn($q)=>$q->where('customer_site_id',$siteId));
        if($contractId){
            $contractAssetIds=DB::table('asset_contract_assignments')->where('service_contract_id',$contractId)->where('status','ACTIVE')->pluck('asset_id');
            $assetQuery->whereIn('id',$contractAssetIds);
        }
        $assets=$assetQuery->get();
        $assetIds=$assets->pluck('id');
        $scopeApplied=(bool)($customerId||$siteId||$contractId);

        $workOrders=WorkOrder::with('asset')
            ->when($scopeApplied,fn($q)=>$q->whereIn('asset_id',$assetIds))
            ->when($contractId,fn($q)=>$q->where('service_contract_id',$contractId))
            ->latest('created_at')->limit(300)->get();
        $serviceRequests=ServiceRequest::query()
            ->when($customerId,fn($q)=>$q->where('customer_id',$customerId))
            ->when($siteId || $contractId,fn($q)=>$q->whereIn('asset_id',$assetIds))
            ->when($contractId,fn($q)=>$q->where('service_contract_id',$contractId))
            ->latest('created_at')->limit(300)->get();
        $contracts=ServiceContract::query()->when($customerId,fn($q)=>$q->where('customer_id',$customerId))->when($contractId,fn($q)=>$q->whereKey($contractId))->orderBy('ends_on')->get();

        $openStatuses=['OPEN','NEW','ASSIGNED','STARTED','IN_PROGRESS','IN PROGRESS','WAITING_PARTS','WAITING PARTS'];
        $completedStatuses=['COMPLETED','CLOSED'];
        $openWorkOrders=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$openStatuses,true));
        $completedWorkOrders=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$completedStatuses,true));
        $inProgress=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),['STARTED','IN_PROGRESS','IN PROGRESS'],true));
        $waitingParts=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),['WAITING_PARTS','WAITING PARTS'],true));
        $overdueWorkOrders=$openWorkOrders->filter(fn($w)=>$w->planned_start && $w->planned_start->isPast());
        $criticalWorkOrders=$openWorkOrders->filter(fn($w)=>in_array(strtoupper((string)$w->priority),['CRITICAL','EMERGENCY'],true));

        $activePlans=MaintenancePlan::where('status','ACTIVE')->when($scopeApplied,fn($q)=>$q->whereIn('asset_id',$assetIds))->when($contractId,fn($q)=>$q->where('service_contract_id',$contractId))->get();
        $overduePm=$activePlans->filter(fn($p)=>$p->next_due_date && $p->next_due_date->isPast());
        $pmDue30=$activePlans->filter(fn($p)=>$p->next_due_date && $p->next_due_date->between(today(),today()->addDays(30)));
        $pmOrders=$workOrders->where('maintenance_type','PREVENTIVE');
        $pmCompleted=$pmOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$completedStatuses,true))->count();
        $pmCompliance=$pmOrders->count()?round($pmCompleted/$pmOrders->count()*100,1):100.0;

        $healthKnown=$assets->whereNotNull('health_score');
        $averageAssetHealth=$healthKnown->count()?round((float)$healthKnown->avg('health_score'),1):100.0;
        $criticalAssets=$assets->filter(fn($a)=>in_array($a->health_band,['ATTENTION','CRITICAL'],true) || in_array($a->operational_status,['BREAKDOWN','OUT_OF_SERVICE'],true));
        $replacementAssets=$assets->where('replacement_recommendation','PLAN_REPLACEMENT');

        $stockAlerts=$reorder->alerts($customerId,$siteId)->when($contractId,fn($rows)=>$rows->whereIn('asset_id',$assetIds))->filter(fn($a)=>$a->computed_alert_status!=='OK')->values();
        $outOfStock=$stockAlerts->where('computed_alert_status','OUT_OF_STOCK');

        $newServiceRequests=$serviceRequests->filter(fn($r)=>$r->created_at && $r->created_at->gte($from));
        $openServiceRequests=$serviceRequests->filter(fn($r)=>!in_array(strtoupper((string)$r->status),['RESOLVED','CLOSED','COMPLETED','CANCELLED'],true));
        $slaBreaches=$openServiceRequests->filter(fn($r)=>$this->isSlaBreached($r));
        $slaPerformance=$openServiceRequests->count()?round(max(0,100-($slaBreaches->count()/$openServiceRequests->count()*100)),1):100.0;

        $invoiceQuery=FinancialDocument::where('document_type','AR_INVOICE')->when($customerId,fn($q)=>$q->where('customer_id',$customerId));
        $openInvoices=(clone $invoiceQuery)->where('open_amount','>',0)->get();
        $outstandingReceivables=(float)$openInvoices->sum('open_amount');
        $overdueReceivables=(float)$openInvoices->filter(fn($i)=>$i->due_date && $i->due_date->isPast())->sum('open_amount');
        $revenueThisMonth=(float)(clone $invoiceQuery)->whereBetween('document_date',[now()->startOfMonth(),now()->endOfMonth()])->sum('amount');
        $revenueLastMonth=(float)(clone $invoiceQuery)->whereBetween('document_date',[now()->subMonthNoOverflow()->startOfMonth(),now()->subMonthNoOverflow()->endOfMonth()])->sum('amount');

        $pendingPOs=PurchaseOrder::whereIn('status',['DRAFT','PENDING','PENDING_APPROVAL','SUBMITTED'])->get();
        $pendingPOValue=(float)$pendingPOs->sum('total');
        $activeEmployees=Employee::where('status','ACTIVE')->count();
        $activeCustomers=$customerId ? 1 : Customer::where('status','ACTIVE')->count();
        $expiringContracts=$contracts->filter(fn($c)=>$c->ends_on && $c->ends_on->between(today(),today()->addDays(60)));

        $currentMonthStart=now()->startOfMonth();
        $previousMonthStart=now()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd=now()->subMonthNoOverflow()->endOfMonth();
        $comparison=[
            'revenue'=>$this->comparison($revenueThisMonth,$revenueLastMonth),
            'opened_wo'=>$this->comparison($workOrders->filter(fn($w)=>$w->created_at && $w->created_at->gte($currentMonthStart))->count(),$workOrders->filter(fn($w)=>$w->created_at && $w->created_at->between($previousMonthStart,$previousMonthEnd))->count()),
            'completed_wo'=>$this->comparison($workOrders->filter(fn($w)=>$w->completed_at && $w->completed_at->gte($currentMonthStart))->count(),$workOrders->filter(fn($w)=>$w->completed_at && $w->completed_at->between($previousMonthStart,$previousMonthEnd))->count()),
            'requests'=>$this->comparison($serviceRequests->filter(fn($r)=>$r->created_at && $r->created_at->gte($currentMonthStart))->count(),$serviceRequests->filter(fn($r)=>$r->created_at && $r->created_at->between($previousMonthStart,$previousMonthEnd))->count()),
        ];

        $workOrderTotal=max(1,$workOrders->count());
        $backlogScore=max(0,100-($overdueWorkOrders->count()/$workOrderTotal*100));
        $stockScore=max(0,100-min(100,$stockAlerts->count()*8));
        $operationalScore=(int)round(($averageAssetHealth*.35)+($pmCompliance*.20)+($slaPerformance*.20)+($backlogScore*.15)+($stockScore*.10));
        $operationalBand=match(true){$operationalScore>=85=>'Excellent',$operationalScore>=70=>'Good',$operationalScore>=55=>'Attention',default=>'Critical'};

        $maintenanceUrl=$capabilities['maintenance']?route('maintenance.work-orders.index'):route('dashboard');
        $eamUrl=$capabilities['eam']?route('eam.health.index'):route('dashboard');
        $crmUrl=$capabilities['crm']?route('crm.customers.index'):route('dashboard');
        $serviceUrl=$capabilities['crm_manage']?route('admin.public-requests.index'):$crmUrl;
        $procurementUrl=$capabilities['procurement']?route('procurement.purchase-orders.index'):route('dashboard');
        $actionItems=collect();
        if($capabilities['maintenance']){
            $this->pushAction($actionItems,$criticalWorkOrders->count(),'CRITICAL','Critical work orders require action','أوامر عمل حرجة تحتاج تدخل',$maintenanceUrl);
            $this->pushAction($actionItems,$overdueWorkOrders->count(),'HIGH','Overdue work orders','أوامر عمل متأخرة',$maintenanceUrl);
            $this->pushAction($actionItems,$overduePm->count(),'HIGH','Preventive maintenance overdue','خطط صيانة وقائية متأخرة',$eamUrl);
        }
        if($capabilities['eam']) $this->pushAction($actionItems,$criticalAssets->count(),'HIGH','Assets need engineering attention','أصول تحتاج مراجعة هندسية',$eamUrl);
        if($capabilities['crm']) $this->pushAction($actionItems,$slaBreaches->count(),'HIGH','Service requests breached SLA','طلبات خدمة تجاوزت SLA',$serviceUrl);
        if($capabilities['inventory']) $this->pushAction($actionItems,$outOfStock->count(),'CRITICAL','Critical spare parts out of stock','قطع غيار حرجة غير متوفرة',$eamUrl);
        if($capabilities['crm']) $this->pushAction($actionItems,$expiringContracts->count(),'MEDIUM','Contracts expire within 60 days','عقود تنتهي خلال 60 يوم',$crmUrl);
        if($capabilities['procurement']) $this->pushAction($actionItems,$pendingPOs->count(),'MEDIUM','Purchase orders awaiting action','أوامر شراء بانتظار الإجراء',$procurementUrl);

        $recentActivity=$this->recentActivity($workOrders,$serviceRequests,$serviceUrl,$capabilities['maintenance']);
        $topRiskAssets=$criticalAssets->sortBy('health_score')->take(6);
        $recentWorkOrders=$workOrders->take(7);
        $statusDistribution=['Open'=>$openWorkOrders->count(),'In Progress'=>$inProgress->count(),'Waiting Parts'=>$waitingParts->count(),'Overdue'=>$overdueWorkOrders->count(),'Completed'=>$completedWorkOrders->count()];
        $trend=$this->monthlyWorkOrderTrend($assetIds,$contractId,$scopeApplied);
        $unreadNotifications=PlatformNotification::where('user_id',$user->id)->whereNull('read_at')->count();
        $filterActive=$scopeApplied;
        $analytics=$analyticsService->build($assets,$openWorkOrders,$serviceRequests,$slaBreaches,$contracts,$stockAlerts);

        return view('dashboard',compact('days','dashboardMode','capabilities','customerId','siteId','contractId','customers','sites','contractOptions','filterActive','unreadNotifications','comparison','operationalScore','operationalBand','averageAssetHealth','pmCompliance','slaPerformance','openWorkOrders','overdueWorkOrders','criticalWorkOrders','overduePm','criticalAssets','replacementAssets','stockAlerts','outOfStock','newServiceRequests','openServiceRequests','slaBreaches','outstandingReceivables','overdueReceivables','revenueThisMonth','pendingPOs','pendingPOValue','activeEmployees','activeCustomers','expiringContracts','pmDue30','actionItems','recentActivity','topRiskAssets','recentWorkOrders','statusDistribution','trend','assets','contracts','analytics'));
    }

    private function dashboardMode(string $role,array $c): string
    {
        if($role==='ADMIN') return 'EXECUTIVE';
        if($c['maintenance'] && $c['eam']) return 'MAINTENANCE';
        if($c['finance']) return 'FINANCE';
        if($c['procurement'] || $c['inventory']) return 'SUPPLY_CHAIN';
        if($c['crm']) return 'CUSTOMER_SERVICE';
        return 'OPERATIONS';
    }

    private function isSlaBreached(ServiceRequest $request): bool
    {
        $created=$request->created_at ? Carbon::parse($request->created_at) : null;
        if(!$created) return false;
        return (bool)(($request->response_sla_minutes && !$request->responded_at && $created->copy()->addMinutes((int)$request->response_sla_minutes)->isPast()) || ($request->resolution_sla_minutes && !$request->resolved_at && $created->copy()->addMinutes((int)$request->resolution_sla_minutes)->isPast()));
    }

    private function comparison(float|int $current,float|int $previous): object
    {
        $change=$previous==0 ? ($current==0?0:100) : round((($current-$previous)/abs($previous))*100,1);
        return (object)['current'=>$current,'previous'=>$previous,'change'=>$change,'direction'=>$change>0?'UP':($change<0?'DOWN':'FLAT')];
    }

    private function pushAction(Collection $items,int $count,string $severity,string $en,string $ar,string $url): void
    {
        if($count>0) $items->push((object)compact('count','severity','en','ar','url'));
    }

    private function recentActivity(Collection $workOrders,Collection $requests,string $serviceUrl,bool $canMaintenance): Collection
    {
        $items=collect();
        if($canMaintenance) foreach($workOrders->take(8) as $wo) $items->push((object)['time'=>$wo->updated_at,'type'=>'WORK_ORDER','title'=>$wo->work_order_no.' · '.($wo->asset?->asset_code ?: 'Asset'),'detail'=>$wo->maintenance_type.' · '.$wo->status,'url'=>route('maintenance.work-orders.show',$wo)]);
        foreach($requests->take(8) as $r) $items->push((object)['time'=>$r->updated_at,'type'=>'SERVICE_REQUEST','title'=>$r->request_no.' · '.($r->subject ?: $r->service_category),'detail'=>$r->priority.' · '.$r->status,'url'=>$serviceUrl]);
        return $items->sortByDesc('time')->take(10)->values();
    }

    private function monthlyWorkOrderTrend(Collection $assetIds,?int $contractId,bool $scoped): array
    {
        $labels=[];$opened=[];$completed=[];
        for($i=5;$i>=0;$i--){
            $month=now()->copy()->subMonths($i);$start=$month->copy()->startOfMonth();$end=$month->copy()->endOfMonth();
            $base=WorkOrder::query()->when($scoped,fn($q)=>$q->whereIn('asset_id',$assetIds))->when($contractId,fn($q)=>$q->where('service_contract_id',$contractId));
            $labels[]=$month->format('M');$opened[]=(clone $base)->whereBetween('created_at',[$start,$end])->count();$completed[]=(clone $base)->whereBetween('completed_at',[$start,$end])->count();
        }
        return compact('labels','opened','completed');
    }
}
