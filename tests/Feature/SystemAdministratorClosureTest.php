<?php

namespace Tests\Feature;

use App\Models\{Organization,Role,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemAdministratorClosureTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $tenant=Tenant::create(['name'=>'Closure','code'=>'CLOSURE','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ-CLOSURE','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'System Admin','email'=>'closure-admin@example.test','password'=>'password','role'=>'SYSTEM_ADMIN','status'=>'ACTIVE']);
        $role=Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail();
        DB::table('user_roles')->insert(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        return $user;
    }

    public function test_system_admin_has_final_governance_permissions(): void
    {
        $admin=$this->admin();
        $authorization=app(\App\Services\AuthorizationService::class);
        $this->assertTrue($authorization->allows($admin,'login_activity.view'));
        $this->assertTrue($authorization->allows($admin,'integrations.view'));
        $this->assertTrue($authorization->allows($admin,'scope.audit.view'));
    }

    public function test_login_activity_has_a_dedicated_route(): void
    {
        $admin=$this->admin();
        $this->actingAs($admin)->get('/admin/system/login-activity')->assertOk();
    }

    public function test_integration_registry_is_available_for_every_tenant(): void
    {
        $admin=$this->admin();
        $this->actingAs($admin)->get('/admin/system/integrations')->assertOk();
        foreach(['DATABASE','MAIL','QUEUE','SCHEDULER','STORAGE','API_ACCESS'] as $code) {
            $this->assertDatabaseHas('system_integrations',['tenant_id'=>$admin->tenant_id,'code'=>$code]);
        }
    }
}
