<?php

namespace Tests\Feature;

use App\Models\{Employee,EmployeeLeaveBalance,LeavePolicy,Organization,Tenant,User,WorkSchedule};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HrAttendanceLeavePhase2Test extends TestCase
{
    use RefreshDatabase;

    private function seedCore(): array
    {
        $tenant=Tenant::create(['name'=>'HR2 Tenant','code'=>'HR2','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'UNIFCO HR2','code'=>'HR2-HQ','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'HR2 Admin','email'=>'hr2@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $employee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'EMP-HR2','name'=>'Phase Two Employee','hire_date'=>'2024-01-01','status'=>'ACTIVE']);
        return [$tenant,$org,$admin,$employee];
    }

    public function test_attendance_workspace_calculates_late_minutes_and_overtime(): void
    {
        [$tenant,$org,$admin,$employee]=$this->seedCore();
        $schedule=WorkSchedule::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>'STD','name'=>'Standard','starts_at'=>'08:00:00','ends_at'=>'17:00:00','break_minutes'=>60,'grace_minutes'=>10,'daily_hours'=>8,'working_days'=>[0,1,2,3,4],'ramadan_mode'=>false,'ramadan_daily_hours'=>6,'status'=>'ACTIVE']);
        DB::table('employees')->where('id',$employee->id)->update(['work_schedule_id'=>$schedule->id]);

        $this->actingAs($admin)->post('/hr/attendance',[
            'employee_id'=>$employee->id,'work_date'=>'2026-08-23','attendance_type'=>'PRESENT','check_in_at'=>'08:25','check_out_at'=>'18:00','source'=>'DEVICE','notes'=>'Device import',
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_entries',['tenant_id'=>$tenant->id,'employee_id'=>$employee->id,'work_date'=>'2026-08-23','late_minutes'=>15,'attendance_type'=>'PRESENT','source'=>'DEVICE']);
        $entry=DB::table('attendance_entries')->where('employee_id',$employee->id)->first();
        $this->assertGreaterThan(0,(float)$entry->overtime_hours);
        $this->actingAs($admin)->get('/hr/attendance?date=2026-08-23')->assertOk()->assertSee('Attendance & Time',false)->assertSee('15m',false);
    }

    public function test_ramadan_schedule_uses_reduced_daily_target(): void
    {
        [$tenant,$org,$admin,$employee]=$this->seedCore();
        $schedule=WorkSchedule::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>'RAM','name'=>'Ramadan','starts_at'=>'09:00:00','ends_at'=>'15:00:00','break_minutes'=>0,'grace_minutes'=>0,'daily_hours'=>8,'working_days'=>[0,1,2,3,4],'ramadan_mode'=>true,'ramadan_daily_hours'=>6,'status'=>'ACTIVE']);
        DB::table('employees')->where('id',$employee->id)->update(['work_schedule_id'=>$schedule->id]);
        $this->actingAs($admin)->post('/hr/attendance',['employee_id'=>$employee->id,'work_date'=>'2026-03-01','attendance_type'=>'PRESENT','check_in_at'=>'09:00','check_out_at'=>'16:00','source'=>'MANUAL'])->assertRedirect();
        $this->assertDatabaseHas('attendance_entries',['employee_id'=>$employee->id,'work_date'=>'2026-03-01','worked_hours'=>7,'overtime_hours'=>1]);
    }

    public function test_leave_policy_balance_accrual_request_and_approval(): void
    {
        [$tenant,$org,$admin,$employee]=$this->seedCore();
        $this->actingAs($admin)->post('/hr/leave/policies',[
            'code'=>'ANNUAL','name'=>'Annual Leave','leave_type'=>'ANNUAL','annual_entitlement_days'=>24,'accrual_method'=>'MONTHLY','carry_forward_limit_days'=>5,'requires_approval'=>1,'paid'=>1,
        ])->assertRedirect();
        $policy=LeavePolicy::where('code','ANNUAL')->firstOrFail();
        $this->actingAs($admin)->post('/hr/leave/balances',['employee_id'=>$employee->id,'leave_policy_id'=>$policy->id,'balance_year'=>2026,'opening_days'=>10,'carried_forward_days'=>9])->assertRedirect();
        $balance=EmployeeLeaveBalance::firstOrFail();
        $this->assertSame(5.0,(float)$balance->carried_forward_days);

        $this->actingAs($admin)->post('/hr/leave/balances/'.$balance->id.'/accrue',['months'=>1])->assertRedirect();
        $this->assertSame(2.0,(float)$balance->fresh()->accrued_days);

        $this->actingAs($admin)->post('/hr/leave',['employee_id'=>$employee->id,'leave_policy_id'=>$policy->id,'starts_on'=>'2026-09-01','ends_on'=>'2026-09-05','days'=>5,'day_portion'=>'FULL_DAY','reason'=>'Annual leave'])->assertRedirect();
        $leave=\App\Models\LeaveRequest::firstOrFail();
        $this->assertSame('PENDING',$leave->status);
        $this->actingAs($admin)->post('/hr/leave/'.$leave->id.'/decide',['decision'=>'APPROVED','decision_notes'=>'Approved'])->assertRedirect();
        $this->assertSame('APPROVED',$leave->fresh()->status);
        $this->assertSame(5.0,(float)$balance->fresh()->used_days);
        $this->actingAs($admin)->get('/hr/leave')->assertOk()->assertSee('Leave Management',false)->assertSee('ANNUAL',false);
    }

    public function test_phase2_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.attendance.index'));
        $this->assertTrue(\Route::has('hr.attendance.schedules.store'));
        $this->assertTrue(\Route::has('hr.leave.index'));
        $this->assertTrue(\Route::has('hr.leave.policies.store'));
        $this->assertTrue(\Route::has('hr.leave.balances.accrue'));
    }
}
