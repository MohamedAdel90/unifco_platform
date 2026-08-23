<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceEntry,BusinessTrip,Employee,EmployeeDocument,EmployeeOnboardingTask,EmploymentContract,EmploymentOffer,FinalSettlement,LeaveRequest,ManpowerRequisition,PayrollRun,RecruitmentCandidate,RecruitmentVacancy};
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
        $missionSettlementPending=BusinessTrip::where('status','SETTLEMENT_PENDING')->count();
        $pendingFinalSettlements=FinalSettlement::with('employee')->where('status','PENDING_APPROVAL')->latest()->limit(5)->get();
        $finalSettlementLiability=(float)FinalSettlement::whereIn('status',['PENDING_APPROVAL','APPROVED'])->sum('net_settlement');
        $pendingRequisitions=ManpowerRequisition::where('status','PENDING_APPROVAL')->count();
        $openVacancies=RecruitmentVacancy::where('status','OPEN')->count();
        $activeCandidates=RecruitmentCandidate::whereNotIn('stage',['REJECTED','WITHDRAWN','HIRED'])->count();
        $pendingOnboarding=EmployeeOnboardingTask::where('status','PENDING')->count();
        $recentCandidates=RecruitmentCandidate::with('vacancy')->whereNotIn('stage',['REJECTED','WITHDRAWN','HIRED'])->latest()->limit(5)->get();
        $expiringContracts=EmploymentContract::where('status','ACTIVE')->whereNotNull('ends_on')->whereBetween('ends_on',[$today,$today->copy()->addDays(90)])->with('employee')->orderBy('ends_on')->limit(8)->get();
        $expiringDocuments=EmployeeDocument::whereNotNull('expires_on')->whereBetween('expires_on',[$today,$today->copy()->addDays(90)])->with('employee')->orderBy('expires_on')->limit(8)->get();
        $departments=$employees->groupBy(fn($e)=>$e->position?->department ?: 'Unassigned')->map->count()->sortDesc();
        $payrollLiability=$active->sum(fn($e)=>$e->grossMonthlyCompensation());

        return view('hr.dashboard',[
            'stats'=>[
                'total'=>$employees->count(),'active'=>$active->count(),'on_leave'=>$onLeaveIds->count(),
                'present_today'=>$attendanceToday->pluck('employee_id')->unique()->count(),'pending_leave'=>$pendingLeaves->count(),
                'monthly_compensation'=>$payrollLiability,'active_missions'=>$activeMissions,'mission_settlements'=>$missionSettlementPending,
                'pending_final_settlements'=>$pendingFinalSettlements->count(),'final_settlement_liability'=>$finalSettlementLiability,
                'pending_requisitions'=>$pendingRequisitions,'open_vacancies'=>$openVacancies,'active_candidates'=>$activeCandidates,'pending_onboarding'=>$pendingOnboarding,
            ],
            'pendingLeaves'=>$pendingLeaves,'latestPayroll'=>$latestPayroll,'pendingMissions'=>$pendingMissions,'pendingFinalSettlements'=>$pendingFinalSettlements,'recentCandidates'=>$recentCandidates,
            'expiringContracts'=>$expiringContracts,'expiringDocuments'=>$expiringDocuments,'departments'=>$departments,'today'=>$today,
        ]);
    }
}
