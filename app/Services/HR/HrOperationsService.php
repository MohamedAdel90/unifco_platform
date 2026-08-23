<?php

namespace App\Services\HR;

use App\Models\{ChartAccount,Journal,LeaveRequest,PayrollRun};
use App\Services\AuditService;
use App\Services\Finance\JournalPostingService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class HrOperationsService
{
    public function __construct(private AuditService $audit, private JournalPostingService $posting) {}

    public function decideLeave(LeaveRequest $leave, string $decision): LeaveRequest
    {
        if ($leave->status !== 'PENDING') throw ValidationException::withMessages(['leave'=>'Only pending leave requests can be decided.']);
        if ((int)$leave->requested_by === (int)Auth::id()) throw ValidationException::withMessages(['leave'=>'Segregation of duties: requester cannot decide their own leave.']);
        if (!in_array($decision,['APPROVED','REJECTED'],true)) throw ValidationException::withMessages(['decision'=>'Unsupported decision.']);
        $before=$leave->toArray();
        $leave->update(['status'=>$decision,'decided_by'=>Auth::id(),'decided_at'=>now()]);
        $this->audit->record('hr.leave.'.strtolower($decision),$leave,$before,$leave->fresh()->toArray());
        return $leave->fresh();
    }

    public function postPayroll(PayrollRun $run, string $expenseAccount, string $payableAccount, string $deductionsAccount): PayrollRun
    {
        if ($run->status !== 'DRAFT') throw ValidationException::withMessages(['payroll'=>'Only DRAFT payroll runs can be posted.']);
        if ((int)$run->created_by === (int)Auth::id()) throw ValidationException::withMessages(['payroll'=>'Segregation of duties: payroll creator cannot post the same run.']);
        $run->load('lines');
        if ($run->lines->isEmpty()) throw ValidationException::withMessages(['payroll'=>'Payroll run requires at least one line.']);
        foreach ([$expenseAccount,$payableAccount,$deductionsAccount] as $code) {
            if (!ChartAccount::where('code',$code)->where('status','ACTIVE')->where('posting_allowed',true)->exists()) throw ValidationException::withMessages(['accounts'=>"Posting account {$code} is invalid."]);
        }

        return DB::transaction(function () use ($run,$expenseAccount,$payableAccount,$deductionsAccount) {
            $gross=(float)$run->lines->sum(fn($l)=>(float)$l->basic_pay+(float)$l->allowances+(float)($l->overtime_pay??0));
            $employeeDeductions=(float)$run->lines->sum('deductions');
            $employerContributions=(float)$run->lines->sum('employer_contributions_total');
            $net=round($gross-$employeeDeductions,2);
            if ($gross <= 0 || $net < 0) throw ValidationException::withMessages(['payroll'=>'Payroll totals are invalid.']);

            $journal=Journal::create(['organization_id'=>$run->organization_id,'created_by'=>$run->created_by,'journal_no'=>'PAYROLL-'.$run->payroll_no,'journal_date'=>$run->posting_date,'description'=>'Payroll '.$run->payroll_no,'status'=>'DRAFT']);
            $lines=[
                ['line_no'=>1,'account_code'=>$expenseAccount,'debit'=>round($gross+$employerContributions,2),'credit'=>0,'description'=>'Payroll gross and employer statutory expense'],
                ['line_no'=>2,'account_code'=>$payableAccount,'debit'=>0,'credit'=>$net,'description'=>'Net payroll payable'],
            ];
            $liabilities=round($employeeDeductions+$employerContributions,2);
            if ($liabilities > 0) $lines[]=['line_no'=>3,'account_code'=>$deductionsAccount,'debit'=>0,'credit'=>$liabilities,'description'=>'Payroll statutory deductions and employer contributions payable'];
            $journal->lines()->createMany($lines);
            $this->posting->post($journal);
            $before=$run->toArray();
            $run->update(['status'=>'POSTED','approved_by'=>Auth::id(),'journal_id'=>$journal->id]);
            $this->audit->record('hr.payroll.posted',$run,$before,$run->fresh('lines')->toArray());
            return $run->fresh('lines');
        });
    }
}
