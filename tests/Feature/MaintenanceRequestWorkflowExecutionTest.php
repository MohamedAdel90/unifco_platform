<?php

namespace Tests\Feature;

use App\Models\{ApprovalRequest,Organization,ServiceRequest,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceRequestWorkflowExecutionTest extends TestCase
{
    use RefreshDatabase;

    private function setupRequest(string $stage): array
    {
        $tenant=Tenant::create(['name'=>'Workflow Test','code'=>'WF-E2E','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'WF-HQ','status'=>'ACTIVE']);
        $request=ServiceRequest::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'request_no'=>'SR-WF-001','request_type'=>'MAINTENANCE',
            'request_subtype'=>'ROUTINE_MAINTENANCE','workflow_key'=>'MAINTENANCE','company_name'=>'Workflow Customer',
            'email'=>'customer@example.test','mobile'=>'0500000000','service_category'=>'MAINTENANCE','subject'=>'Pump failure',
            'details'=>'Pump is not operating.','site_city'=>'Riyadh','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>$stage,
            'assigned_department'=>'OPERATIONS','approval_state'=>'PENDING','next_action'=>$stage,'workflow_context'=>[],
        ]);
        return [$tenant,$org,$request];
    }

    private function user(Tenant $tenant, Organization $org, string $role, string $email): User
    {
        return User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>$role,'email'=>$email,
            'password'=>'password','role'=>$role,'user_type'=>'INTERNAL','status'=>'ACTIVE',
        ]);
    }

    private function step(ServiceRequest $request, User $requester, string $action, string $role, int $order, string $status): ApprovalRequest
    {
        return ApprovalRequest::create([
            'tenant_id'=>$request->tenant_id,'organization_id'=>$request->organization_id,'entity_type'=>ServiceRequest::class,
            'entity_id'=>$request->id,'action'=>$action,'workflow_key'=>'SERVICE_REQUEST_MAINTENANCE','approval_role'=>$role,
            'step_order'=>$order,'sla_minutes'=>60,'requested_by'=>$requester->id,'status'=>$status,
            'due_at'=>$status==='PENDING'?now()->addHour():null,'metadata'=>['department'=>'OPERATIONS','stage'=>$action],
        ]);
    }

    public function test_operations_triage_moves_request_to_project_manager_review(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('TRIAGE');
        $ops=$this->user($tenant,$org,'OPERATIONS_MANAGER','ops-e2e@example.test');
        $this->step($serviceRequest,$ops,'TRIAGE','OPERATIONS_MANAGER',1,'PENDING');
        $this->step($serviceRequest,$ops,'PROJECT_MANAGER_REVIEW','PROJECT_MANAGER',2,'WAITING');

        $this->actingAs($ops)->post(route('service-requests.workflow.triage',$serviceRequest),['notes'=>'Validated and routed.'])
            ->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame('PROJECT_MANAGER_REVIEW',$serviceRequest->workflow_stage);
        $this->assertSame('PENDING',ApprovalRequest::where('entity_id',$serviceRequest->id)->where('action','PROJECT_MANAGER_REVIEW')->value('status'));
    }

    public function test_assigned_technician_can_complete_execution_and_move_to_customer_acceptance(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('EXECUTION');
        $technician=$this->user($tenant,$org,'TECHNICIAN','tech-e2e@example.test');
        $serviceRequest->update(['assigned_engineer_id'=>$technician->id]);
        $this->step($serviceRequest,$technician,'EXECUTION','TECHNICIAN',1,'PENDING');
        $this->step($serviceRequest,$technician,'CUSTOMER_ACCEPTANCE','CUSTOMER',2,'WAITING');

        $this->actingAs($technician)->post(route('service-requests.workflow.complete-execution',$serviceRequest),[
            'completion_notes'=>'Repair completed and functional test passed.',
        ])->assertRedirect();

        $this->assertSame('CUSTOMER_ACCEPTANCE',$serviceRequest->fresh()->workflow_stage);
    }

    public function test_unassigned_technician_cannot_complete_execution(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('EXECUTION');
        $assigned=$this->user($tenant,$org,'TECHNICIAN','assigned-e2e@example.test');
        $other=$this->user($tenant,$org,'TECHNICIAN','other-e2e@example.test');
        $serviceRequest->update(['assigned_engineer_id'=>$assigned->id]);
        $this->step($serviceRequest,$assigned,'EXECUTION','TECHNICIAN',1,'PENDING');

        $this->actingAs($other)->post(route('service-requests.workflow.complete-execution',$serviceRequest),[
            'completion_notes'=>'Attempted completion.',
        ])->assertForbidden();
    }
}
