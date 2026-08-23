<?php

namespace App\Services\HR;

use App\Models\{AttendanceEntry,Employee,LeaveRequest,PayrollPolicy,PayrollRun,WorkSchedule};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaudiPayrollService
{
    public function activePolicy(int $tenantId, Carbon|string $date): PayrollPolicy
    {
        $date=$date instanceof Carbon?$date->toDateString():(string)$date;
        return PayrollPolicy::where('tenant_id',$tenantId)->where('status','ACTIVE')->whereDate('effective_from','<=',$date)->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$date))->orderByDesc('effective_from')->firstOrFail();
    }

    public function calculateEmployee(Employee $employee, PayrollPolicy $policy, Carbon|string $start, Carbon|string $end): array
    {
        $start=Carbon::parse($start); $end=Carbon::parse($end);
        $basic=(float)$employee->basic_salary; $housing=(float)$employee->housing_allowance; $transport=(float)$employee->transport_allowance; $other=(float)$employee->other_allowances;
        $actualMonthly=$basic+$housing+$transport+$other;
        $schedule=$employee->getAttribute('work_schedule_id')?WorkSchedule::find($employee->getAttribute('work_schedule_id')):null;
        $dailyHours=(float)($schedule?->daily_hours ?: 8); if($dailyHours<=0)$dailyHours=8;
        $overtimeHours=(float)AttendanceEntry::where('employee_id',$employee->id)->whereBetween('work_date',[$start->toDateString(),$end->toDateString()])->sum('overtime_hours');
        $hourlyActual=$actualMonthly/(float)$policy->standard_month_days/$dailyHours; $hourlyBasic=$basic/(float)$policy->standard_month_days/$dailyHours;
        $overtimePay=round($overtimeHours*($hourlyActual+((float)$policy->overtime_basic_premium_rate*$hourlyBasic)),2);
        $unpaidDays=(float)LeaveRequest::where('employee_id',$employee->id)->where('status','APPROVED')->whereBetween('starts_on',[$start->toDateString(),$end->toDateString()])->where(function($q){$q->where('leave_type','UNPAID')->orWhereHas('policy',fn($p)=>$p->where('paid',false));})->sum('days');
        $unpaidDeduction=round(($actualMonthly/(float)$policy->standard_month_days)*$unpaidDays,2);
        $isSaudi=in_array(strtoupper(trim((string)$employee->nationality)),['SAUDI','SAUDI ARABIAN','KSA'],true); $gosiWage=round($basic+$housing,2);
        $pensionEmployee=$isSaudi?round($gosiWage*(float)$policy->pension_employee_rate,2):0; $sanedEmployee=$isSaudi?round($gosiWage*(float)$policy->saned_employee_rate,2):0;
        $pensionEmployer=$isSaudi?round($gosiWage*(float)$policy->pension_employer_rate,2):0; $sanedEmployer=$isSaudi?round($gosiWage*(float)$policy->saned_employer_rate,2):0; $hazardEmployer=round($gosiWage*(float)$policy->occupational_hazard_employer_rate,2);
        $employeeDeductions=round($pensionEmployee+$sanedEmployee+$unpaidDeduction,2); $employerContributions=round($pensionEmployer+$sanedEmployer+$hazardEmployer,2);
        $gross=round($actualMonthly+$overtimePay,2); $net=round($gross-$employeeDeductions,2); if($net<0) throw ValidationException::withMessages(['payroll'=>'Calculated net pay cannot be negative.']);
        return compact('basic','housing','transport','other','overtimeHours','overtimePay','unpaidDays','unpaidDeduction','gosiWage','pensionEmployee','sanedEmployee','pensionEmployer','sanedEmployer','hazardEmployer','employeeDeductions','employerContributions','gross','net');
    }

    public function generate(PayrollRun $run, PayrollPolicy $policy): PayrollRun
    {
        return DB::transaction(function() use($run,$policy){$run->lines()->delete();$employees=Employee::where('status','ACTIVE')->where('organization_id',$run->organization_id)->orderBy('employee_no')->get();foreach($employees as $employee){$c=$this->calculateEmployee($employee,$policy,$run->period_start,$run->period_end);$run->lines()->create(['employee_id'=>$employee->id,'basic_pay'=>$c['basic'],'housing_allowance'=>$c['housing'],'transport_allowance'=>$c['transport'],'other_allowances'=>$c['other'],'allowances'=>$c['housing']+$c['transport']+$c['other'],'overtime_hours'=>$c['overtimeHours'],'overtime_pay'=>$c['overtimePay'],'unpaid_leave_days'=>$c['unpaidDays'],'unpaid_leave_deduction'=>$c['unpaidDeduction'],'gosi_contributory_wage'=>$c['gosiWage'],'gosi_pension_employee'=>$c['pensionEmployee'],'gosi_saned_employee'=>$c['sanedEmployee'],'gosi_pension_employer'=>$c['pensionEmployer'],'gosi_saned_employer'=>$c['sanedEmployer'],'gosi_hazard_employer'=>$c['hazardEmployer'],'employee_deductions_total'=>$c['employeeDeductions'],'employer_contributions_total'=>$c['employerContributions'],'deductions'=>$c['employeeDeductions'],'net_pay'=>$c['net'],'calculation_status'=>'CALCULATED']);}$run->load('lines');$run->update(['payroll_policy_id'=>$policy->id,'gross_total'=>round($run->lines->sum(fn($l)=>(float)$l->basic_pay+(float)$l->allowances+(float)$l->overtime_pay),2),'employee_deductions_total'=>round($run->lines->sum('employee_deductions_total'),2),'employer_contributions_total'=>round($run->lines->sum('employer_contributions_total'),2),'net_total'=>round($run->lines->sum('net_pay'),2),'status'=>'DRAFT']);return $run->fresh('lines');});
    }
}
