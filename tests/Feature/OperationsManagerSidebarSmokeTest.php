<?php

namespace Tests\Feature;

use App\Models\{AccessScope,Organization,Role,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsManagerSidebarSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        $tenant=Tenant::create(['name'=>'Operations','code'=>'OPS-SMOKE','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'OPS-SMOKE-HQ','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Operations Manager','email'=>'ops-smoke@example.test','password'=>'password','role'=>'OPERATIONS_MANAGER','user_type'=>'INTERNAL','status'=>'ACTIVE']);
        $role=Role::whereNull('tenant_id')->where('code','OPERATIONS_MANAGER')->firstOrFail();
        DB::table('user_roles')->insert(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        $scope=AccessScope::create(['tenant_id'=>$tenant->id,'scope_type'=>'GLOBAL','scope_id'=>null,'name'=>'Operations Global','is_active'=>true]);
        DB::table('user_scopes')->insert(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'SYSTEM','created_at'=>now(),'updated_at'=>now()]);
        return $user;
    }

    public function test_all_operations_manager_workspace_entries_render_without_server_errors(): void
    {
        $manager=$this->manager();
        $paths=[
            'customer-onboarding','contracts','customer-portal','preventive-maintenance','parts-consumption',
            'asset-360','meters-readings','reliability','spare-parts-reorder','maintenance-plans',
            'transfers','bins-locations','receiving','material-issues-returns','inventory-movements',
            'project-tasks','project-assets','project-costs','materials-bom','production-tracking',
            'operations-analytics','predictive-analytics',
        ];

        foreach($paths as $workspace){
            $response=$this->actingAs($manager)->get('/workspace/'.$workspace);
            $this->assertLessThan(500,$response->getStatusCode(),"Workspace {$workspace} returned a server error");
            $response->assertDontSee('ready to receive dedicated KPIs',false)
                ->assertDontSee('stable navigation destination',false);
        }
    }

    public function test_primary_operations_manager_pages_do_not_return_server_errors(): void
    {
        $manager=$this->manager();
        foreach(['/operations-manager','/operations-manager/service-requests','/field/operations','/projects','/eam/assets','/maintenance/work-orders','/inventory/stock'] as $path){
            $response=$this->actingAs($manager)->get($path);
            $this->assertLessThan(500,$response->getStatusCode(),"Sidebar destination {$path} returned a server error");
        }
    }
}
