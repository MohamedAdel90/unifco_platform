<?php

namespace Tests\Feature;

use App\Models\{AccessScope, Organization, Role, Tenant, User};
use App\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsManagerPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    private function manager(bool $withGlobalScope = true): User
    {
        $tenant = Tenant::create(['name'=>'Operations','code'=>'OPS','status'=>'ACTIVE']);
        $org = Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'OPS-HQ','status'=>'ACTIVE']);
        $user = User::create([
            'tenant_id'=>$tenant->id,
            'organization_id'=>$org->id,
            'name'=>'Operations Manager',
            'email'=>'operations.manager@example.test',
            'password'=>'password',
            'role'=>'OPERATIONS_MANAGER',
            'user_type'=>'INTERNAL',
            'status'=>'ACTIVE',
        ]);

        $role = Role::whereNull('tenant_id')->where('code','OPERATIONS_MANAGER')->firstOrFail();
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,
            'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);

        if ($withGlobalScope) {
            $scope = AccessScope::create(['tenant_id'=>$tenant->id,'scope_type'=>'GLOBAL','scope_id'=>null,'name'=>'Operations Global','is_active'=>true]);
            DB::table('user_scopes')->insert([
                'tenant_id'=>$tenant->id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'SYSTEM',
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        return $user;
    }

    public function test_operations_manager_baseline_separates_operational_and_system_authority(): void
    {
        $manager = $this->manager();
        $auth = app(AuthorizationService::class);

        $this->assertTrue($auth->allows($manager,'operations.dashboard.view'));
        $this->assertTrue($auth->allows($manager,'maintenance.work_order.read'));
        $this->assertTrue($auth->allows($manager,'maintenance.work_order.manage'));
        $this->assertFalse($auth->allows($manager,'system.dashboard.view'));
        $this->assertFalse($auth->allows($manager,'finance.journal.post'));
        $this->assertFalse($auth->allows($manager,'payroll.edit'));
    }

    public function test_operations_manager_can_open_scoped_command_dashboard_but_not_system_admin(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->get('/operations-manager')->assertOk();
        $this->actingAs($manager)->get('/system-admin')->assertForbidden();
    }

    public function test_operations_dashboard_is_still_safe_when_structured_manager_has_no_scope(): void
    {
        $manager = $this->manager(false);
        $response = $this->actingAs($manager)->get('/operations-manager');
        $response->assertOk()->assertSee('Operations Command Center')->assertSee('0');
    }

    public function test_legacy_admin_home_is_not_hijacked_by_operations_manager_redirect(): void
    {
        $tenant = Tenant::create(['name'=>'Legacy','code'=>'LEGACY','status'=>'ACTIVE']);
        $org = Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'LEGACY-HQ','status'=>'ACTIVE']);
        $admin = User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Legacy Admin',
            'email'=>'legacy-admin@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE',
        ]);

        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }

    public function test_structured_operations_manager_home_redirects_to_operations_command_center(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->get('/dashboard')
            ->assertRedirect(route('operations-manager.dashboard'));
    }

    public function test_multi_role_user_with_operations_manager_membership_redirects_to_operations_command_center(): void
    {
        $manager = $this->manager();
        $manager->update(['role'=>'PROJECT_MANAGER']);

        $this->actingAs($manager)->get('/dashboard')
            ->assertRedirect(route('operations-manager.dashboard'));
    }
}
