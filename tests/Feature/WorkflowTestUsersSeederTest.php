<?php

namespace Tests\Feature;

use App\Models\{ApprovalRequest,Customer,CustomerContact,CustomerSite,Project,ServiceRequest,User};
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkflowTestUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_test_users_customer_and_permissions_are_provisioned(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $expected=['engineer@unifco.local'=>'MAINTENANCE_ENGINEER','maintenance.manager@unifco.local'=>'MAINTENANCE_MANAGER','operations.manager@unifco.local'=>'OPERATIONS_MANAGER','projects.manager@unifco.local'=>'PROJECT_MANAGER','technical.supervisor@unifco.local'=>'TECHNICAL_SUPERVISOR','technician@unifco.local'=>'TECHNICIAN','quality@unifco.local'=>'QUALITY','hse@unifco.local'=>'HSE','customer.service@unifco.local'=>'CUSTOMER_SERVICE','procurement@unifco.local'=>'PROCUREMENT','tenders@unifco.local'=>'TENDERS_CONTRACTS','sales@unifco.local'=>'SALES','finance@unifco.local'=>'FINANCE_MANAGER','accountant@unifco.local'=>'ACCOUNTANT','ceo@unifco.local'=>'CEO','workflow.customer@unifco.local'=>'CUSTOMER'];
        foreach($expected as $email=>$role) $this->assertDatabaseHas('users',['email'=>$email,'role'=>$role,'status'=>'ACTIVE']);
        $technician=User::where('email','technician@unifco.local')->firstOrFail();
        $this->assertNotNull($technician->employee_id);
        $this->actingAs($technician)->get('/field/technician')->assertOk();

        $customer=Customer::where('customer_code','WF-TEST-001')->firstOrFail();
        $this->assertSame('ACTIVE',$customer->status);
        $this->assertSame('ACTIVE',$customer->onboarding_status);
        $this->assertTrue(CustomerContact::where('customer_id',$customer->id)->where('is_primary',true)->exists());
        $this->assertTrue(CustomerSite::where('customer_id',$customer->id)->where('site_code','WF-RUH-01')->exists());
        $portalUser=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $this->assertSame($customer->id,$portalUser->customer_id);
        $this->assertSame('CUSTOMER',$portalUser->customer_portal_role);

        foreach(['MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROJECT_MANAGER','QUALITY','HSE','CUSTOMER_SERVICE','PROCUREMENT','TENDERS_CONTRACTS','FINANCE_MANAGER','CEO'] as $role){
            $this->assertTrue(DB::table('role_permissions')->where('role_code',$role)->where('permission_code','workflow.approval.read')->exists());
            $this->assertTrue(DB::table('role_permissions')->where('role_code',$role)->where('permission_code','workflow.approval.decide')->exists());
        }
    }

    public function test_uat_project_gets_active_scoped_workflow_team(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $customer=Customer::where('customer_code','WF-TEST-001')->firstOrFail();
        $customer->update(['customer_code'=>'100']);
        $project=Project::create([
            'tenant_id'=>$customer->tenant_id,'organization_id'=>$customer->organization_id,
            'project_no'=>'PRJ-TEST-001','name'=>'UNIFCO Maintenance UAT Project',
            'customer_id'=>$customer->id,'status'=>'ACTIVE',
        ]);

        $this->seed(WorkflowTestUsersSeeder::class);
        $this->seed(WorkflowTestUsersSeeder::class);
        foreach ([
            'PROJECT_MANAGER'=>'projects.manager@unifco.local',
            'MAINTENANCE_MANAGER'=>'maintenance.manager@unifco.local',
            'MAINTENANCE_ENGINEER'=>'engineer@unifco.local',
            'TECHNICAL_SUPERVISOR'=>'technical.supervisor@unifco.local',
            'TECHNICIAN'=>'technician@unifco.local',
            'QUALITY'=>'quality@unifco.local',
            'HSE'=>'hse@unifco.local',
        ] as $projectRole=>$email) {
            $owner=User::where('email',$email)->firstOrFail();
            $this->assertSame(1,DB::table('project_user_assignments')
                ->where('project_id',$project->id)->where('user_id',$owner->id)
                ->where('project_role',$projectRole)->where('status','ACTIVE')->count());
        }
    }

    public function test_existing_open_uat_approval_is_reassigned_after_team_seeding(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $customer=Customer::where('customer_code','WF-TEST-001')->firstOrFail();
        $customer->update(['customer_code'=>'100']);
        $project=Project::create([
            'tenant_id'=>$customer->tenant_id,'organization_id'=>$customer->organization_id,
            'project_no'=>'PRJ-TEST-001','name'=>'UNIFCO Maintenance UAT Project',
            'customer_id'=>$customer->id,'status'=>'ACTIVE',
        ]);
        $request=ServiceRequest::create([
            'tenant_id'=>$customer->tenant_id,'organization_id'=>$customer->organization_id,
            'customer_id'=>$customer->id,'project_id'=>$project->id,
            'request_no'=>'SR-UAT-OWNER','request_type'=>'MAINTENANCE',
            'company_name'=>$customer->name,'email'=>$customer->email,
            'service_category'=>'Maintenance','subject'=>'Existing pending review',
            'details'=>'Test owner reconciliation','priority'=>'NORMAL',
            'status'=>'OPEN','workflow_stage'=>'MAINTENANCE_MANAGER_REVIEW',
            'eligibility'=>'CHARGEABLE',
        ]);
        $approval=ApprovalRequest::create([
            'tenant_id'=>$customer->tenant_id,'organization_id'=>$customer->organization_id,
            'entity_type'=>ServiceRequest::class,'entity_id'=>$request->id,
            'action'=>'MAINTENANCE_MANAGER_REVIEW','approval_role'=>'MAINTENANCE_MANAGER',
            'requested_by'=>User::where('email','projects.manager@unifco.local')->firstOrFail()->id,
            'step_order'=>1,'sla_minutes'=>120,'status'=>'PENDING',
            'routing_status'=>'NEEDS_ASSIGNMENT',
        ]);

        $this->seed(WorkflowTestUsersSeeder::class);
        $manager=User::where('email','maintenance.manager@unifco.local')->firstOrFail();
        $this->assertSame($manager->id,$approval->fresh()->assigned_user_id);
        $this->assertSame('ASSIGNED',$approval->fresh()->routing_status);
        $this->assertSame('MAINTENANCE_MANAGER_REVIEW',$request->fresh()->workflow_stage);
    }

    public function test_workflow_seeder_is_idempotent(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $this->seed(WorkflowTestUsersSeeder::class);
        foreach(['engineer@unifco.local','technician@unifco.local','quality@unifco.local','hse@unifco.local','customer.service@unifco.local','workflow.customer@unifco.local'] as $email) $this->assertSame(1,User::where('email',$email)->count());
        $customer=Customer::where('customer_code','WF-TEST-001')->firstOrFail();
        $this->assertSame(1,CustomerContact::where('customer_id',$customer->id)->where('email','workflow.customer@unifco.local')->count());
        $this->assertSame(1,CustomerSite::where('customer_id',$customer->id)->where('site_code','WF-RUH-01')->count());
    }
}
