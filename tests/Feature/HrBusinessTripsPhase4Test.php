<?php

namespace Tests\Feature;

use App\Models\{BusinessTrip,BusinessTripExpense,Customer,Employee,Organization,Project,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrBusinessTripsPhase4Test extends TestCase
{
    use RefreshDatabase;

    private function seedCore(string $code='M4'): array
    {
        $tenant=Tenant::create(['name'=>$code.' Tenant','code'=>$code,'status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>$code.'-HQ','status'=>'ACTIVE']);
        $requester=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Requester','email'=>strtolower($code).'-requester@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $approver=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Approver','email'=>strtolower($code).'-approver@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $employee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>$code.'-EMP','name'=>'Mission Employee','hire_date'=>'2024-01-01','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>$code.'-CUS','name'=>'Mission Customer','status'=>'ACTIVE']);
        $project=Project::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'project_no'=>$code.'-PRJ','name'=>'Mission Project','customer_id'=>$customer->id,'status'=>'ACTIVE']);
        return [$tenant,$org,$requester,$approver,$employee,$customer,$project];
    }

    private function requestMission(User $requester,Employee $employee,array $extra=[]): BusinessTrip
    {
        $this->actingAs($requester)->post('/hr/missions',array_merge(['employee_id'=>$employee->id,'trip_type'=>'LOCAL','purpose'=>'Mission work','destination_city'=>'Dammam','destination_country'=>'SA','starts_on'=>'2026-09-01','ends_on'=>'2026-09-03','per_diem_rate'=>200,'requested_advance'=>1000],$extra))->assertRedirect();
        return BusinessTrip::firstOrFail();
    }

    public function test_phase4_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.missions.index')); $this->assertTrue(\Route::has('hr.missions.decide')); $this->assertTrue(\Route::has('hr.missions.expenses.store')); $this->assertTrue(\Route::has('hr.missions.settle'));
    }

    public function test_mission_request_calculates_per_diem_and_links_business_context(): void
    {
        [$tenant,$org,$requester,$approver,$employee,$customer,$project]=$this->seedCore();
        $trip=$this->requestMission($requester,$employee,['purpose'=>'Customer site commissioning','project_id'=>$project->id,'customer_id'=>$customer->id,'travel_method'=>'COMPANY_CAR','hotel_required'=>1]);
        $this->assertSame(3.0,(float)$trip->per_diem_days); $this->assertSame(600.0,(float)$trip->per_diem_total); $this->assertSame('PENDING',$trip->status); $this->assertSame('REQUESTED',$trip->advance_status); $this->assertSame($project->id,$trip->project_id); $this->assertSame($customer->id,$trip->customer_id);
    }

    public function test_mission_360_renders_business_context(): void
    {
        [$tenant,$org,$requester,$approver,$employee,$customer,$project]=$this->seedCore('MR');
        $trip=$this->requestMission($requester,$employee,['purpose'=>'Render mission','project_id'=>$project->id,'customer_id'=>$customer->id]);
        $this->actingAs($requester)->get('/hr/missions/'.$trip->id)->assertOk()->assertSee('Mission 360',false)->assertSee('Render mission',false);
    }

    public function test_requester_cannot_approve_own_mission_and_full_settlement_is_auditable(): void
    {
        [$tenant,$org,$requester,$approver,$employee]=$this->seedCore('M5'); $trip=$this->requestMission($requester,$employee,['trip_type'=>'INTERNATIONAL','purpose'=>'Supplier inspection','destination_city'=>'Dubai','destination_country'=>'AE','starts_on'=>'2026-10-10','ends_on'=>'2026-10-12']);
        $this->actingAs($requester)->post('/hr/missions/'.$trip->id.'/decide',['decision'=>'APPROVED'])->assertSessionHasErrors('mission');
        $this->actingAs($approver)->post('/hr/missions/'.$trip->id.'/decide',['decision'=>'APPROVED'])->assertRedirect(); $this->assertSame('APPROVED',$trip->fresh()->status);
        $this->actingAs($approver)->post('/hr/missions/'.$trip->id.'/advance',['action'=>'APPROVE','amount'=>1000])->assertRedirect(); $this->actingAs($approver)->post('/hr/missions/'.$trip->id.'/advance',['action'=>'MARK_PAID'])->assertRedirect(); $this->assertSame('PAID',$trip->fresh()->advance_status);
        $this->actingAs($requester)->post('/hr/missions/'.$trip->id.'/expenses',['expense_date'=>'2026-10-11','category'=>'HOTEL','description'=>'Hotel invoice','amount'=>300,'currency'=>'SAR','receipt_ref'=>'INV-300'])->assertRedirect(); $expense=BusinessTripExpense::firstOrFail();
        $this->actingAs($requester)->post('/hr/missions/'.$trip->id.'/expenses/'.$expense->id.'/decide',['decision'=>'APPROVED'])->assertSessionHasErrors('expense'); $this->actingAs($approver)->post('/hr/missions/'.$trip->id.'/expenses/'.$expense->id.'/decide',['decision'=>'APPROVED'])->assertRedirect();
        $this->actingAs($requester)->post('/hr/missions/'.$trip->id.'/complete',['completion_notes'=>'Mission completed'])->assertRedirect(); $this->actingAs($approver)->post('/hr/missions/'.$trip->id.'/settle')->assertRedirect();
        $trip=$trip->fresh(); $this->assertSame('SETTLED',$trip->status); $this->assertSame(900.0,(float)$trip->settlement_total); $this->assertSame(0.0,(float)$trip->company_payable); $this->assertSame(100.0,(float)$trip->employee_refund_due);
    }

    public function test_missions_are_tenant_scoped(): void
    {
        [$tenantA,$orgA,$requesterA]=$this->seedCore('TA'); [$tenantB,$orgB,$requesterB,$approverB,$employeeB]=$this->seedCore('TB'); $trip=$this->requestMission($requesterB,$employeeB,['purpose'=>'Other tenant mission','destination_city'=>'Jeddah','starts_on'=>'2026-11-01','ends_on'=>'2026-11-01','per_diem_rate'=>100]);
        $this->actingAs($requesterA)->get('/hr/missions/'.$trip->id)->assertNotFound();
    }
}
