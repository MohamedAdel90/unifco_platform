<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\{Customer,Project,ServiceRequest,WorkOrder};
use App\Services\ScopeService;
use Illuminate\Http\Request;

class OperationsReportController extends Controller
{
    public function __invoke(Request $request, ScopeService $scopes)
    {
        $user=$request->user();
        $workOrders=$scopes->apply(WorkOrder::query()->where('tenant_id',$user->tenant_id),$user);
        $serviceRequests=$scopes->apply(ServiceRequest::query()->where('tenant_id',$user->tenant_id),$user);
        $projects=$scopes->apply(Project::query()->where('tenant_id',$user->tenant_id),$user);
        $customers=$scopes->apply(Customer::query()->where('tenant_id',$user->tenant_id),$user);

        $closed=['COMPLETED','CLOSED','CANCELLED'];
        $report=[
            'open_work_orders'=>(clone $workOrders)->whereNotIn('status',$closed)->count(),
            'completed_work_orders'=>(clone $workOrders)->whereIn('status',['COMPLETED','CLOSED'])->count(),
            'emergency_work_orders'=>(clone $workOrders)->whereNotIn('status',$closed)->whereIn('priority',['EMERGENCY','CRITICAL','URGENT'])->count(),
            'open_service_requests'=>(clone $serviceRequests)->whereNotIn('status',['RESOLVED','CLOSED','CANCELLED'])->count(),
            'sla_overdue'=>(clone $serviceRequests)->whereNotIn('status',['RESOLVED','CLOSED','CANCELLED'])->whereNotNull('current_stage_due_at')->where('current_stage_due_at','<',now())->count(),
            'active_projects'=>(clone $projects)->whereNotIn('status',$closed)->count(),
            'active_customers'=>(clone $customers)->where('status','ACTIVE')->count(),
        ];

        $workOrderStatus=(clone $workOrders)->selectRaw('status, COUNT(*) total')->groupBy('status')->orderByDesc('total')->get();
        $requestPriority=(clone $serviceRequests)->selectRaw('priority, COUNT(*) total')->groupBy('priority')->orderByDesc('total')->get();
        $projectStatus=(clone $projects)->selectRaw('status, COUNT(*) total')->groupBy('status')->orderByDesc('total')->get();

        return view('operations.reports',compact('report','workOrderStatus','requestPriority','projectStatus'));
    }
}
