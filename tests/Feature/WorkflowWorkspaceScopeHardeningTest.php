<?php

namespace Tests\Feature;

use App\Models\{AccessScope,Customer,Organization,Permission,Role,ServiceRequest,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkflowWorkspaceScopeHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_secondary_structured_role_can_open_workspace_and_customer_scope_filters_queue(): void
    {
        $tenant=Tenant::create(['name'=>'Workflow Scope','code'=>'WF-SCOPE','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'Workflow HQ','code'=>'WF-HQ','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Multi Role Engineer','email'=>'multi-role@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $role=Role::create(['tenant_id'=>$tenant->id,'code'=>'MAINTENANCE_ENGINEER','name_en'=>'Maintenance Engineer','name_ar'=>'Maintenance Engineer','is_active'=>true,'grants_business_authority'=>true]);
        $permission=Permission::firstOrCreate(['code'=>'workflow.approval.read'],['module'=>'workflow','action'=>'approval.read']);
        DB::table('user_roles')->insert(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>false,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        DB::table('role_permissions')->insert(['tenant_id'=>$tenant->id,'role_id'=>$role->id,'permission_id'=>$permission->id,'role_code'=>$role->code,'permission_code'=>$permission->code,'effect'=>'ALLOW','created_at'=>now(),'updated_at'=>now()]);

        $allowedCustomer=Customer::withoutGlobalScopes()->create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'WF-ALLOWED','name'=>'Allowed Customer','status'=>'ACTIVE']);
        $blockedCustomer=Customer::withoutGlobalScopes()->create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'WF-BLOCKED','name'=>'Blocked Customer','status'=>'ACTIVE']);
        $scope=AccessScope::create(['tenant_id'=>$tenant->id,'scope_type'=>'CUSTOMER','scope_id'=>$allowedCustomer->id,'name'=>'Allowed Customer','is_active'=>true]);
        DB::table('user_scopes')->insert(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'DIRECT','created_at'=>now(),'updated_at'=>now()]);

        ServiceRequest::withoutGlobalScopes()->create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$allowedCustomer->id,'request_no'=>'SR-SCOPE-ALLOWED','company_name'=>'Allowed Customer','request_type'=>'MAINTENANCE','service_category'=>'Maintenance','subject'=>'Allowed scoped request','details'=>'Visible','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'TECHNICAL_REVIEW']);
        ServiceRequest::withoutGlobalScopes()->create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$blockedCustomer->id,'request_no'=>'SR-SCOPE-BLOCKED','company_name'=>'Blocked Customer','request_type'=>'MAINTENANCE','service_category'=>'Maintenance','subject'=>'Blocked scoped request','details'=>'Hidden','priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'TECHNICAL_REVIEW']);

        $this->actingAs($user)->get('/workflow/workspace?role=MAINTENANCE_ENGINEER')
            ->assertOk()
            ->assertSee('Maintenance Engineer Workspace')
            ->assertSee('SR-SCOPE-ALLOWED')
            ->assertDontSee('SR-SCOPE-BLOCKED');
    }
}
