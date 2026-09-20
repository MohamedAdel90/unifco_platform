<?php

namespace Tests\Feature;

use App\Models\{AccessScope,ApprovalRequest,Customer,Project,ProjectUserAssignment,Role,ServiceRequest,Tenant,User};
use App\Services\ServiceRequestWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestStageOwnerRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::create(['name'=>'Owner Routing','code'=>'OWNER-ROUTING','status'=>'ACTIVE']);
    }

    private function userWithRole(Tenant $tenant,string $roleCode,string $email): User
    {
        $role=Role::query()->firstOrCreate(
            ['tenant_id'=>$tenant->id,'code'=>$roleCode],
            ['name_en'=>str($roleCode)->headline(),'is_active'=>true,'grants_business_authority'=>true]
        );
        $user=User::query()->create([
            'tenant_id'=>$tenant->id,'name'=>$email,'email'=>$email,'password'=>'password',
            'role'=>$roleCode,'user_type'=>'INTERNAL','status'=>'ACTIVE',
        ]);
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,
            'is_primary'=>true,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return $user;
    }

    public function test_project_manager_stage_is_assigned_to_actual_manager_of_that_project(): void
    {
        $tenant=$this->tenant();
        $customer=Customer::query()->create([
            'tenant_id'=>$tenant->id,'customer_code'=>'OWNER-C1','name'=>'Owner Customer','status'=>'ACTIVE',
        ]);
        $project=Project::query()->create([
            'tenant_id'=>$tenant->id,'project_no'=>'OWNER-P1','name'=>'Owner Project',
            'customer_id'=>$customer->id,'budget'=>0,'status'=>'ACTIVE',
        ]);

        $pm=$this->userWithRole($tenant,'PROJECT_MANAGER','owner-pm@example.test');
        $ops=$this->userWithRole($tenant,'OPERATIONS_MANAGER','owner-ops@example.test');
        $requester=$this->userWithRole($tenant,'CUSTOMER_SERVICE','owner-cs@example.test');

        foreach([[$pm,'PROJECT_MANAGER'],[$ops,'OPERATIONS_MANAGER']] as [$user,$role]){
            ProjectUserAssignment::query()->create([
                'tenant_id'=>$tenant->id,'project_id'=>$project->id,'user_id'=>$user->id,
                'project_role'=>$role,'access_level'=>'PROJECT','status'=>'ACTIVE',
            ]);
            $scope=AccessScope::query()->firstOrCreate(
                ['tenant_id'=>$tenant->id,'scope_type'=>'PROJECT','scope_id'=>$project->id],
                ['name'=>$project->project_no,'is_active'=>true]
            );
            DB::table('user_scopes')->updateOrInsert(
                ['user_id'=>$user->id,'access_scope_id'=>$scope->id],
                ['tenant_id'=>$tenant->id,'source'=>'PROJECT_TEAM','created_at'=>now(),'updated_at'=>now()]
            );
        }

        $request=ServiceRequest::query()->create([
            'tenant_id'=>$tenant->id,'customer_id'=>$customer->id,'project_id'=>$project->id,
            'operations_manager_id'=>$ops->id,'project_manager_id'=>$pm->id,
            'request_no'=>'SR-OWNER-1','request_type'=>'MAINTENANCE','company_name'=>$customer->name,
            'service_category'=>'Maintenance','subject'=>'Owner routing','details'=>'Owner routing',
            'priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'IN_CONTRACT',
        ]);

        app(ServiceRequestWorkflowService::class)->start($request);

        $pmStep=ApprovalRequest::query()
            ->where('entity_id',$request->id)
            ->where('action','PROJECT_MANAGER_REVIEW')->firstOrFail();

        $this->assertSame($pm->id,$pmStep->assigned_user_id);
        $this->assertSame('ASSIGNED',$pmStep->routing_status);
    }

    public function test_multiple_project_supervisors_leave_stage_in_controlled_unassigned_state(): void
    {
        $tenant=$this->tenant();
        $customer=Customer::query()->create([
            'tenant_id'=>$tenant->id,'customer_code'=>'OWNER-C2','name'=>'Owner Customer 2','status'=>'ACTIVE',
        ]);
        $project=Project::query()->create([
            'tenant_id'=>$tenant->id,'project_no'=>'OWNER-P2','name'=>'Owner Project 2',
            'customer_id'=>$customer->id,'budget'=>0,'status'=>'ACTIVE',
        ]);
        $ops=$this->userWithRole($tenant,'OPERATIONS_MANAGER','owner2-ops@example.test');
        $pm=$this->userWithRole($tenant,'PROJECT_MANAGER','owner2-pm@example.test');
        foreach([$this->userWithRole($tenant,'TECHNICAL_SUPERVISOR','sup1@example.test'),$this->userWithRole($tenant,'TECHNICAL_SUPERVISOR','sup2@example.test')] as $sup){
            ProjectUserAssignment::query()->create([
                'tenant_id'=>$tenant->id,'project_id'=>$project->id,'user_id'=>$sup->id,
                'project_role'=>'TECHNICAL_SUPERVISOR','access_level'=>'PROJECT','status'=>'ACTIVE',
            ]);
        }

        $request=ServiceRequest::query()->create([
            'tenant_id'=>$tenant->id,'customer_id'=>$customer->id,'project_id'=>$project->id,
            'operations_manager_id'=>$ops->id,'project_manager_id'=>$pm->id,
            'request_no'=>'SR-OWNER-2','request_type'=>'MAINTENANCE','company_name'=>$customer->name,
            'service_category'=>'Maintenance','subject'=>'Controlled queue','details'=>'Controlled queue',
            'priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'NEW','eligibility'=>'IN_CONTRACT',
        ]);

        app(ServiceRequestWorkflowService::class)->start($request);

        $step=ApprovalRequest::query()
            ->where('entity_id',$request->id)
            ->where('action','TECHNICIAN_ASSIGNMENT')->firstOrFail();

        $this->assertNull($step->assigned_user_id);
        $this->assertSame('NEEDS_ASSIGNMENT',$step->routing_status);
    }
}
