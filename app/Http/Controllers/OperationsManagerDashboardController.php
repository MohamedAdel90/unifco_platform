<?php

namespace App\Http\Controllers;

use App\Services\AuthorizationService;
use App\Services\Dashboard\OperationsManagerDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsManagerDashboardController extends Controller
{
    public function __invoke(Request $request, AuthorizationService $authorization, OperationsManagerDashboardService $dashboard): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('OPERATIONS_MANAGER'), 403);
        $authorization->authorize($user, 'dashboard.view');

        $capabilities = [
            'maintenance' => $authorization->allows($user, 'maintenance.work_order.read'),
            'maintenance_manage' => $authorization->allows($user, 'maintenance.work_order.manage'),
            'eam' => $authorization->allows($user, 'eam.asset.read'),
            'eam_manage' => $authorization->allows($user, 'eam.asset.manage'),
            'crm' => $authorization->allows($user, 'crm.customer.read'),
            'crm_manage' => $authorization->allows($user, 'crm.customer.manage'),
        ];

        $workOrders = $dashboard->workOrders($user);
        $serviceRequests = $dashboard->serviceRequests($user);
        $assets = $dashboard->assets($user);

        $openWorkOrders = (clone $workOrders)->whereNotIn('status', ['COMPLETED','CLOSED','CANCELLED'])->get();
        $overdueWorkOrders = (clone $workOrders)->whereNotIn('status', ['COMPLETED','CLOSED','CANCELLED'])
            ->whereNotNull('planned_end')->where('planned_end', '<', now())->get();
        $criticalWorkOrders = (clone $workOrders)->whereNotIn('status', ['COMPLETED','CLOSED','CANCELLED'])
            ->whereIn('priority', ['CRITICAL','EMERGENCY','URGENT'])->get();
        $recentWorkOrders = (clone $workOrders)->latest('id')->limit(10)->get();

        $openServiceRequests = (clone $serviceRequests)->whereNotIn('status', ['CLOSED','CANCELLED','COMPLETED'])->get();
        $slaBreaches = (clone $serviceRequests)->whereNotIn('status', ['CLOSED','CANCELLED','COMPLETED'])
            ->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->get();
        $criticalAssets = (clone $assets)->where(function ($q) {
            $q->whereIn('status', ['DOWN','OUT_OF_SERVICE','CRITICAL'])
              ->orWhere('health_score', '<', 50);
        })->get();

        $slaTotal = max(1, $openServiceRequests->count());
        $slaPerformance = (int) round((1 - ($slaBreaches->count() / $slaTotal)) * 100);
        $pmCompliance = 100; // Replaced by plan-based metric when maintenance-plan scope gateway is added.
        $averageAssetHealth = (int) round((clone $assets)->whereNotNull('health_score')->avg('health_score') ?? 100);
        $operationalScore = max(0, min(100, (int) round(($slaPerformance * .45) + ($pmCompliance * .25) + ($averageAssetHealth * .30))));
        $operationalBand = $operationalScore >= 90 ? 'Operational' : ($operationalScore >= 75 ? 'Watch' : ($operationalScore >= 60 ? 'Degraded' : 'Critical'));

        $actionItems = collect([
            ['count'=>$criticalWorkOrders->count(),'severity'=>'Critical','ar'=>'أوامر عمل حرجة تحتاج تدخلاً','en'=>'Critical work orders require attention','url'=>route('maintenance.work-orders.index')],
            ['count'=>$overdueWorkOrders->count(),'severity'=>'High','ar'=>'أوامر عمل متأخرة','en'=>'Overdue work orders','url'=>route('maintenance.work-orders.index')],
            ['count'=>$slaBreaches->count(),'severity'=>'High','ar'=>'طلبات تجاوزت SLA','en'=>'Service requests breached SLA','url'=>route('admin.public-requests.index')],
            ['count'=>$criticalAssets->count(),'severity'=>'Medium','ar'=>'أصول حرجة أو متوقفة','en'=>'Critical or down assets','url'=>route('eam.assets.index')],
        ])->filter(fn ($item) => $item['count'] > 0)->values();

        return view('operations-manager.dashboard', compact(
            'capabilities','openWorkOrders','overdueWorkOrders','criticalWorkOrders','recentWorkOrders',
            'openServiceRequests','slaBreaches','criticalAssets','slaPerformance','pmCompliance',
            'averageAssetHealth','operationalScore','operationalBand','actionItems'
        ));
    }
}
