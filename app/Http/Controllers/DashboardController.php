<?php

namespace App\Http\Controllers;

use App\Models\{Asset,Customer,Employee,FinancialDocument,MaintenancePlan,PurchaseOrder,ServiceContract,ServiceRequest,WorkOrder};
use App\Services\EAM\AssetSparePartReorderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AssetSparePartReorderService $reorder): View
    {
        $user=auth()->user();
        $tenantId=(int)$user->tenant_id;
        $days=max(7,min(90,(int)$request->query('days',30)));
        $from=now()->subDays($days);

        $assets=Asset::with(['customer','site'])->get();
        $workOrders=WorkOrder::with('asset')->latest('created_at')->limit(250)->get();
        $serviceRequests=ServiceRequest::latest('created_at')->limit(250)->get();
        $contracts=ServiceContract::orderBy('ends_on')->get();

        $openStatuses=['OPEN','NEW','ASSIGNED','STARTED','IN_PROGRESS','IN PROGRESS','WAITING_PARTS','WAITING PARTS'];
        $completedStatuses=['COMPLETED','CLOSED'];
        $openWorkOrders=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$openStatuses,true));
        $completedWorkOrders=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$completedStatuses,true));
        $inProgress=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),['STARTED','IN_PROGRESS','IN PROGRESS'],true));
        $waitingParts=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),['WAITING_PARTS','WAITING PARTS'],true));
        $overdueWorkOrders=$openWorkOrders->filter(fn($w)=>$w->planned_start && $w->planned_start->isPast());
        $criticalWorkOrders=$openWorkOrders->where('priority','CRITICAL');

        $activePlans=MaintenancePlan::where('status','ACTIVE')->get();
        $overduePm=$activePlans->filter(fn($p)=>$p->next_due_date && $p->next_due_date->isPast());
        $pmDue30=$activePlans->filter(fn($p)=>$p->next_due_date && $p->next_due_date->between(today(),today()->addDays(30)));
        $pmOrders=$workOrders->where('maintenance_type','PREVENTIVE');
        $pmCompleted=$pmOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$completedStatuses,true))->count();
        $pmCompliance=$pmOrders->count()?round($pmCompleted/$pmOrders->count()*100,1):100.0;

        $healthKnown=$assets->whereNotNull('health_score');
        $averageAssetHealth=$healthKnown->count()?round((float)$healthKnown->avg('health_score'),1):100.0;
        $criticalAssets=$assets->filter(fn($a)=>in_array($a->health_band,['ATTENTION','CRITICAL'],true) || in_array($a->operational_status,['BREAKDOWN','OUT_OF_SERVICE'],true));
        $replacementAssets=$assets->where('replacement_recommendation','PLAN_REPLACEMENT');

        $stockAlerts=$reorder->alerts()->filter(fn($a)=>$a->computed_alert_status!=='OK')->values();
        $outOfStock=$stockAlerts->where('computed_alert_status','OUT_OF_STOCK');

        $newServiceRequests=$serviceRequests->filter(fn($r)=>$r->created_at && $r->created_at->gte($from));
        $openServiceRequests=$serviceRequests->filter(fn($r)=>!in_array(strtoupper((string)$r->status),['RESOLVED','CLOSED','COMPLETED','CANCELLED'],true));
        $slaBreaches=$openServiceRequests->filter(function($r){
            $created=$r->created_at ? Carbon::parse($r->created_at) : null;
            if(!$created) return false;
            $responseBreach=$r->response_sla_minutes && !$r->responded_at && $created->copy()->addMinutes((int)$r->response_sla_minutes)->isPast();
            $resolutionBreach=$r->resolution_sla_minutes && !$r->resolved_at && $created->copy()->addMinutes((int)$r->resolution_sla_minutes)->isPast();
            return $responseBreach || $resolutionBreach;
        });
        $slaPerformance=$openServiceRequests->count()?round(max(0,100-($slaBreaches->count()/$openServiceRequests->count()*100)),1):100.0;

        $openInvoices=FinancialDocument::where('document_type','AR_INVOICE')->where('open_amount','>',0)->get();
        $outstandingReceivables=(float)$openInvoices->sum('open_amount');
        $overdueReceivables=(float)$openInvoices->filter(fn($i)=>$i->due_date && $i->due_date->isPast())->sum('open_amount');
        $revenueThisMonth=(float)FinancialDocument::where('document_type','AR_INVOICE')->whereBetween('document_date',[now()->startOfMonth(),now()->endOfMonth()])->sum('amount');

        $pendingPOs=PurchaseOrder::whereIn('status',['DRAFT','PENDING','PENDING_APPROVAL','SUBMITTED'])->get();
        $pendingPOValue=(float)$pendingPOs->sum('total');
        $activeEmployees=Employee::where('status','ACTIVE')->count();
        $activeCustomers=Customer::where('status','ACTIVE')->count();
        $expiringContracts=$contracts->filter(fn($c)=>$c->ends_on && $c->ends_on->between(today(),today()->addDays(60)));

        $workOrderTotal=max(1,$workOrders->count());
        $backlogScore=max(0,100-($overdueWorkOrders->count()/$workOrderTotal*100));
        $stockScore=max(0,100-min(100,$stockAlerts->count()*8));
        $operationalScore=(int)round(
            ($averageAssetHealth*.35)+($pmCompliance*.20)+($slaPerformance*.20)+($backlogScore*.15)+($stockScore*.10)
        );
        $operationalBand=match(true){$operationalScore>=85=>'Excellent',$operationalScore>=70=>'Good',$operationalScore>=55=>'Attention',default=>'Critical'};

        $actionItems=collect();
        $this->pushAction($actionItems,$criticalWorkOrders->count(),'CRITICAL','Critical work orders require action','أوامر عمل حرجة تحتاج تدخل',route('maintenance.work-orders.index'));
        $this->pushAction($actionItems,$overdueWorkOrders->count(),'HIGH','Overdue work orders','أوامر عمل متأخرة',route('maintenance.work-orders.index'));
        $this->pushAction($actionItems,$overduePm->count(),'HIGH','Preventive maintenance overdue','خطط صيانة وقائية متأخرة',route('eam.health.index'));
        $this->pushAction($actionItems,$criticalAssets->count(),'HIGH','Assets need engineering attention','أصول تحتاج مراجعة هندسية',route('eam.health.index'));
        $this->pushAction($actionItems,$slaBreaches->count(),'HIGH','Service requests breached SLA','طلبات خدمة تجاوزت SLA',route('admin.public-requests.index'));
        $this->pushAction($actionItems,$outOfStock->count(),'CRITICAL','Critical spare parts out of stock','قطع غيار حرجة غير متوفرة',route('eam.health.index'));
        $this->pushAction($actionItems,$expiringContracts->count(),'MEDIUM','Contracts expire within 60 days','عقود تنتهي خلال 60 يوم',route('crm.customers.index'));
        $this->pushAction($actionItems,$pendingPOs->count(),'MEDIUM','Purchase orders awaiting action','أوامر شراء بانتظار الإجراء',route('procurement.purchase-orders.index'));

        $recentActivity=$this->recentActivity($workOrders,$serviceRequests,$tenantId);
        $topRiskAssets=$criticalAssets->sortBy('health_score')->take(6);
        $recentWorkOrders=$workOrders->take(7);

        $statusDistribution=[
            'Open'=>$openWorkOrders->count(),
            'In Progress'=>$inProgress->count(),
            'Waiting Parts'=>$waitingParts->count(),
            'Overdue'=>$overdueWorkOrders->count(),
            'Completed'=>$completedWorkOrders->count(),
        ];

        $trend=$this->monthlyWorkOrderTrend($tenantId);

        return view('dashboard',compact(
            'days','operationalScore','operationalBand','averageAssetHealth','pmCompliance','slaPerformance','openWorkOrders','overdueWorkOrders','criticalWorkOrders',
            'criticalAssets','replacementAssets','stockAlerts','outOfStock','newServiceRequests','openServiceRequests','slaBreaches','outstandingReceivables','overdueReceivables',
            'revenueThisMonth','pendingPOs','pendingPOValue','activeEmployees','activeCustomers','expiringContracts','pmDue30','actionItems','recentActivity','topRiskAssets',
            'recentWorkOrders','statusDistribution','trend','assets','contracts'
        ));
    }

    private function pushAction(Collection $items,int $count,string $severity,string $en,string $ar,string $url): void
    {
        if($count<=0) return;
        $items->push((object)compact('count','severity','en','ar','url'));
    }

    private function recentActivity(Collection $workOrders,Collection $requests,int $tenantId): Collection
    {
        $items=collect();
        foreach($workOrders->take(8) as $wo){
            $items->push((object)[
                'time'=>$wo->updated_at,'type'=>'WORK_ORDER','title'=>$wo->work_order_no.' · '.($wo->asset?->asset_code ?: 'Asset'),
                'detail'=>$wo->maintenance_type.' · '.$wo->status,'url'=>route('maintenance.work-orders.show',$wo),
            ]);
        }
        foreach($requests->take(8) as $r){
            $items->push((object)[
                'time'=>$r->updated_at,'type'=>'SERVICE_REQUEST','title'=>$r->request_no.' · '.($r->subject ?: $r->service_category),
                'detail'=>$r->priority.' · '.$r->status,'url'=>route('admin.public-requests.index'),
            ]);
        }
        return $items->sortByDesc('time')->take(10)->values();
    }

    private function monthlyWorkOrderTrend(int $tenantId): array
    {
        $labels=[];$opened=[];$completed=[];
        for($i=5;$i>=0;$i--){
            $month=now()->copy()->subMonths($i);
            $start=$month->copy()->startOfMonth();$end=$month->copy()->endOfMonth();
            $labels[]=$month->format('M');
            $opened[]=WorkOrder::whereBetween('created_at',[$start,$end])->count();
            $completed[]=WorkOrder::whereBetween('completed_at',[$start,$end])->count();
        }
        return compact('labels','opened','completed');
    }
}
