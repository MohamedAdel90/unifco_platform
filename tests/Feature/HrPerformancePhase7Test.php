<?php

namespace Tests\Feature;

use App\Models\{CareerAction,Employee,EmployeeGoal,EmployeeSkill,JobPosition,Organization,PerformanceReview,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPerformancePhase7Test extends TestCase
{
    use RefreshDatabase;

    private function core(string $code='PERF'): array
    {
        $tenant=Tenant::create(['name'=>$code.' Tenant','code'=>$code,'status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>$code.'-HQ','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Performance Manager','email'=>strtolower($code).'-mgr@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $approver=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Performance Approver','email'=>strtolower($code).'-app@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $position=JobPosition::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>$code.'-TECH','title'=>'Technician','department'=>'Operations','status'=>'ACTIVE']);
        $employee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'job_position_id'=>$position->id,'employee_no'=>$code.'-001','name'=>'Performance Employee','hire_date'=>'2026-01-01','probation_end_date'=>'2026-09-01','status'=>'ACTIVE','basic_salary'=>8000]);
        return [$tenant,$org,$manager,$approver,$position,$employee];
    }

    public function test_goals_reviews_and_independent_approval_work(): void
    {
        [$tenant,$org,$manager,$approver,$position,$employee]=$this->core();
        $this->actingAs($manager)->post('/hr/performance/cycles',['code'=>'P26','name'=>'2026 Cycle','starts_on'=>'2026-01-01','ends_on'=>'2026-12-31'])->assertRedirect();
        $cycle=\App\Models\PerformanceCycle::firstOrFail();
        $this->actingAs($manager)->post('/hr/performance/employees/'.$employee->id.'/goals',['performance_cycle_id'=>$cycle->id,'title'=>'Close work orders','weight'=>60,'target_value'=>100,'unit'=>'jobs','due_on'=>'2026-12-31'])->assertRedirect();
        $this->assertSame('OPEN',EmployeeGoal::firstOrFail()->status);
        $this->actingAs($manager)->post('/hr/performance/employees/'.$employee->id.'/reviews',['performance_cycle_id'=>$cycle->id,'review_type'=>'ANNUAL','review_date'=>'2026-12-31','goal_score'=>90,'competency_score'=>80])->assertRedirect();
        $review=PerformanceReview::firstOrFail();
        $this->assertSame(86.0,(float)$review->overall_score);
        $this->assertSame('EXCEEDS_EXPECTATIONS',$review->rating);
        $this->actingAs($manager)->post('/hr/performance/reviews/'.$review->id.'/submit')->assertRedirect();
        $this->actingAs($manager)->post('/hr/performance/reviews/'.$review->id.'/decide',['decision'=>'APPROVED'])->assertSessionHasErrors('review');
        $this->actingAs($approver)->post('/hr/performance/reviews/'.$review->id.'/decide',['decision'=>'APPROVED'])->assertRedirect();
        $this->assertSame('APPROVED',$review->fresh()->status);
    }

    public function test_probation_skill_and_development_records_are_employee_scoped(): void
    {
        [$tenant,$org,$manager,$approver,$position,$employee]=$this->core('SKL');
        $this->actingAs($manager)->post('/hr/performance/employees/'.$employee->id.'/probation',['review_date'=>'2026-08-20','performance_score'=>88,'attendance_score'=>94,'conduct_score'=>91,'recommendation'=>'CONFIRM'])->assertRedirect();
        $this->actingAs($manager)->post('/hr/performance/employees/'.$employee->id.'/skills',['skill_name'=>'HVAC Level II','skill_type'=>'CERTIFICATION','proficiency'=>'Expert','certificate_no'=>'CERT-77','issued_on'=>'2026-01-01','expires_on'=>'2027-01-01','issuer'=>'Training Body'])->assertRedirect();
        $this->actingAs($manager)->post('/hr/performance/employees/'.$employee->id.'/development',['item_type'=>'TRAINING','title'=>'Advanced Reliability','target_date'=>'2026-11-01','estimated_cost'=>1500])->assertRedirect();
        $this->assertDatabaseHas('probation_reviews',['employee_id'=>$employee->id,'recommendation'=>'CONFIRM']);
        $this->assertSame('CERTIFICATION',EmployeeSkill::firstOrFail()->skill_type);
        $this->actingAs($manager)->get('/hr/performance/employees/'.$employee->id)->assertOk()->assertSee('Performance 360',false)->assertSee('HVAC Level II');
    }

    public function test_career_action_requires_independent_approval_and_effective_date_before_apply(): void
    {
        [$tenant,$org,$manager,$approver,$position,$employee]=$this->core('CAR');
        $newPosition=JobPosition::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>'CAR-SUP','title'=>'Supervisor','department'=>'Operations','status'=>'ACTIVE']);
        $this->actingAs($manager)->post('/hr/performance/employees/'.$employee->id.'/career-actions',['action_type'=>'PROMOTION','to_job_position_id'=>$newPosition->id,'new_basic_salary'=>10000,'effective_on'=>'2026-08-01','reason'=>'Performance promotion'])->assertRedirect();
        $action=CareerAction::firstOrFail();
        $this->actingAs($manager)->post('/hr/performance/career-actions/'.$action->id.'/decide',['decision'=>'APPROVED'])->assertSessionHasErrors('action');
        $this->actingAs($approver)->post('/hr/performance/career-actions/'.$action->id.'/decide',['decision'=>'APPROVED'])->assertRedirect();
        $this->actingAs($approver)->post('/hr/performance/career-actions/'.$action->id.'/apply')->assertRedirect();
        $this->assertSame('APPLIED',$action->fresh()->status);
        $this->assertSame($newPosition->id,$employee->fresh()->job_position_id);
        $this->assertSame(10000.0,(float)$employee->fresh()->basic_salary);
    }

    public function test_performance_records_are_tenant_scoped(): void
    {
        [$ta,$oa,$ua,$aa,$pa,$ea]=$this->core('PA');
        [$tb,$ob,$ub,$ab,$pb,$eb]=$this->core('PB');
        $this->actingAs($ub)->post('/hr/performance/employees/'.$eb->id.'/skills',['skill_name'=>'Private Skill','skill_type'=>'SKILL'])->assertRedirect();
        $this->actingAs($ua)->get('/hr/performance/employees/'.$eb->id)->assertNotFound();
    }

    public function test_phase7_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.performance.index'));
        $this->assertTrue(\Route::has('hr.performance.employees.show'));
        $this->assertTrue(\Route::has('hr.performance.reviews.decide'));
        $this->assertTrue(\Route::has('hr.performance.career.apply'));
    }
}
