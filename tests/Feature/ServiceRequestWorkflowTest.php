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
        $approver=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Sales Reviewer','email'=>'wf-sales@example.test','password'=>'StrongPassword123','role'=>'SALES','status'=>'ACTIVE']);
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
            'SALES','MAINTENANCE_ENGINEER','PROCUREMENT','TENDERS_CONTRACTS','OPERATIONS_MANAGER','CUSTOMER','TENDERS_CONTRACTS','SALES',
        ],$steps->pluck('approval_role')->all());
        $this->assertSame([
            'SALES_REVIEW','TECHNICAL_REVIEW','PRICING_PROCUREMENT','CONTRACT_REVIEW','INTERNAL_APPROVAL','CUSTOMER_DECISION','PO_OR_CONTRACT','COMPLETED',
        ],$steps->pluck('action')->all());
        $this->assertSame('PENDING',$steps->first()->status);
        $this->assertTrue($steps->skip(1)->every(fn($step)=>$step->status==='WAITING'));
        $this->assertSame(120,(int)$steps->first()->sla_minutes);
        $this->assertNotNull($steps->first()->due_at);
        $this->assertNull($steps->get(1)->due_at);
        $this->assertSame('SALES_REVIEW',$request->fresh()->workflow_stage);
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

        $this->assertSame('SALES_REVIEW',$first->action);
        $this->assertSame('SALES',$first->approval_role);
        $this->actingAs($c['approver']);
        app(ApprovalService::class)->decide($first,'APPROVED','Sales review complete');

        $steps=ApprovalRequest::where('entity_id',$request->id)->orderBy('step_order')->get();
        $this->assertSame('APPROVED',$steps->get(0)->status);
        $this->assertSame('PENDING',$steps->get(1)->status);
        $this->assertSame('TECHNICAL_REVIEW',$steps->get(1)->action);
        $this->assertSame('MAINTENANCE_ENGINEER',$steps->get(1)->approval_role);
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

    public function test_in_contract_routine_maintenance_builds_full_operational_route(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-003','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Corrective','subject'=>'Routine issue','details'=>'Scope','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'IN_CONTRACT','procurement_required'=>false,
        ]);
        app(ServiceRequestWorkflowService::class)->start($request);

        $steps=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$request->id)->orderBy('step_order')->get();
        $this->assertSame([
            'TRIAGE','PROJECT_MANAGER_REVIEW','TECHNICIAN_ASSIGNMENT','EXECUTION','CUSTOMER_ACCEPTANCE','CLOSURE','CSAT',
        ],$steps->pluck('action')->all());
        $this->assertSame([
            'OPERATIONS_MANAGER','PROJECT_MANAGER','PROJECT_MANAGER','TECHNICIAN','CUSTOMER','OPERATIONS_MANAGER','CUSTOMER',
        ],$steps->pluck('approval_role')->all());
        $this->assertSame('PENDING',$steps->first()->status);
        $this->assertSame(60,(int)$steps->first()->sla_minutes);
        $this->assertTrue($steps->skip(1)->every(fn($step)=>$step->status==='WAITING'));
        $this->assertSame('TRIAGE',$request->fresh()->workflow_stage);
    }

    public function test_emergency_maintenance_has_ten_minute_response_sla_and_full_route(): void
    {
        $c=$this->context();
        $request=ServiceRequest::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,
            'request_no'=>'SR-WF-004','request_type'=>'MAINTENANCE','company_name'=>$c['customer']->name,'email'=>$c['customer']->email,
            'service_category'=>'Emergency','subject'=>'Critical outage','details'=>'Scope','priority'=>'EMERGENCY','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'CHARGEABLE','response_sla_minutes'=>10,
        ]);
        app(ServiceRequestWorkflowService::class)->start($request);

        $steps=ApprovalRequest::where('entity_type',ServiceRequest::class)->where('entity_id',$request->id)->orderBy('step_order')->get();
        $this->assertSame(10,(int)$request->response_sla_minutes);
        $this->assertSame([
            'EMERGENCY_DISPATCH','PROJECT_MANAGER_REVIEW','TECHNICIAN_ASSIGNMENT','EXECUTION','CUSTOMER_ACCEPTANCE','FINANCE_REVIEW','CLOSURE','CSAT',
        ],$steps->pluck('action')->all());
        $this->assertSame([
            'OPERATIONS_MANAGER','PROJECT_MANAGER','PROJECT_MANAGER','TECHNICIAN','CUSTOMER','FINANCE','OPERATIONS_MANAGER','CUSTOMER',
        ],$steps->pluck('approval_role')->all());
        $this->assertSame('PENDING',$steps->first()->status);
        $this->assertSame(10,(int)$steps->first()->sla_minutes);
        $this->assertNotNull($steps->first()->due_at);
        $this->assertTrue($steps->skip(1)->every(fn($step)=>$step->status==='WAITING'));
        $this->assertSame('EMERGENCY_DISPATCH',$request->fresh()->workflow_stage);
    }
}
