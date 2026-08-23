<?php

namespace Tests\Feature;

use App\Models\{Employee,FinalSettlement,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrEndOfServicePhase5Test extends TestCase
{
    use RefreshDatabase;

    private function core(string $code='EOS'): array
    {
        $tenant=Tenant::create(['name'=>$code.' Tenant','code'=>$code,'status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>$code.'-HQ','status'=>'ACTIVE']);
        $preparer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'HR Preparer','email'=>strtolower($code).'-prep@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $approver=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'HR Approver','email'=>strtolower($code).'-approve@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $employee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>$code.'-001','name'=>'Settlement Employee','hire_date'=>'2020-01-01','status'=>'ACTIVE','basic_salary'=>10000,'housing_allowance'=>2000,'transport_allowance'=>1000,'other_allowances'=>0]);
        return [$tenant,$org,$preparer,$approver,$employee];
    }

    public function test_standard_termination_calculates_full_saudi_eos_and_snapshot(): void
    {
        [$tenant,$org,$preparer,$approver,$employee]=$this->core();
        $this->actingAs($preparer)->post('/hr/settlements',[
            'employee_id'=>$employee->id,'termination_reason'=>'CONTRACT_EXPIRY','last_working_day'=>'2026-01-01','unused_leave_days'=>10,'unpaid_salary'=>2000,'other_earnings'=>500,'notice_compensation'=>0,'employee_debt'=>300,'advance_recovery'=>200,'other_deductions'=>0,
        ])->assertRedirect();
        $s=FinalSettlement::firstOrFail();
        $this->assertSame('DRAFT',$s->status);
        $this->assertSame(13000.0,(float)$s->last_wage_basis);
        $this->assertSame(1.0,(float)$s->eos_multiplier);
        $this->assertGreaterThan(45000,(float)$s->eos_award);
        $this->assertSame(4333.33,(float)$s->leave_encashment);
        $this->assertSame((float)$s->gross_entitlements-(float)$s->total_deductions,(float)$s->net_settlement);
        $this->assertSame('article_84',array_key_first($s->calculation_snapshot['law_basis']));
        $this->actingAs($preparer)->get('/hr/settlements/'.$s->id)->assertOk()->assertSee('Final Settlement 360',false);
    }

    public function test_resignation_tiers_and_article_80_are_enforced(): void
    {
        [$tenant,$org,$preparer,$approver,$employee]=$this->core('TIER');
        $employee->update(['hire_date'=>'2022-01-01']);
        $this->actingAs($preparer)->post('/hr/settlements',['employee_id'=>$employee->id,'termination_reason'=>'RESIGNATION','last_working_day'=>'2026-01-01','unused_leave_days'=>0])->assertRedirect();
        $resignation=FinalSettlement::firstOrFail();
        $this->assertEqualsWithDelta(0.3333,(float)$resignation->eos_multiplier,0.0001);
        $this->assertGreaterThan(0,(float)$resignation->eos_award);

        $employee2=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'TIER-002','name'=>'Article 80 Employee','hire_date'=>'2018-01-01','status'=>'ACTIVE','basic_salary'=>9000]);
        $this->actingAs($preparer)->post('/hr/settlements',['employee_id'=>$employee2->id,'termination_reason'=>'ARTICLE_80','last_working_day'=>'2026-01-01','unused_leave_days'=>0])->assertRedirect();
        $article80=FinalSettlement::where('employee_id',$employee2->id)->firstOrFail();
        $this->assertSame(0.0,(float)$article80->eos_multiplier);
        $this->assertSame(0.0,(float)$article80->eos_award);
    }

    public function test_final_settlement_requires_independent_approval_and_can_be_marked_paid(): void
    {
        [$tenant,$org,$preparer,$approver,$employee]=$this->core('SOD');
        $this->actingAs($preparer)->post('/hr/settlements',['employee_id'=>$employee->id,'termination_reason'=>'EMPLOYER_TERMINATION','last_working_day'=>'2026-01-01','unused_leave_days'=>0])->assertRedirect();
        $s=FinalSettlement::firstOrFail();
        $this->actingAs($preparer)->post('/hr/settlements/'.$s->id.'/submit')->assertRedirect();
        $this->assertSame('PENDING_APPROVAL',$s->fresh()->status);
        $this->actingAs($preparer)->post('/hr/settlements/'.$s->id.'/decide',['decision'=>'APPROVED'])->assertSessionHasErrors('settlement');
        $this->actingAs($approver)->post('/hr/settlements/'.$s->id.'/decide',['decision'=>'APPROVED'])->assertRedirect();
        $this->assertSame('APPROVED',$s->fresh()->status);
        $this->actingAs($approver)->post('/hr/settlements/'.$s->id.'/paid')->assertRedirect();
        $this->assertSame('PAID',$s->fresh()->status);
        $this->assertNotNull($s->fresh()->paid_at);
    }

    public function test_final_settlements_are_tenant_scoped(): void
    {
        [$tenantA,$orgA,$userA,$approverA,$employeeA]=$this->core('EA');
        [$tenantB,$orgB,$userB,$approverB,$employeeB]=$this->core('EB');
        $this->actingAs($userB)->post('/hr/settlements',['employee_id'=>$employeeB->id,'termination_reason'=>'CONTRACT_EXPIRY','last_working_day'=>'2026-01-01','unused_leave_days'=>0])->assertRedirect();
        $s=FinalSettlement::firstOrFail();
        $this->actingAs($userA)->get('/hr/settlements/'.$s->id)->assertNotFound();
    }

    public function test_phase5_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.settlements.index'));
        $this->assertTrue(\Route::has('hr.settlements.show'));
        $this->assertTrue(\Route::has('hr.settlements.decide'));
        $this->assertTrue(\Route::has('hr.settlements.paid'));
    }
}
