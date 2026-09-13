<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\{Customer,CustomerSite,Employee,Project,WorkOrder,WorkOrderAssignment};
use App\Services\ScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectSiteOperationsController extends Controller
{
    public function __invoke(Request $request, ScopeService $scopes): View
    {
        $user=$request->user();
        $projects=$scopes->apply(Project::query()->where('tenant_id',$user->tenant_id),$user)
            ->orderByRaw("CASE status WHEN 'ACTIVE' THEN 1 WHEN 'IN_PROGRESS' THEN 2 ELSE 3 END")
            ->orderBy('name')->get();
        $customers=$scopes->apply(Customer::query()->where('tenant_id',$user->tenant_id),$user)
            ->where('status','ACTIVE')->orderBy('name')->get();
        $customerIds=$customers->pluck('id');
        $sites=CustomerSite::query()->whereIn('customer_id',$customerIds)->where('status','ACTIVE')->orderBy('name')->get();

        $workOrders=$scopes->apply(WorkOrder::query()->where('tenant_id',$user->tenant_id),$user)
            ->whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->get(['id','asset_id','priority','status']);
        $assignments=WorkOrderAssignment::query()->where('tenant_id',$user->tenant_id)
            ->whereIn('work_order_id',$workOrders->pluck('id'))
            ->whereIn('dispatch_status',['DISPATCHED','ACCEPTED','ARRIVED','IN_PROGRESS'])
            ->get();
        $employeeIds=$assignments->pluck('employee_id')->unique();
        $employees=Employee::query()->where('tenant_id',$user->tenant_id)->whereIn('id',$employeeIds)->get()->keyBy('id');
        $utilization=$assignments->groupBy('employee_id')->map(function($rows,$employeeId) use($employees){
            $employee=$employees->get((int)$employeeId);
            return [
                'employee'=>$employee?->name ?: 'Employee #'.$employeeId,
                'active_assignments'=>$rows->count(),
                'in_progress'=>$rows->where('dispatch_status','IN_PROGRESS')->count(),
                'arrived'=>$rows->where('dispatch_status','ARRIVED')->count(),
            ];
        })->sortByDesc('active_assignments')->values()->take(12);

        $metrics=[
            'projects'=>$projects->count(),
            'customers'=>$customers->count(),
            'sites'=>$sites->count(),
            'open_work_orders'=>$workOrders->count(),
            'field_assignments'=>$assignments->count(),
            'active_technicians'=>$employeeIds->count(),
        ];

        return view('operations.project-sites',compact('projects','customers','sites','utilization','metrics'));
    }
}
