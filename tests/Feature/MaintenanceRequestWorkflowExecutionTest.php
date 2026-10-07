<?php

namespace Tests\Feature;

use App\Models\{ApprovalRequest,Asset,Customer,Organization,Project,ProjectUserAssignment,ServiceRequest,Tenant,User,WorkOrder};
use App\Services\RequestStageOwnerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function routingProject(Tenant $tenant, Organization $org): array
    {
        $pm=$this->user($tenant,$org,'PROJECT_MANAGER','pm-routing@example.test');
        $project=Project::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'project_no'=>'PRJ-ROUTING','name'=>'Routing test project','status'=>'ACTIVE',
        ]);
        ProjectUserAssignment::create([
            'tenant_id'=>$tenant->id,'project_id'=>$project->id,'user_id'=>$pm->id,
            'project_role'=>'PROJECT_MANAGER','access_level'=>'PROJECT','status'=>'ACTIVE',
        ]);
        return [$project,$pm];
    }

    public function test_operations_triage_moves_request_to_project_manager_review(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('TRIAGE');
        $ops=$this->user($tenant,$org,'OPERATIONS_MANAGER','ops-e2e@example.test');
        [$project,$pm]=$this->routingProject($tenant,$org);
        $this->step($serviceRequest,$ops,'TRIAGE','OPERATIONS_MANAGER',1,'PENDING');
        $this->step($serviceRequest,$ops,'PROJECT_MANAGER_REVIEW','PROJECT_MANAGER',2,'WAITING');

        $this->actingAs($ops)->post(route('service-requests.workflow.triage',$serviceRequest),['notes'=>'Validated and routed.'])
            ->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame('PROJECT_MANAGER_REVIEW',$serviceRequest->workflow_stage);
        $this->assertSame($project->id,$serviceRequest->project_id);
        $this->assertSame($pm->id,ApprovalRequest::where('entity_id',$serviceRequest->id)
            ->where('action','PROJECT_MANAGER_REVIEW')->value('assigned_user_id'));
        $this->assertSame('PENDING',ApprovalRequest::where('entity_id',$serviceRequest->id)->where('action','PROJECT_MANAGER_REVIEW')->value('status'));
    }

    public function test_consultation_operations_review_shows_action_and_routes_to_project_review(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('OPERATIONS_REVIEW');
        $serviceRequest->update(['request_type'=>'CONSULTATION','workflow_key'=>'TECHNICAL_CONSULTATION']);
        $ops=$this->user($tenant,$org,'OPERATIONS_MANAGER','ops-consultation@example.test');
        [$project,$pm]=$this->routingProject($tenant,$org);
        $this->step($serviceRequest,$ops,'OPERATIONS_REVIEW','OPERATIONS_MANAGER',1,'PENDING');
        $this->step($serviceRequest,$ops,'PROJECT_MANAGER_REVIEW','PROJECT_MANAGER',2,'WAITING');

        $this->actingAs($ops)->get(route('service-requests.workflow.show',$serviceRequest))
            ->assertOk()->assertSee('Complete & Route',false);

        $this->actingAs($ops)->post(route('service-requests.workflow.triage',$serviceRequest),[
            'notes'=>'Consultation scope reviewed.',
        ])->assertRedirect();

        $this->assertSame('PROJECT_MANAGER_REVIEW',$serviceRequest->fresh()->workflow_stage);
        $this->assertSame($project->id,$serviceRequest->fresh()->project_id);
        $this->assertSame($pm->id,ApprovalRequest::where('entity_id',$serviceRequest->id)
            ->where('action','PROJECT_MANAGER_REVIEW')->value('assigned_user_id'));
    }

    public function test_triage_without_customer_project_keeps_the_request_in_operations(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('TRIAGE');
        $ops=$this->user($tenant,$org,'OPERATIONS_MANAGER','ops-no-project@example.test');
        $this->step($serviceRequest,$ops,'TRIAGE','OPERATIONS_MANAGER',1,'PENDING');
        $this->step($serviceRequest,$ops,'PROJECT_MANAGER_REVIEW','PROJECT_MANAGER',2,'WAITING');

        $this->actingAs($ops)->get(route('service-requests.workflow.show',$serviceRequest))
            ->assertOk()->assertSee('No active project exists for this customer.');

        $this->actingAs($ops)->post(route('service-requests.workflow.triage',$serviceRequest),[
            'notes'=>'Attempt to route without a customer project.',
        ])->assertRedirect()->assertSessionHasErrors('project_id');

        $this->assertNull($serviceRequest->fresh()->project_id);
        $this->assertSame('TRIAGE',$serviceRequest->fresh()->workflow_stage);
        $this->assertSame('PENDING',ApprovalRequest::where('entity_id',$serviceRequest->id)
            ->where('action','TRIAGE')->value('status'));
        $this->assertSame('WAITING',ApprovalRequest::where('entity_id',$serviceRequest->id)
            ->where('action','PROJECT_MANAGER_REVIEW')->value('status'));
    }

    public function test_project_bound_operations_candidates_require_a_project_team_assignment(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('OPERATIONS_REVIEW');
        $ops=$this->user($tenant,$org,'OPERATIONS_MANAGER','ops-recovery@example.test');
        $project=Project::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'project_no'=>'PRJ-RECOVERY','name'=>'Recovery project','status'=>'ACTIVE']);
        $serviceRequest->update(['project_id'=>$project->id]);

        $candidates=app(RequestStageOwnerService::class)->candidates($serviceRequest->fresh(),'OPERATIONS_MANAGER');
        $this->assertFalse($candidates->contains('id',$ops->id));
        $this->assertSame('NEEDS_ASSIGNMENT',app(RequestStageOwnerService::class)
            ->resolve($serviceRequest->fresh(),'OPERATIONS_MANAGER','OPERATIONS_REVIEW')['status']);
    }

    public function test_failed_project_routing_rolls_back_request_project_binding(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('OPERATIONS_REVIEW');
        $ops=$this->user($tenant,$org,'OPERATIONS_MANAGER','ops-rollback@example.test');
        $pm=$this->user($tenant,$org,'PROJECT_MANAGER','pm-rollback@example.test');
        $step=$this->step($serviceRequest,$ops,'OPERATIONS_REVIEW','OPERATIONS_MANAGER',1,'PENDING');
        $step->update(['routing_status'=>'NEEDS_ASSIGNMENT']);
        $project=Project::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'project_no'=>'PRJ-ROLLBACK','name'=>'Rollback project','status'=>'ACTIVE']);
        ProjectUserAssignment::create(['tenant_id'=>$tenant->id,'project_id'=>$project->id,
            'user_id'=>$pm->id,'project_role'=>'PROJECT_MANAGER','access_level'=>'PROJECT','status'=>'ACTIVE']);

        $this->actingAs($ops)->post(route('service-requests.workflow.triage',$serviceRequest),[
            'project_id'=>$project->id,
        ])->assertStatus(422);
        $this->assertNull($serviceRequest->fresh()->project_id);
    }

    public function test_operations_role_queue_routes_before_project_owner_is_recalculated(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('TRIAGE');
        $ops=$this->user($tenant,$org,'OPERATIONS_MANAGER','ops-bind@example.test');
        $pm=$this->user($tenant,$org,'PROJECT_MANAGER','pm-bind@example.test');
        $this->step($serviceRequest,$ops,'TRIAGE','OPERATIONS_MANAGER',1,'PENDING');
        $this->step($serviceRequest,$ops,'PROJECT_MANAGER_REVIEW','PROJECT_MANAGER',2,'WAITING');
        $project=Project::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'project_no'=>'PRJ-BIND','name'=>'Binding project','status'=>'ACTIVE']);
        ProjectUserAssignment::create(['tenant_id'=>$tenant->id,'project_id'=>$project->id,
            'user_id'=>$pm->id,'project_role'=>'PROJECT_MANAGER','access_level'=>'PROJECT','status'=>'ACTIVE']);

        $this->actingAs($ops)->post(route('service-requests.workflow.triage',$serviceRequest),[
            'project_id'=>$project->id,
        ])->assertRedirect();
        $this->assertSame($project->id,$serviceRequest->fresh()->project_id);
        $this->assertSame('PROJECT_MANAGER_REVIEW',$serviceRequest->fresh()->workflow_stage);
        $this->assertSame($pm->id,ApprovalRequest::where('entity_id',$serviceRequest->id)
            ->where('action','PROJECT_MANAGER_REVIEW')->value('assigned_user_id'));
        $this->assertSame('ASSIGNED',ApprovalRequest::where('entity_id',$serviceRequest->id)
            ->where('action','PROJECT_MANAGER_REVIEW')->value('routing_status'));
        $this->assertSame('COMPLETED',ApprovalRequest::where('entity_id',$serviceRequest->id)
            ->where('action','TRIAGE')->value('status'));
    }

    public function test_unbound_technical_visit_requires_customer_project_before_project_manager_review(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('PROJECT_MANAGER_REVIEW');
        $customer=Customer::create(['tenant_id'=>$tenant->id,'customer_code'=>'ROUTE-C1',
            'name'=>'Routing customer','status'=>'ACTIVE']);
        $serviceRequest->update(['customer_id'=>$customer->id,'request_type'=>'QUOTATION',
            'workflow_key'=>'TECHNICAL_VISIT']);
        $ops=$this->user($tenant,$org,'OPERATIONS_MANAGER','ops-quotation@example.test');
        $pm=$this->user($tenant,$org,'PROJECT_MANAGER','pm-quotation@example.test');
        $project=Project::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'customer_id'=>$customer->id,'project_no'=>'PRJ-QUOTE','name'=>'Customer project','status'=>'ACTIVE']);
        ProjectUserAssignment::create(['tenant_id'=>$tenant->id,'project_id'=>$project->id,
            'user_id'=>$pm->id,'project_role'=>'PROJECT_MANAGER','access_level'=>'PROJECT','status'=>'ACTIVE']);
        DB::table('role_permissions')->insert(['tenant_id'=>$tenant->id,'role_code'=>'OPERATIONS_MANAGER',
            'permission_code'=>'service_requests.assign','effect'=>'ALLOW','created_at'=>now(),'updated_at'=>now()]);
        $step=$this->step($serviceRequest,$pm,'PROJECT_MANAGER_REVIEW','PROJECT_MANAGER',2,'PENDING');
        $step->update(['routing_status'=>'ROLE_QUEUE']);

        $this->assertSame('NEEDS_ASSIGNMENT',app(RequestStageOwnerService::class)
            ->resolve($serviceRequest->fresh(),'PROJECT_MANAGER','PROJECT_MANAGER_REVIEW')['status']);
        $this->actingAs($pm)->post(route('service-requests.workflow.project-review',$serviceRequest),[
            'decision'=>'APPROVE',
        ])->assertStatus(422);
        $this->assertSame('PROJECT_MANAGER_REVIEW',$serviceRequest->fresh()->workflow_stage);

        $this->actingAs($ops)->get(route('service-requests.workflow.show',$serviceRequest))
            ->assertOk()->assertSee('Link Project & Reassign Review',false);
        $this->actingAs($ops)->post(route('service-requests.workflow.assign-project',$serviceRequest),[
            'project_id'=>$project->id,
        ])->assertRedirect();
        $this->assertSame($project->id,$serviceRequest->fresh()->project_id);
        $this->assertSame($pm->id,$step->fresh()->assigned_user_id);
        $this->assertSame('ASSIGNED',$step->fresh()->routing_status);
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

    public function test_execution_cannot_complete_an_open_linked_work_order(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('EXECUTION');
        $technician=$this->user($tenant,$org,'TECHNICIAN','tech-open-wo@example.test');
        $asset=Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'asset_code'=>'WF-OPEN-WO','name'=>'UAT pump',
        ]);
        $workOrder=WorkOrder::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'work_order_no'=>'WO-WF-OPEN','asset_id'=>$asset->id,
            'status'=>'OPEN',
        ]);
        $serviceRequest->update(['assigned_engineer_id'=>$technician->id,'work_order_id'=>$workOrder->id]);
        $this->step($serviceRequest,$technician,'EXECUTION','TECHNICIAN',1,'PENDING');
        $this->step($serviceRequest,$technician,'CUSTOMER_ACCEPTANCE','CUSTOMER',2,'WAITING');

        $this->actingAs($technician)->post(route('service-requests.workflow.complete-execution',$serviceRequest),[
            'completion_notes'=>'Attempt to skip work order completion.',
        ])->assertStatus(422);

        $this->assertSame('OPEN',$workOrder->fresh()->status);
        $this->assertSame('EXECUTION',$serviceRequest->fresh()->workflow_stage);
        $this->assertSame('PENDING',ApprovalRequest::where('entity_id',$serviceRequest->id)
            ->where('action','EXECUTION')->value('status'));
    }

    public function test_linked_work_order_only_completes_for_the_assigned_technician(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('EXECUTION');
        $assigned=$this->user($tenant,$org,'TECHNICIAN','assigned-wo@example.test');
        $other=$this->user($tenant,$org,'TECHNICIAN','other-wo@example.test');
        $asset=Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'asset_code'=>'WF-OWNER-WO','name'=>'UAT pump',
        ]);
        $workOrder=WorkOrder::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
            'work_order_no'=>'WO-WF-OWNER','asset_id'=>$asset->id,'status'=>'OPEN',
        ]);
        $serviceRequest->update(['assigned_engineer_id'=>$assigned->id,'work_order_id'=>$workOrder->id]);
        $step=$this->step($serviceRequest,$assigned,'EXECUTION','TECHNICIAN',1,'PENDING');
        $step->update(['assigned_user_id'=>$assigned->id,'routing_status'=>'ASSIGNED']);
        $this->step($serviceRequest,$assigned,'CUSTOMER_ACCEPTANCE','CUSTOMER',2,'WAITING');
        DB::table('role_permissions')->updateOrInsert(
            ['tenant_id'=>$tenant->id,'role_code'=>'TECHNICIAN','permission_code'=>'maintenance.work_order.manage'],
            ['effect'=>'ALLOW','created_at'=>now(),'updated_at'=>now()]
        );
        $completion=['completion_notes'=>'Repair completed and tested.','labor_hours'=>1,'labor_cost'=>100,'external_cost'=>0];

        $this->actingAs($other)->post(route('maintenance.work-orders.complete',$workOrder),$completion)->assertForbidden();
        $this->assertSame('OPEN',$workOrder->fresh()->status);
        $this->assertSame('EXECUTION',$serviceRequest->fresh()->workflow_stage);
        $this->assertSame('PENDING',$step->fresh()->status);

        $this->actingAs($assigned)->post(route('maintenance.work-orders.complete',$workOrder),$completion)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED',$workOrder->fresh()->status);
        $this->assertSame($assigned->id,$workOrder->fresh()->completed_by);
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

    public function test_operations_closure_marks_resolved_and_moves_to_csat(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('CLOSURE');
        $ops=$this->user($tenant,$org,'OPERATIONS_MANAGER','ops-close@example.test');
        $this->step($serviceRequest,$ops,'CLOSURE','OPERATIONS_MANAGER',1,'PENDING');
        $this->step($serviceRequest,$ops,'CSAT','CUSTOMER',2,'WAITING');

        $this->actingAs($ops)->post(route('service-requests.workflow.close',$serviceRequest),[
            'notes'=>'Operational work verified and closed.',
        ])->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame('RESOLVED',$serviceRequest->status);
        $this->assertSame('CSAT',$serviceRequest->workflow_stage);
        $this->assertNotNull($serviceRequest->resolved_at);
        $this->assertArrayHasKey('operationally_closed_at',$serviceRequest->workflow_context);
    }

    public function test_assigned_technician_completes_site_visit_and_sends_it_to_the_engineer(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('SITE_VISIT');
        $technician=$this->user($tenant,$org,'TECHNICIAN','visit-tech@example.test');
        [$project,$pm]=$this->routingProject($tenant,$org);
        ProjectUserAssignment::create(['tenant_id'=>$tenant->id,'project_id'=>$project->id,
            'user_id'=>$technician->id,'project_role'=>'TECHNICIAN','access_level'=>'PROJECT','status'=>'ACTIVE']);
        $serviceRequest->update(['workflow_key'=>'TECHNICAL_VISIT','project_id'=>$project->id,
            'assigned_engineer_id'=>$technician->id]);
        $visit=$this->step($serviceRequest,$pm,'SITE_VISIT','TECHNICIAN',1,'PENDING');
        $visit->update(['assigned_user_id'=>$technician->id,'routing_status'=>'ASSIGNED']);
        $this->step($serviceRequest,$pm,'TECHNICAL_REPORT','MAINTENANCE_ENGINEER',2,'WAITING');

        $this->actingAs($technician)->get(route('service-requests.workflow.show',$serviceRequest))
            ->assertOk()->assertSee('Complete Site Visit &amp; Send Technical Report',false);
        $this->actingAs($technician)->post(route('service-requests.workflow.complete-execution',$serviceRequest),[
            'completion_notes'=>'UAT visit: inspection recorded and findings sent for engineer review.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('TECHNICAL_REPORT',$serviceRequest->fresh()->workflow_stage);
        $this->assertSame('COMPLETED',$visit->fresh()->status);
    }

    public function test_consultation_visit_requires_notes_and_the_assigned_technician(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('SITE_VISIT');
        $assigned=$this->user($tenant,$org,'TECHNICIAN','consultation-tech@example.test');
        $other=$this->user($tenant,$org,'TECHNICIAN','other-visit-tech@example.test');
        $serviceRequest->update(['workflow_key'=>'TECHNICAL_CONSULTATION','assigned_engineer_id'=>$assigned->id]);
        $this->step($serviceRequest,$assigned,'SITE_VISIT','TECHNICIAN',1,'PENDING');
        $this->step($serviceRequest,$assigned,'TECHNICAL_REPORT','MAINTENANCE_ENGINEER',2,'WAITING');

        $this->actingAs($other)->get(route('service-requests.workflow.show',$serviceRequest))
            ->assertOk()->assertDontSee('Complete Site Visit',false);
        $this->actingAs($other)->post(route('service-requests.workflow.complete-execution',$serviceRequest),[
            'completion_notes'=>'Attempt to complete another technician visit.',
        ])->assertForbidden();
        $this->actingAs($assigned)->post(route('service-requests.workflow.complete-execution',$serviceRequest),[])
            ->assertRedirect()->assertSessionHasErrors('completion_notes');
        $this->assertSame('SITE_VISIT',$serviceRequest->fresh()->workflow_stage);
        $this->actingAs($assigned)->post(route('service-requests.workflow.complete-execution',$serviceRequest),[
            'completion_notes'=>'UAT consultation inspection complete.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('TECHNICAL_REPORT',$serviceRequest->fresh()->workflow_stage);
    }

    public function test_engineer_report_requires_findings_before_customer_delivery(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('TECHNICAL_REPORT');
        $engineer=$this->user($tenant,$org,'MAINTENANCE_ENGINEER','report-engineer@example.test');
        $serviceRequest->update(['workflow_key'=>'TECHNICAL_CONSULTATION']);
        $this->step($serviceRequest,$engineer,'TECHNICAL_REPORT','MAINTENANCE_ENGINEER',1,'PENDING');
        $this->step($serviceRequest,$engineer,'CUSTOMER_DELIVERY','CUSTOMER',2,'WAITING');
        $this->actingAs($engineer)->get(route('service-requests.workflow.show',$serviceRequest))
            ->assertOk()->assertSee('Record Review');
        $this->actingAs($engineer)->post(route('service-requests.workflow.stage-review',$serviceRequest),[
            'decision'=>'APPROVE',
        ])->assertRedirect()->assertSessionHasErrors('notes');
        $this->assertSame('TECHNICAL_REPORT',$serviceRequest->fresh()->workflow_stage);
        $this->actingAs($engineer)->post(route('service-requests.workflow.stage-review',$serviceRequest),[
            'decision'=>'APPROVE','notes'=>'UAT report: inspection findings, risks and recommendations recorded.',
            'customer_report'=>'UAT customer-facing findings and recommended next steps.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('CUSTOMER_DELIVERY',$serviceRequest->fresh()->workflow_stage);
    }

    public function test_report_return_reopens_the_site_visit_instead_of_maintenance_execution(): void
    {
        [$tenant,$org,$serviceRequest]=$this->setupRequest('TECHNICAL_REPORT');
        $engineer=$this->user($tenant,$org,'MAINTENANCE_ENGINEER','report-return@example.test');
        $serviceRequest->update(['workflow_key'=>'TECHNICAL_CONSULTATION']);
        $this->step($serviceRequest,$engineer,'SITE_VISIT','TECHNICIAN',1,'COMPLETED');
        $this->step($serviceRequest,$engineer,'TECHNICAL_REPORT','MAINTENANCE_ENGINEER',2,'PENDING');

        $this->actingAs($engineer)->post(route('service-requests.workflow.stage-review',$serviceRequest),[
            'decision'=>'RETURN','notes'=>'Additional site readings are required.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('SITE_VISIT',$serviceRequest->fresh()->workflow_stage);
        $this->assertSame('PENDING',ApprovalRequest::where('entity_id',$serviceRequest->id)
            ->where('action','SITE_VISIT')->value('status'));
    }
}
