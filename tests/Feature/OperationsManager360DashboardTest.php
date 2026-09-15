<?php

namespace Tests\Feature;

use App\Models\{AccessScope, Organization, Role, Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsManager360DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        $tenant = Tenant::create(['name'=>'Operations 360','code'=>'OPS360','status'=>'ACTIVE']);
        $org = Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'OPS360-HQ','status'=>'ACTIVE']);
        $user = User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Operations Manager',
            'email'=>'operations-360@example.test','password'=>'password','role'=>'OPERATIONS_MANAGER',
            'user_type'=>'INTERNAL','status'=>'ACTIVE',
        ]);
        $role = Role::whereNull('tenant_id')->where('code','OPERATIONS_MANAGER')->firstOrFail();
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,
            'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $scope = AccessScope::create(['tenant_id'=>$tenant->id,'scope_type'=>'GLOBAL','scope_id'=>null,'name'=>'Operations 360 Global','is_active'=>true]);
        DB::table('user_scopes')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'SYSTEM',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        return $user;
    }

    public function test_operations_manager_dashboard_is_an_action_oriented_360_control_center(): void
    {
        $this->actingAs($this->manager())->get('/operations-manager')
            ->assertOk()
            ->assertSee('Operations Command Center')
            ->assertSee('Action Required')
            ->assertSee('SLA compliance')
            ->assertSee('Unassigned Requests')
            ->assertSee('Service Request Control Queue')
            ->assertSee('Priority Work Queue')
            ->assertSee('Operational Boundaries')
            ->assertSee('Service Requests & SLA')
            ->assertSee('Field Operations')
            ->assertSee('Projects, Sites & Capacity');
    }
}
