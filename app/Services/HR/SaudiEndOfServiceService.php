<?php

namespace App\Services\HR;

use App\Models\{Employee,EmployeeLeaveBalance,EndOfServicePolicy,LeavePolicy};
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SaudiEndOfServiceService
{
    public const REASONS = [
        'RESIGNATION','CONTRACT_EXPIRY','EMPLOYER_TERMINATION','MUTUAL_AGREEMENT','RETIREMENT','FORCE_MAJEURE','ARTICLE_81','FEMALE_MARRIAGE_OR_BIRTH','ARTICLE_80','DEATH_OR_PERMANENT_INCAPACITY','OTHER',
    ];

    public function activePolicy(int $tenantId, string|Carbon $date): EndOfServicePolicy
    {
        $date=Carbon::parse($date)->toDateString();
        $policy=EndOfServicePolicy::where('tenant_id',$tenantId)->where('status','ACTIVE')->whereDate('effective_from','<=',$date)
            ->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$date))->latest('effective_from')->first();
        if($policy) return $policy;

        return EndOfServicePolicy::create([
            'tenant_id'=>$tenantId,'code'=>'SA-LABOR-LAW','name'=>'Saudi Labor Law EOS Baseline','effective_from'=>'2025-02-19','status'=>'ACTIVE',
            'first_five_years_month_factor'=>0.5,'after_five_years_month_factor'=>1,
            'resignation_two_to_five_multiplier'=>1/3,'resignation_five_to_ten_multiplier'=>2/3,'resignation_ten_plus_multiplier'=>1,
            'standard_month_days'=>30,'include_housing_allowance'=>true,'include_transport_allowance'=>true,'include_other_allowances'=>true,
        ]);
    }

    public function calculate(Employee $employee, EndOfServicePolicy $policy, array $input): array
    {
        $start=Carbon::parse($input['service_start_date'] ?? $employee->hire_date);
        $end=Carbon::parse($input['last_working_day']);
        if($end->lt($start)) throw ValidationException::withMessages(['last_working_day'=>'Last working day cannot be before service start date.']);

        $serviceDays=$start->diffInDays($end)+1;
        $serviceYears=$serviceDays/365.2425;
        $wage=$this->wageBasis($employee,$policy);
        $first=min(5,$serviceYears);
        $after=max(0,$serviceYears-5);
        $base=($wage*(float)$policy->first_five_years_month_factor*$first)+($wage*(float)$policy->after_five_years_month_factor*$after);
        $reason=$input['termination_reason'];
        $multiplier=$this->entitlementMultiplier($reason,$serviceYears,$policy);
        $eos=round($base*$multiplier,2);

        $unusedLeaveDays=array_key_exists('unused_leave_days',$input) ? max(0,(float)$input['unused_leave_days']) : $this->unusedPaidAnnualLeaveDays($employee);
        $dailyRate=$wage/max(1,(float)$policy->standard_month_days);
        $leaveEncashment=round($unusedLeaveDays*$dailyRate,2);
        $unpaidSalary=max(0,(float)($input['unpaid_salary']??0));
        $otherEarnings=max(0,(float)($input['other_earnings']??0));
        $noticeCompensation=max(0,(float)($input['notice_compensation']??0));
        $employeeDebt=max(0,(float)($input['employee_debt']??0));
        $advanceRecovery=max(0,(float)($input['advance_recovery']??0));
        $otherDeductions=max(0,(float)($input['other_deductions']??0));
        $gross=round($eos+$leaveEncashment+$unpaidSalary+$otherEarnings+$noticeCompensation,2);
        $deductions=round($employeeDebt+$advanceRecovery+$otherDeductions,2);

        return [
            'service_start_date'=>$start->toDateString(),'last_working_day'=>$end->toDateString(),'service_years'=>round($serviceYears,6),'last_wage_basis'=>round($wage,2),
            'eos_base_award'=>round($base,2),'eos_multiplier'=>round($multiplier,4),'eos_award'=>$eos,'unused_leave_days'=>round($unusedLeaveDays,2),
            'leave_daily_rate'=>round($dailyRate,4),'leave_encashment'=>$leaveEncashment,'unpaid_salary'=>$unpaidSalary,'other_earnings'=>$otherEarnings,
            'notice_compensation'=>$noticeCompensation,'employee_debt'=>$employeeDebt,'advance_recovery'=>$advanceRecovery,'other_deductions'=>$otherDeductions,
            'gross_entitlements'=>$gross,'total_deductions'=>$deductions,'net_settlement'=>round($gross-$deductions,2),
            'calculation_snapshot'=>[
                'law_basis'=>['article_84'=>'Half-month wage for each of first five years, one month thereafter, fractions pro-rated','article_85'=>'Resignation entitlement tiers','article_87'=>'Full award for force majeure and qualifying female marriage/birth cases','article_111'=>'Accrued unused annual leave wage at termination'],
                'service_days'=>$serviceDays,'first_five_years_portion'=>round($first,6),'after_five_years_portion'=>round($after,6),
                'salary_components'=>['basic'=>(float)$employee->basic_salary,'housing'=>(float)$employee->housing_allowance,'transport'=>(float)$employee->transport_allowance,'other'=>(float)$employee->other_allowances],
                'policy_id'=>$policy->id,'policy_code'=>$policy->code,'termination_reason'=>$reason,
            ],
        ];
    }

    public function wageBasis(Employee $employee, EndOfServicePolicy $policy): float
    {
        $wage=(float)$employee->basic_salary;
        if($policy->include_housing_allowance) $wage+=(float)$employee->housing_allowance;
        if($policy->include_transport_allowance) $wage+=(float)$employee->transport_allowance;
        if($policy->include_other_allowances) $wage+=(float)$employee->other_allowances;
        return $wage;
    }

    private function entitlementMultiplier(string $reason,float $years,EndOfServicePolicy $policy): float
    {
        if($reason==='ARTICLE_80') return 0;
        if($reason!=='RESIGNATION') return 1;
        if($years<2) return 0;
        if($years<=5) return (float)$policy->resignation_two_to_five_multiplier;
        if($years<10) return (float)$policy->resignation_five_to_ten_multiplier;
        return (float)$policy->resignation_ten_plus_multiplier;
    }

    private function unusedPaidAnnualLeaveDays(Employee $employee): float
    {
        $policyIds=LeavePolicy::where('paid',true)->where('leave_type','ANNUAL')->pluck('id');
        if($policyIds->isEmpty()) return 0;
        return EmployeeLeaveBalance::where('employee_id',$employee->id)->whereIn('leave_policy_id',$policyIds)->get()->sum(fn($b)=>max(0,$b->availableDays()));
    }
}
