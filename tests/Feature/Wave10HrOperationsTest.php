<?php

namespace Tests\Feature;

use App\Models\{ChartAccount,Employee,FiscalPeriod,LeaveRequest,PayrollRun,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Wave10HrOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_position_attendance_and_leave_workflow(): void
    {
        $this->seed(); $admin=User::firstOrFail();
        $employee=Employee::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'employee_no'=>'E-100','name'=>'Employee 100','status'=>'ACTIVE']);
        $this->actingAs($admin)->post('/hr/operations/positions',['code'=>'ENG','title'=>'Engineer','department'=>'Operations'])->assertRedirect();
        $positionId=(int)\DB::table('job_positions')->value('id');
        $this->actingAs($admin)->post("/hr/operations/employees/{$employee->id}/position",['job_position_id'=>$positionId])->assertRedirect();
        $this->actingAs($admin)->post('/hr/operations/attendance',['employee_id'=>$employee->id,'work_date'=>'2026-08-15','worked_hours'=>8,'overtime_hours'=>2])->assertRedirect();
        $this->actingAs($admin)->post('/hr/operations/leave',['employee_id'=>$employee->id,'leave_type'=>'ANNUAL','starts_on'=>'2026-08-20','ends_on'=>'2026-08-21','days'=>2])->assertRedirect();
        $leave=LeaveRequest::firstOrFail();
        $this->actingAs($admin)->post("/hr/operations/leave/{$leave->id}/decide",['decision'=>'APPROVED'])->assertSessionHasErrors('leave');
        $approver=User::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'name'=>'HR Approver','email'=>'hr.approver@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $this->actingAs($approver)->post("/hr/operations/leave/{$leave->id}/decide",['decision'=>'APPROVED'])->assertRedirect();
        $this->assertDatabaseHas('leave_requests',['id'=>$leave->id,'status'=>'APPROVED']);
        $this->assertDatabaseHas('attendance_entries',['employee_id'=>$employee->id,'worked_hours'=>8]);
    }

    public function test_payroll_posts_balanced_journal_with_segregation_of_duties(): void
    {
        $this->seed(); $creator=User::firstOrFail();
        $employee=Employee::create(['tenant_id'=>$creator->tenant_id,'organization_id'=>$creator->organization_id,'employee_no'=>'E-200','name'=>'Employee 200','status'=>'ACTIVE']);
        FiscalPeriod::create(['organization_id'=>$creator->organization_id,'code'=>'2026-08','starts_on'=>'2026-08-01','ends_on'=>'2026-08-31','status'=>'OPEN']);
        foreach ([['6100','Payroll Expense','EXPENSE','DEBIT'],['2100','Payroll Payable','LIABILITY','CREDIT'],['2200','Payroll Deductions','LIABILITY','CREDIT']] as [$code,$name,$type,$normal])
            ChartAccount::create(['organization_id'=>$creator->organization_id,'code'=>$code,'name'=>$name,'type'=>$type,'normal_balance'=>$normal,'posting_allowed'=>true,'status'=>'ACTIVE']);

        $this->actingAs($creator)->post('/hr/operations/payroll',[
            'payroll_no'=>'PAY-2026-08','period_start'=>'2026-08-01','period_end'=>'2026-08-31','posting_date'=>'2026-08-31','currency'=>'USD',
            'lines'=>[['employee_id'=>$employee->id,'basic_pay'=>1000,'allowances'=>100,'deductions'=>150]],
        ])->assertRedirect();
        $run=PayrollRun::firstOrFail();
        $this->actingAs($creator)->post("/hr/operations/payroll/{$run->id}/post",['expense_account_code'=>'6100','payable_account_code'=>'2100','deductions_account_code'=>'2200'])->assertSessionHasErrors('payroll');

        $poster=User::create(['tenant_id'=>$creator->tenant_id,'organization_id'=>$creator->organization_id,'name'=>'Payroll Poster','email'=>'payroll.poster@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $this->actingAs($poster)->post("/hr/operations/payroll/{$run->id}/post",['expense_account_code'=>'6100','payable_account_code'=>'2100','deductions_account_code'=>'2200'])->assertRedirect();
        $this->assertDatabaseHas('payroll_runs',['id'=>$run->id,'status'=>'POSTED']);
        $this->assertDatabaseHas('journals',['journal_no'=>'PAYROLL-PAY-2026-08','status'=>'POSTED']);
        $this->assertDatabaseHas('journal_lines',['account_code'=>'2100','credit'=>950]);
        $this->assertDatabaseHas('journal_lines',['account_code'=>'2200','credit'=>150]);
    }
}
