<?php

namespace Tests\Feature;

use App\Models\{AttendanceEntry,ChartAccount,Employee,Organization,PayrollPolicy,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPayrollPhase3Test extends TestCase
{
    use RefreshDatabase;

    private function seedCore(): array
    {
        $tenant=Tenant::create(['name'=>'PAY Tenant','code'=>'PAY','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'Payroll HQ','code'=>'PAY-HQ','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Payroll Admin','email'=>'pay-admin@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $saudi=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'EMP-SA','name'=>'Saudi Employee','hire_date'=>'2024-01-01','status'=>'ACTIVE','nationality'=>'Saudi','basic_salary'=>10000,'housing_allowance'=>2500,'transport_allowance'=>1000,'other_allowances'=>500]);
        $expat=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'EMP-EX','name'=>'Expat Employee','hire_date'=>'2024-01-01','status'=>'ACTIVE','nationality'=>'Jordanian','basic_salary'=>8000,'housing_allowance'=>2000,'transport_allowance'=>800,'other_allowances'=>200]);
        return [$tenant,$org,$admin,$saudi,$expat];
    }

    public function test_payroll_policy_and_run_calculate_saudi_gosi_and_overtime(): void
    {
        [$tenant,$org,$admin,$saudi,$expat]=$this->seedCore();
        $this->actingAs($admin)->post('/hr/payroll/policies',['code'=>'KSA-GOSI','name'=>'Saudi Standard','effective_from'=>'2026-01-01','pension_employee_rate'=>0.09,'pension_employer_rate'=>0.09,'saned_employee_rate'=>0.01,'saned_employer_rate'=>0.01,'occupational_hazard_employer_rate'=>0.02,'overtime_basic_premium_rate'=>0.50,'standard_month_days'=>30])->assertRedirect();
        AttendanceEntry::create(['tenant_id'=>$tenant->id,'employee_id'=>$saudi->id,'work_date'=>'2026-08-10','worked_hours'=>10,'overtime_hours'=>2,'status'=>'RECORDED']);
        $response=$this->actingAs($admin)->post('/hr/payroll/runs',['payroll_no'=>'PAY-2026-08','period_start'=>'2026-08-01','period_end'=>'2026-08-31','posting_date'=>'2026-08-31','currency'=>'SAR']);
        $run=\App\Models\PayrollRun::where('payroll_no','PAY-2026-08')->firstOrFail();
        $response->assertRedirect(route('hr.payroll.show',$run));
        $saudiLine=$run->lines()->where('employee_id',$saudi->id)->firstOrFail();
        $expatLine=$run->lines()->where('employee_id',$expat->id)->firstOrFail();
        $this->assertSame(12500.0,(float)$saudiLine->gosi_contributory_wage);
        $this->assertSame(1125.0,(float)$saudiLine->gosi_pension_employee);
        $this->assertSame(125.0,(float)$saudiLine->gosi_saned_employee);
        $this->assertSame(0.0,(float)$expatLine->gosi_pension_employee);
        $this->assertSame(200.0,(float)$expatLine->gosi_hazard_employer);
        $this->assertGreaterThan(0,(float)$saudiLine->overtime_pay);
        $this->actingAs($admin)->get('/hr/payroll/'.$run->id)->assertOk()->assertSee('Employee Payroll Lines',false)->assertSee('Saudi Employee',false);
    }

    public function test_payroll_workspace_and_routes_render(): void
    {
        [,,$admin]=$this->seedCore();
        $this->actingAs($admin)->get('/hr/payroll')->assertOk()->assertSee('Payroll Command Center',false)->assertSee('Saudi Payroll Policy',false);
        $this->assertTrue(\Route::has('hr.payroll.index'));
        $this->assertTrue(\Route::has('hr.payroll.runs.store'));
        $this->assertTrue(\Route::has('hr.payroll.post'));
    }
}
