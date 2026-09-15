<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\{Asset, Customer, Project, ServiceRequest, WorkOrder};
use App\Services\ScopeService;
use Illuminate\Http\Request;

class OperationsManagerDashboardController extends Controller
{
    public function __invoke(Request $request, ScopeService $scopes)
    {
        $user = $request->user();

        $workOrders = $scopes->apply(WorkOrder::query()->where('tenant_id', $user->tenant_id), $user);
        $assets = $scopes->apply(Asset::query()->where('tenant_id', $user->tenant_id), $user);
        $projects = $scopes->apply(Project::query()->where('tenant_id', $user->tenant_id), $user);
        $customers = $scopes->apply(Customer::query()->where('tenant_id', $user->tenant_id), $user);
        $serviceRequests = $scopes->apply(ServiceRequest::query()->where('tenant_id', $user->tenant_id), $user);

        $openStatuses = ['OPEN','PLANNED','ASSIGNED','IN_PROGRESS','WAITING_PARTS','ON_HOLD'];
        $closedStatuses = ['COMPLETED','CLOSED','CANCELLED'];
        $openRequestStages = ['NEW','TRIAGE','ASSIGNED','IN_PROGRESS','WAITING_PARTS','ON_HOLD','ESCALATED'];

        $openServiceRequests = (clone $serviceRequests)->whereIn('workflow_stage', $openRequestStages)->count();
        $slaBreaches = (clone $serviceRequests)
            ->whereIn('workflow_stage', $openRequestStages)
            ->whereNotNull('current_stage_due_at')
            ->where('current_stage_due_at','<',now())
            ->count();

        $metrics = [
            'open_work_orders' => (clone $workOrders)->whereIn('status', $openStatuses)->count(),
            'emergency_work_orders' => (clone $workOrders)->whereIn('status', $openStatuses)->whereIn('priority', ['EMERGENCY','CRITICAL','URGENT'])->count(),
            'overdue_work_orders' => (clone $workOrders)->whereIn('status', $openStatuses)->whereNotNull('planned_start')->where('planned_start', '<', now())->count(),
            'active_projects' => (clone $projects)->whereNotIn('status', ['COMPLETED','CLOSED','CANCELLED'])->count(),
            'active_assets' => (clone $assets)->whereNotIn('status', ['RETIRED','DISPOSED','INACTIVE'])->count(),
            'customers_in_scope' => (clone $customers)->where('status', 'ACTIVE')->count(),
            'open_service_requests' => $openServiceRequests,
            'sla_breaches' => $slaBreaches,
            'unassigned_requests' => (clone $serviceRequests)->whereIn('workflow_stage', $openRequestStages)->whereNull('assigned_engineer_id')->count(),
            'escalated_requests' => (clone $serviceRequests)->where('workflow_stage', 'ESCALATED')->count(),
            'in_progress_requests' => (clone $serviceRequests)->where('workflow_stage', 'IN_PROGRESS')->count(),
            'waiting_parts_requests' => (clone $serviceRequests)->where('workflow_stage', 'WAITING_PARTS')->count(),
        ];

        $metrics['sla_compliance'] = $openServiceRequests > 0
            ? (int) round(max(0, (($openServiceRequests - $slaBreaches) / $openServiceRequests) * 100))
            : 100;
        $metrics['attention_total'] = $metrics['unassigned_requests'] + $metrics['escalated_requests'] + $metrics['sla_breaches'] + $metrics['overdue_work_orders'];

        $priorityWorkOrders = (clone $workOrders)
            ->whereNotIn('status', $closedStatuses)
            ->orderByRaw("CASE priority WHEN 'EMERGENCY' THEN 1 WHEN 'CRITICAL' THEN 2 WHEN 'URGENT' THEN 3 WHEN 'HIGH' THEN 4 ELSE 5 END")
            ->orderBy('planned_start')
            ->limit(8)
            ->get(['id','work_order_no','asset_id','maintenance_type','priority','status','planned_start']);

        $recentServiceRequests = (clone $serviceRequests)
            ->whereIn('workflow_stage', $openRequestStages)
            ->orderByRaw("CASE priority WHEN 'EMERGENCY' THEN 1 WHEN 'CRITICAL' THEN 2 WHEN 'URGENT' THEN 3 WHEN 'HIGH' THEN 4 ELSE 5 END")
            ->orderByRaw('CASE WHEN current_stage_due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('current_stage_due_at')
            ->latest('id')
            ->limit(8)
            ->get(['id','request_no','priority','status','workflow_stage','assigned_engineer_id','current_stage_due_at','created_at']);

        $statusBreakdown = (clone $workOrders)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $actionRequired = [
            ['label' => 'Unassigned requests', 'count' => $metrics['unassigned_requests'], 'tone' => 'warning', 'url' => route('operations-manager.service-requests.index', ['stage' => 'NEW'])],
            ['label' => 'SLA overdue', 'count' => $metrics['sla_breaches'], 'tone' => 'danger', 'url' => route('operations-manager.service-requests.index')],
            ['label' => 'Escalated requests', 'count' => $metrics['escalated_requests'], 'tone' => 'danger', 'url' => route('operations-manager.service-requests.index', ['stage' => 'ESCALATED'])],
            ['label' => 'Overdue work orders', 'count' => $metrics['overdue_work_orders'], 'tone' => 'warning', 'url' => route('maintenance.work-orders.index')],
            ['label' => 'Waiting for parts', 'count' => $metrics['waiting_parts_requests'], 'tone' => 'neutral', 'url' => route('operations-manager.service-requests.index', ['stage' => 'WAITING_PARTS'])],
        ];

        return view('operations.manager-dashboard', compact(
            'metrics', 'priorityWorkOrders', 'recentServiceRequests', 'statusBreakdown', 'actionRequired'
        ));
    }
}
