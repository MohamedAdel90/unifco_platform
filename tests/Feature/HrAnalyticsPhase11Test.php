<?php

namespace Tests\Feature;

use App\Models\{AttendanceEntry,Employee,HrComplianceCase,HrWorkforcePlan,JobPosition,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrAnalyticsPhase11Test extends TestCase
{
    use RefreshDatabase;

    private function core(): array
    {
        $tenant=Tenant::create(['name'=>'Analytics Tenant','code'=>'ANA','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'ANA-HQ','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'HR Analytics Admin','email'=>'analytics@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $position=JobPosition::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>'OPS','title'=>'Technician','department'=>'Operations','status'=>'ACTIVE']);
        $saudi=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'job_position_id'=>$position->id,'employee_no'=>'SA-100','name'=>'Saudi Worker','nationality'=>'Saudi','status'=>'ACTIVE','hire_date'=>'2024-01-01','contract_type'=>'UNLIMITED','work_location'=>'Riyadh','basic_salary'=>8000,'housing_allowance'=>2000,'transport_allowance'=>500]);
        $expat=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'job_position_id'=>$position->id,'employee_no'=>'EX-100','name'=>'Expat Worker','nationality'=>'Indian','status'=>'ACTIVE','hire_date'=>'2024-01-01','contract_type'=>'FIXED','work_location'=>'Riyadh','basic_salary'=>6000,'housing_allowance'=>1500,'transport_allowance'=>500]);
        AttendanceEntry::create(['tenant_id'=>$tenant->id,'employee_id'=>$saudi->id,'work_date'=>now()->startOfYear()->addDay(),'attendance_type'=>'PRESENT','overtime_hours'=>2,'late_minutes'=>10,'status'=>'APPROVED']);
        AttendanceEntry::create(['tenant_id'=>$tenant->id,'employee_id'=>$expat->id,'work_date'=>now()->startOfYear()->addDays(2),'attendance_type'=>'ABSENT','overtime_hours'=>0,'late_minutes'=>0,'status'=>'APPROVED']);
        HrComplianceCase::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'case_no'=>'HRC-TEST','category'=>'QIWA','severity'=>'HIGH','title'=>'Test risk','description'=>'Test risk','status'=>'OPEN']);
        return compact('tenant','org','admin','position','saudi','expat');
    }

    public function test_hr_analytics_dashboard_calculates_workforce_signals(): void
    {
        $c=$this->core();
        $this->actingAs($c['admin'])->get('/hr/analytics')->assertOk()
            ->assertSee('Workforce Intelligence Command Center',false)
            ->assertSee('50%',false)
            ->assertSee('Operations',false)
            ->assertSee('Riyadh',false)
            ->assertSee('Compliance Risk',false);
    }

    public function test_workforce_plan_can_be_saved_and_compared_to_actuals(): void
    {
        $c=$this->core();
        $this->actingAs($c['admin'])->post('/hr/analytics/workforce-plan',[
            'plan_year'=>now()->year,'department'=>'Operations','target_headcount'=>4,'budgeted_monthly_cost'=>30000,'target_saudi_pct'=>60,'notes'=>'Growth plan',
        ])->assertRedirect();
        $this->assertDatabaseHas('hr_workforce_plans',['department'=>'Operations','target_headcount'=>4,'notes'=>'Growth plan']);
        $this->actingAs($c['admin'])->get('/hr/analytics')->assertOk()->assertSee('Operations',false)->assertSee('60.00%',false)->assertSee('30,000',false);
    }

    public function test_phase11_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.analytics.index'));
        $this->assertTrue(\Route::has('hr.analytics.plans.store'));
    }
}
