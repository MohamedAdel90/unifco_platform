<?php

namespace Tests\Feature;

use App\Models\{BusinessTrip,Employee,EmployeeGoal,EmployeeLeaveBalance,LeavePolicy,LeaveRequest,Organization,PayrollLine,PayrollRun,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrSelfServicePhase8Test extends TestCase
{
    use RefreshDatabase;

    private function core(): array
    {
        $tenant=Tenant::create(['name'=>'ESS Tenant','code'=>'ESS','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'ESS-HQ','status'=>'ACTIVE']);
        $managerEmployee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'ESS-MGR','name'=>'Manager Employee','status'=>'ACTIVE','basic_salary'=>15000]);
        $employee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'manager_employee_id'=>$managerEmployee->id,'employee_no'=>'ESS-001','name'=>'Direct Report','status'=>'ACTIVE','basic_salary'=>8000]);
        $outsider=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'ESS-999','name'=>'Outside Team','status'=>'ACTIVE','basic_salary'=>30000]);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_id'=>$managerEmployee->id,'name'=>'Manager User','email'=>'manager-ess@example.test','password'=>'password','role'=>'MANAGER','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_id'=>$employee->id,'name'=>'Employee User','email'=>'employee-ess@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $outsiderUser=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_id'=>$outsider->id,'name'=>'Outside User','email'=>'outside-ess@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        return compact('tenant','org','managerEmployee','employee','outsider','manager','user','outsiderUser');
    }

    public function test_employee_portal_is_scoped_to_linked_employee_and_own_payroll(): void
    {
        $c=$this->core();
        $run=PayrollRun::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'payroll_no'=>'PAY-ESS-1','period_start'=>'2026-08-01','period_end'=>'2026-08-31','posting_date'=>'2026-08-31','currency'=>'SAR','status'=>'CALCULATED']);
        PayrollLine::create(['payroll_run_id'=>$run->id,'employee_id'=>$c['employee']->id,'basic_pay'=>8000,'allowances'=>1000,'deductions'=>500,'net_pay'=>8500,'calculation_status'=>'CALCULATED']);
        PayrollLine::create(['payroll_run_id'=>$run->id,'employee_id'=>$c['outsider']->id,'basic_pay'=>30000,'allowances'=>5000,'deductions'=>1000,'net_pay'=>34000,'calculation_status'=>'CALCULATED']);
        $this->actingAs($c['user'])->get('/hr/self-service')->assertOk()->assertSee('My HR',false)->assertSee('8,500 SAR',false)->assertDontSee('34,000 SAR',false)->assertDontSee('Outside Team',false);
    }

    public function test_employee_can_submit_leave_against_own_balance_only(): void
    {
        $c=$this->core();
        $policy=LeavePolicy::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'code'=>'ANNUAL','name'=>'Annual Leave','leave_type'=>'ANNUAL','annual_entitlement_days'=>21,'accrual_method'=>'ANNUAL','carry_forward_limit_days'=>5,'requires_approval'=>true,'paid'=>true,'status'=>'ACTIVE']);
        EmployeeLeaveBalance::create(['tenant_id'=>$c['tenant']->id,'employee_id'=>$c['employee']->id,'leave_policy_id'=>$policy->id,'balance_year'=>2026,'opening_days'=>21]);
        $this->actingAs($c['user'])->post('/hr/self-service/leave',['leave_policy_id'=>$policy->id,'starts_on'=>'2026-09-01','ends_on'=>'2026-09-03','day_portion'=>'FULL_DAY','reason'=>'Family'])->assertRedirect();
        $leave=LeaveRequest::firstOrFail();
        $this->assertSame($c['employee']->id,$leave->employee_id);
        $this->assertSame('PENDING',$leave->status);
        $this->assertSame(3.0,(float)$leave->days);
    }

    public function test_employee_can_submit_mission_and_update_only_own_goal(): void
    {
        $c=$this->core();
        $this->actingAs($c['user'])->post('/hr/self-service/missions',['trip_type'=>'LOCAL','purpose'=>'Customer site support','destination_city'=>'Riyadh','destination_country'=>'Saudi Arabia','starts_on'=>'2026-09-10','ends_on'=>'2026-09-11','requested_advance'=>500])->assertRedirect();
        $trip=BusinessTrip::firstOrFail();
        $this->assertSame($c['employee']->id,$trip->employee_id);
        $this->assertSame('PENDING',$trip->status);
        $goal=EmployeeGoal::create(['tenant_id'=>$c['tenant']->id,'employee_id'=>$c['employee']->id,'title'=>'Close PM backlog','weight'=>40,'target_value'=>100,'status'=>'OPEN']);
        $otherGoal=EmployeeGoal::create(['tenant_id'=>$c['tenant']->id,'employee_id'=>$c['outsider']->id,'title'=>'Executive target','weight'=>50,'target_value'=>100,'status'=>'OPEN']);
        $this->actingAs($c['user'])->post('/hr/self-service/goals/'.$goal->id,['actual_value'=>60,'status'=>'IN_PROGRESS'])->assertRedirect();
        $this->assertSame(60.0,(float)$goal->fresh()->actual_value);
        $this->actingAs($c['user'])->post('/hr/self-service/goals/'.$otherGoal->id,['actual_value'=>90,'status'=>'IN_PROGRESS'])->assertNotFound();
    }

    public function test_manager_portal_exposes_direct_reports_but_not_outside_team_or_payroll(): void
    {
        $c=$this->core();
        $this->actingAs($c['manager'])->get('/hr/manager-self-service')->assertOk()->assertSee('Direct Report',false)->assertDontSee('Outside Team',false)->assertDontSee('30,000',false);
    }

    public function test_manager_can_decide_direct_report_leave_but_cannot_touch_outside_team(): void
    {
        $c=$this->core();
        $policy=LeavePolicy::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'code'=>'ANN','name'=>'Annual','leave_type'=>'ANNUAL','annual_entitlement_days'=>21,'accrual_method'=>'ANNUAL','carry_forward_limit_days'=>0,'requires_approval'=>true,'paid'=>true,'status'=>'ACTIVE']);
        foreach([$c['employee'],$c['outsider']] as $e) EmployeeLeaveBalance::create(['tenant_id'=>$c['tenant']->id,'employee_id'=>$e->id,'leave_policy_id'=>$policy->id,'balance_year'=>2026,'opening_days'=>21]);
        $mine=LeaveRequest::create(['tenant_id'=>$c['tenant']->id,'employee_id'=>$c['employee']->id,'leave_policy_id'=>$policy->id,'leave_type'=>'ANNUAL','starts_on'=>'2026-09-01','ends_on'=>'2026-09-02','days'=>2,'day_portion'=>'FULL_DAY','status'=>'PENDING','requested_by'=>$c['user']->id]);
        $outside=LeaveRequest::create(['tenant_id'=>$c['tenant']->id,'employee_id'=>$c['outsider']->id,'leave_policy_id'=>$policy->id,'leave_type'=>'ANNUAL','starts_on'=>'2026-09-01','ends_on'=>'2026-09-02','days'=>2,'day_portion'=>'FULL_DAY','status'=>'PENDING','requested_by'=>$c['outsiderUser']->id]);
        $this->actingAs($c['manager'])->post('/hr/manager-self-service/leave/'.$mine->id.'/decide',['decision'=>'APPROVED'])->assertRedirect();
        $this->assertSame('APPROVED',$mine->fresh()->status);
        $this->actingAs($c['manager'])->post('/hr/manager-self-service/leave/'.$outside->id.'/decide',['decision'=>'APPROVED'])->assertForbidden();
        $this->assertSame('PENDING',$outside->fresh()->status);
    }

    public function test_unlinked_user_cannot_open_employee_self_service(): void
    {
        $c=$this->core();
        $unlinked=User::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'name'=>'Unlinked','email'=>'unlinked@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $this->actingAs($unlinked)->get('/hr/self-service')->assertForbidden();
    }

    public function test_phase8_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.self-service.index'));
        $this->assertTrue(\Route::has('hr.self-service.leave.store'));
        $this->assertTrue(\Route::has('hr.manager-self-service.index'));
        $this->assertTrue(\Route::has('hr.manager-self-service.leave.decide'));
    }
}
