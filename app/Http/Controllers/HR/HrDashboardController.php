<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceEntry,BusinessTrip,Employee,EmployeeDocument,EmploymentContract,LeaveRequest,PayrollRun};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HrDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today=Carbon::today();
        $employees=Employee::with('position')->orderBy('employee_no')->get();
        $active=$employees->where('status','ACTIVE');
        $onLeaveIds=LeaveRequest::where('status','APPROVED')->whereDate('starts_on','<=',$today)->whereDate('ends_on','>=',$today)->pluck('employee_id')->unique();
        $attendanceToday=AttendanceEntry::whereDate('work_date',$today)->get();
        $pendingLeaves=LeaveRequest::where('status','PENDING')->latest()->limit(6)->get();
        $latestPayroll=PayrollRun::with('lines')->latest('period_end')->first();
        $pendingMissions=BusinessTrip::with('employee')->where('status','PENDING')->latest()->limit(5)->get();
        $activeMissions=BusinessTrip::whereIn('status',['APPROVED','IN_PROGRESS'])->count();
        $settlementPending=BusinessTrip::where('status','SETTLEMENT_PENDING')->count();
        $expiringContracts=EmploymentContract::where('status','ACTIVE')->whereNotNull('ends_on')->whereBetween('ends_on',[$today,$today->copy()->addDays(90)])->with('employee')->orderBy('ends_on')->limit(8)->get();
        $expiringDocuments=EmployeeDocument::whereNotNull('expires_on')->whereBetween('expires_on',[$today,$today->copy()->addDays(90)])->with('employee')->orderBy('expires_on')->limit(8)->get();
        $departments=$employees->groupBy(fn($e)=>$e->position?->department ?: 'Unassigned')->map->count()->sortDesc();
        $payrollLiability=$active->sum(fn($e)=>$e->grossMonthlyCompensation());

        return view('hr.dashboard',[
            'stats'=>[
                'total'=>$employees->count(),'active'=>$active->count(),'on_leave'=>$onLeaveIds->count(),
                'present_today'=>$attendanceToday->pluck('employee_id')->unique()->count(),'pending_leave'=>$pendingLeaves->count(),
                'monthly_compensation'=>$payrollLiability,'active_missions'=>$activeMissions,'mission_settlements'=>$settlementPending,
            ],
            'pendingLeaves'=>$pendingLeaves,'latestPayroll'=>$latestPayroll,'pendingMissions'=>$pendingMissions,
            'expiringContracts'=>$expiringContracts,'expiringDocuments'=>$expiringDocuments,'departments'=>$departments,'today'=>$today,
        ]);
    }
}
