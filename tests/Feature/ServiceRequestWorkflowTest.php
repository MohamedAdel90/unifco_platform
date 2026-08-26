<?php

namespace Tests\Feature;

use App\Models\{ApprovalRequest,Customer,Organization,ServiceRequest,Tenant,User};
use App\Services\{ApprovalService,ServiceRequestWorkflowService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiceRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C-WF','name'=>'Workflow Customer','email'=>'wf@example.test','status'=>'ACTIVE']);
        $requester=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'System Admin','email'=>'wf-admin@example.test','password'=>'StrongPassword123','role'=>'ADMIN','status'=>'ACTIVE']);
        $approver=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Maintenance Engineer','email'=>'wf-engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        return compact('tenant','org','customer','requester','approver');
    }

    public function test_large_quotation_builds_sequential_approval_matrix_with_sla(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-001','request_type'=>'QUOTATION','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Project quotation','subject'=>'Major maintenance project','details'=>'Scope','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE','procurement_required'=>true,
        ]);

        app(ServiceRequestWorkflowService::class)->start($request,[
            'estimated_value'=>320000,'margin_pct'=>8,'payment_terms_days'=>120,'risk_level'=>'HIGH','procurement_required'=>true,
        ]);

        $steps=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$request->id)->orderBy('step_order')->get();
        $this->assertSame([
            'MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE','PROJECT_MANAGER','CEO',
        ],$steps->pluck('approval_role')->all());
        $this->assertSame('PENDING',$steps->first()->status);
        $this->assertTrue($steps->skip(1)->every(fn($step)=>$step->status==='WAITING'));
        $this->assertSame(120,(int)$steps->first()->sla_minutes);
        $this->assertNotNull($steps->first()->due_at);
        $this->assertNull($steps->get(1)->due_at);
    }

    public function test_approval_moves_only_next_waiting_step_to_pending(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-002','request_type'=>'QUOTATION','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Quotation','subject'=>'Standard quote','details'=>'Scope','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE',
        ]);
        app(ServiceRequestWorkflowService::class)->start($request,['estimated_value'=>50000,'margin_pct'=>20,'payment_terms_days'=>30,'risk_level'=>'NORMAL']);
        $first=ApprovalRequest::where('entity_id',$request->id)->where('status','PENDING')->firstOrFail();

        $this->actingAs($c['approver']);
        app(ApprovalService::class)->decide($first,'APPROVED','Technical review complete');

        $steps=ApprovalRequest::where('entity_id',$request->id)->orderBy('step_order')->get();
        $this->assertSame('APPROVED',$steps->get(0)->status);
        $this->assertSame('PENDING',$steps->get(1)->status);
        $this->assertTrue($steps->skip(2)->every(fn($step)=>$step->status==='WAITING'));
        $this->assertSame($steps->get(1)->action,$request->fresh()->workflow_stage);
    }

    public function test_wrong_role_cannot_decide_another_roles_approval(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-ROLE','request_type'=>'QUOTATION','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Quotation','subject'=>'Role ownership','details'=>'Scope','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE',
        ]);
        app(ServiceRequestWorkflowService::class)->start($request,['estimated_value'=>50000,'margin_pct'=>20,'payment_terms_days'=>30,'risk_level'=>'NORMAL']);
        $first=ApprovalRequest::where('entity_id',$request->id)->where('status','PENDING')->firstOrFail();
        $wrong=User::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'name'=>'Procurement','email'=>'wf-procurement@example.test','password'=>'StrongPassword123','role'=>'PROCUREMENT','status'=>'ACTIVE']);
        $this->actingAs($wrong);

        $this->expectException(ValidationException::class);
        app(ApprovalService::class)->decide($first,'APPROVED','Should not be allowed');
    }

    public function test_in_contract_routine_maintenance_keeps_approval_path_short(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-003','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Corrective','subject'=>'Routine issue','details'=>'Scope','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'IN_CONTRACT','procurement_required'=>false,
        ]);
        app(ServiceRequestWorkflowService::class)->start($request);

        $this->assertDatabaseCount('approval_requests',1);
        $this->assertDatabaseHas('approval_requests',['entity_id'=>$request->id,'approval_role'=>'MAINTENANCE_ENGINEER','status'=>'PENDING','sla_minutes'=>120]);
    }

    public function test_emergency_maintenance_has_ten_minute_response_sla_and_short_route(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-004','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Emergency','subject'=>'Critical outage','details'=>'Scope','priority'=>'EMERGENCY','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE','response_sla_minutes'=>10,
        ]);
        app(ServiceRequestWorkflowService::class)->start($request);

        $this->assertSame(10,(int)$request->response_sla_minutes);
        $this->assertDatabaseCount('approval_requests',1);
        $this->assertDatabaseHas('approval_requests',['entity_id'=>$request->id,'approval_role'=>'MAINTENANCE_ENGINEER','status'=>'PENDING']);
    }
}
