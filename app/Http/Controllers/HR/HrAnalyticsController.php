<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceEntry,Employee,EmployeeLeaveBalance,FinalSettlement,HrComplianceCase,HrComplianceProfile,HrWorkforcePlan,JobPosition,LeavePolicy,ManpowerRequisition,PayrollRun,PerformanceReview,RecruitmentVacancy};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HrAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $year=(int)$request->query('year',now()->year);
        if($year<2020 || $year>now()->year+3) $year=now()->year;
        $active=Employee::with('position')->where('status','ACTIVE')->get();
        $all=Employee::with('position')->get();
        $saudis=$active->filter(fn($e)=>$this->isSaudi($e));
        $monthlyCost=$active->sum(fn($e)=>$e->grossMonthlyCompensation());
        $annualizedCost=$monthlyCost*12;

        $attendance=AttendanceEntry::whereYear('work_date',$year)->get();
        $absences=$attendance->where('attendance_type','ABSENT')->count();
        $attendanceRows=max(1,$attendance->count());
        $absenceRate=round($absences/$attendanceRows*100,2);
        $overtimeHours=round((float)$attendance->sum('overtime_hours'),2);
        $lateMinutes=(int)$attendance->sum('late_minutes');

        $settlements=FinalSettlement::whereYear('last_working_day',$year)->get();
        $separations=$settlements->count();
        $avgHeadcount=max(1,$active->count()+($separations/2));
        $turnoverRate=round($separations/$avgHeadcount*100,2);

        $paidPolicies=LeavePolicy::where('paid',true)->pluck('id');
        $balances=EmployeeLeaveBalance::where('balance_year',$year)->whereIn('leave_policy_id',$paidPolicies)->get();
        $employeesById=$active->keyBy('id');
        $leaveDays=0; $leaveLiability=0;
        foreach($balances as $b){
            $available=max(0,$b->availableDays()); $leaveDays+=$available;
            if($e=$employeesById->get($b->employee_id)) $leaveLiability += $available*($e->grossMonthlyCompensation()/30);
        }

        $departmentRows=$active->groupBy(fn($e)=>$e->position?->department ?: 'Unassigned')->map(function($rows,$department){
            $saudi=$rows->filter(fn($e)=>$this->isSaudi($e))->count();
            return ['department'=>$department,'headcount'=>$rows->count(),'saudi'=>$saudi,'saudi_pct'=>$rows->count()?round($saudi/$rows->count()*100,1):0,'monthly_cost'=>round($rows->sum(fn($e)=>$e->grossMonthlyCompensation()),2)];
        })->sortByDesc('headcount')->values();

        $locationRows=$active->groupBy(fn($e)=>$e->work_location ?: 'Unassigned')->map(fn($rows,$location)=>['location'=>$location,'headcount'=>$rows->count(),'monthly_cost'=>round($rows->sum(fn($e)=>$e->grossMonthlyCompensation()),2)])->sortByDesc('headcount')->values();
        $nationalityRows=$active->groupBy(fn($e)=>$e->nationality ?: 'Not Recorded')->map(fn($rows,$name)=>['name'=>$name,'count'=>$rows->count()])->sortByDesc('count')->take(10)->values();
        $contractRows=$active->groupBy(fn($e)=>$e->contract_type ?: 'Not Recorded')->map(fn($rows,$name)=>['name'=>$name,'count'=>$rows->count()])->sortByDesc('count')->values();

        $payrollTrend=PayrollRun::whereYear('period_end',$year)->orderBy('period_end')->get()->groupBy(fn($r)=>$r->period_end->format('Y-m'))->map(fn($rows,$month)=>['month'=>$month,'gross'=>round((float)$rows->sum('gross_total'),2),'net'=>round((float)$rows->sum('net_total'),2),'employer_cost'=>round((float)$rows->sum(fn($r)=>(float)$r->gross_total+(float)$r->employer_contributions_total),2)])->values();

        $performance=PerformanceReview::whereYear('created_at',$year)->whereIn('status',['APPROVED','COMPLETED'])->get();
        $averagePerformance=$performance->count()?round((float)$performance->avg('overall_score'),1):null;
        $openVacancies=RecruitmentVacancy::where('status','OPEN')->count();
        $approvedHeadcount=ManpowerRequisition::whereIn('status',['APPROVED','OPEN','IN_RECRUITMENT'])->sum('headcount');
        $complianceRisk=HrComplianceCase::whereIn('status',['OPEN','IN_PROGRESS'])->count();
        $criticalCompliance=HrComplianceCase::whereIn('status',['OPEN','IN_PROGRESS'])->whereIn('severity',['CRITICAL','HIGH'])->count();
        $profile=HrComplianceProfile::first();

        $plans=HrWorkforcePlan::where('plan_year',$year)->orderBy('department')->get();
        $planAnalysis=$plans->map(function($plan) use($departmentRows,$active){
            if($plan->department==='ALL'){
                $actual=$active->count(); $cost=$active->sum(fn($e)=>$e->grossMonthlyCompensation()); $saudis=$active->filter(fn($e)=>$this->isSaudi($e))->count();
            } else {
                $row=$departmentRows->firstWhere('department',$plan->department); $actual=$row['headcount']??0; $cost=$row['monthly_cost']??0; $saudis=$row['saudi']??0;
            }
            $saudiPct=$actual?round($saudis/$actual*100,1):0;
            return ['plan'=>$plan,'actual_headcount'=>$actual,'headcount_gap'=>$plan->target_headcount-$actual,'actual_monthly_cost'=>round($cost,2),'budget_gap'=>round((float)$plan->budgeted_monthly_cost-$cost,2),'actual_saudi_pct'=>$saudiPct,'saudi_gap'=>$plan->target_saudi_pct===null?null:round((float)$plan->target_saudi_pct-$saudiPct,1)];
        });

        $forecast=$this->forecast($active,$separations,$approvedHeadcount,$openVacancies);

        return view('hr.analytics.index',compact(
            'year','active','all','saudis','monthlyCost','annualizedCost','absenceRate','absences','overtimeHours','lateMinutes','turnoverRate','separations',
            'leaveDays','leaveLiability','departmentRows','locationRows','nationalityRows','contractRows','payrollTrend','averagePerformance','openVacancies','approvedHeadcount',
            'complianceRisk','criticalCompliance','profile','plans','planAnalysis','forecast'
        ));
    }

    public function storePlan(Request $request,AuditService $audit): RedirectResponse
    {
        $d=$request->validate([
            'plan_year'=>['required','integer','min:2020','max:'.(now()->year+3)],'department'=>['required','string','max:160'],
            'target_headcount'=>['required','integer','min:0','max:100000'],'budgeted_monthly_cost'=>['required','numeric','min:0'],
            'target_saudi_pct'=>['nullable','numeric','min:0','max:100'],'notes'=>['nullable','string','max:3000'],
        ]);
        $plan=HrWorkforcePlan::updateOrCreate(
            ['organization_id'=>$request->user()->organization_id,'plan_year'=>$d['plan_year'],'department'=>$d['department']],
            [...$d,'status'=>'ACTIVE','updated_by'=>$request->user()->id,'created_by'=>$request->user()->id]
        );
        $audit->record('hr.analytics.workforce_plan.saved',$plan,[],$plan->toArray());
        return back()->with('status','Workforce plan saved.');
    }

    private function forecast($active,int $separations,int $approvedHeadcount,int $openVacancies): array
    {
        $base=$active->count();
        $monthlyAttrition=$separations/12;
        $pipeline=min($approvedHeadcount,max($approvedHeadcount,$openVacancies));
        $rows=[];
        for($m=1;$m<=6;$m++){
            $plannedHires=$pipeline ? round($pipeline/6*$m,1) : 0;
            $projected=max(0,round($base-$monthlyAttrition*$m+$plannedHires,1));
            $rows[]=['month'=>now()->copy()->addMonths($m)->format('M Y'),'projected_headcount'=>$projected,'planned_hires_to_date'=>$plannedHires,'attrition_to_date'=>round($monthlyAttrition*$m,1)];
        }
        return $rows;
    }

    private function isSaudi(Employee $e): bool
    {
        return in_array(strtolower(trim((string)$e->nationality)),['saudi','saudi arabian','ksa','سعودي','سعودية'],true);
    }
}
