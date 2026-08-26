<?php

namespace Tests\Feature;

use App\Models\{Customer,CustomerContact,CustomerSite,User};
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

        $expected=[
            'engineer@unifco.local'=>'MAINTENANCE_ENGINEER',
            'maintenance.manager@unifco.local'=>'MAINTENANCE_MANAGER',
            'procurement@unifco.local'=>'PROCUREMENT',
            'tenders@unifco.local'=>'TENDERS_CONTRACTS',
            'finance@unifco.local'=>'FINANCE',
            'projects.manager@unifco.local'=>'PROJECT_MANAGER',
            'ceo@unifco.local'=>'CEO',
            'workflow.customer@unifco.local'=>'CUSTOMER',
        ];

        foreach($expected as $email=>$role){
            $this->assertDatabaseHas('users',['email'=>$email,'role'=>$role,'status'=>'ACTIVE']);
        }

        $customer=Customer::where('customer_code','WF-TEST-001')->firstOrFail();
        $this->assertSame('ACTIVE',$customer->status);
        $this->assertSame('ACTIVE',$customer->onboarding_status);
        $this->assertTrue(CustomerContact::where('customer_id',$customer->id)->where('is_primary',true)->exists());
        $this->assertTrue(CustomerSite::where('customer_id',$customer->id)->where('site_code','WF-RUH-01')->exists());

        $portalUser=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $this->assertSame($customer->id,$portalUser->customer_id);
        $this->assertSame('CUSTOMER_ADMIN',$portalUser->customer_portal_role);

        foreach(['MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE','PROJECT_MANAGER','CEO'] as $role){
            $this->assertTrue(DB::table('role_permissions')->where('role_code',$role)->where('permission_code','workflow.approval.read')->exists());
            $this->assertTrue(DB::table('role_permissions')->where('role_code',$role)->where('permission_code','workflow.approval.decide')->exists());
        }
    }

    public function test_workflow_seeder_is_idempotent(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $this->seed(WorkflowTestUsersSeeder::class);

        $this->assertSame(1,User::where('email','engineer@unifco.local')->count());
        $this->assertSame(1,User::where('email','workflow.customer@unifco.local')->count());
        $customer=Customer::where('customer_code','WF-TEST-001')->firstOrFail();
        $this->assertSame(1,CustomerContact::where('customer_id',$customer->id)->where('email','workflow.customer@unifco.local')->count());
        $this->assertSame(1,CustomerSite::where('customer_id',$customer->id)->where('site_code','WF-RUH-01')->count());
    }
}
