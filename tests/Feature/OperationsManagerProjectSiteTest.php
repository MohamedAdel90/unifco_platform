<?php

namespace Tests\Feature;

use App\Models\{AccessScope,Customer,CustomerSite,Organization,Project,Role,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsManagerProjectSiteTest extends TestCase
{
    use RefreshDatabase;

    private function manager(bool $global=true): User
    {
        $tenant=Tenant::create(['name'=>'Ops Hierarchy','code'=>'OPS-HIER','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'OPS-HIER-HQ','status'=>'ACTIVE']);
        $user=User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Operations Manager',
            'email'=>'ops-hierarchy@example.test','password'=>'password','role'=>'OPERATIONS_MANAGER','user_type'=>'INTERNAL','status'=>'ACTIVE',
        ]);
        $role=Role::whereNull('tenant_id')->where('code','OPERATIONS_MANAGER')->firstOrFail();
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,
            'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        if($global){
            $scope=AccessScope::create(['tenant_id'=>$tenant->id,'scope_type'=>'GLOBAL','name'=>'Global','is_active'=>true]);
            DB::table('user_scopes')->insert([
                'tenant_id'=>$tenant->id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'SYSTEM',
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        }
        return $user;
    }

    public function test_project_site_overview_shows_only_current_tenant_operating_hierarchy(): void
    {
        $manager=$this->manager();
        $customer=Customer::create([
            'tenant_id'=>$manager->tenant_id,'organization_id'=>$manager->organization_id,
            'customer_code'=>'OPS-C-1','name'=>'Scoped Customer','status'=>'ACTIVE',
        ]);
        CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'OPS-S-1','name'=>'Scoped Site','city'=>'Riyadh','status'=>'ACTIVE']);
        Project::create([
            'tenant_id'=>$manager->tenant_id,'organization_id'=>$manager->organization_id,'project_no'=>'OPS-P-1',
            'name'=>'Scoped Project','customer_id'=>$customer->id,'status'=>'ACTIVE','budget'=>0,
        ]);

        $this->actingAs($manager)->get('/operations-manager/projects-sites')
            ->assertOk()
            ->assertSee('Scoped Project')
            ->assertSee('Scoped Site')
            ->assertSee('Field Capacity Attention')
            ->assertSee('Emergency / Critical Work')
            ->assertSee('Overloaded Technicians')
            ->assertSee('Technician Utilization Snapshot');
    }

    public function test_project_site_overview_is_empty_for_structured_manager_without_scope(): void
    {
        $manager=$this->manager(false);
        $this->actingAs($manager)->get('/operations-manager/projects-sites')
            ->assertOk()->assertSee('No projects visible in the current scope.');
    }
}
