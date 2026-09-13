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

        $metrics = [
            'open_work_orders' => (clone $workOrders)->whereIn('status', $openStatuses)->count(),
            'emergency_work_orders' => (clone $workOrders)->whereIn('status', $openStatuses)->whereIn('priority', ['EMERGENCY','CRITICAL','URGENT'])->count(),
            'overdue_work_orders' => (clone $workOrders)->whereIn('status', $openStatuses)->whereNotNull('planned_start')->where('planned_start', '<', now())->count(),
            'active_projects' => (clone $projects)->whereNotIn('status', ['COMPLETED','CLOSED','CANCELLED'])->count(),
            'active_assets' => (clone $assets)->whereNotIn('status', ['RETIRED','DISPOSED','INACTIVE'])->count(),
            'customers_in_scope' => (clone $customers)->where('status', 'ACTIVE')->count(),
            'open_service_requests' => (clone $serviceRequests)->whereIn('workflow_stage', $openRequestStages)->count(),
            'sla_breaches' => (clone $serviceRequests)->whereIn('workflow_stage', $openRequestStages)->whereNotNull('current_stage_due_at')->where('current_stage_due_at','<',now())->count(),
        ];

        $priorityWorkOrders = (clone $workOrders)
            ->whereNotIn('status', $closedStatuses)
            ->orderByRaw("CASE priority WHEN 'EMERGENCY' THEN 1 WHEN 'CRITICAL' THEN 2 WHEN 'URGENT' THEN 3 WHEN 'HIGH' THEN 4 ELSE 5 END")
            ->orderBy('planned_start')
            ->limit(8)
            ->get(['id','work_order_no','asset_id','maintenance_type','priority','status','planned_start']);

        $statusBreakdown = (clone $workOrders)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return view('operations.manager-dashboard', compact('metrics','priorityWorkOrders','statusBreakdown'));
    }
}
