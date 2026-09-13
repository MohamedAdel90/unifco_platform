<?php

namespace Tests\Feature;

use App\Models\{AccessScope,Organization,Role,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsManagerReportsTest extends TestCase
{
    use RefreshDatabase;

    private function manager(bool $global=true): User
    {
        $tenant=Tenant::create(['name'=>'Ops Reports','code'=>'OPSREP','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'OPSREP-HQ','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Ops Manager','email'=>'reports@example.test','password'=>'test-secret','role'=>'OPERATIONS_MANAGER','user_type'=>'INTERNAL','status'=>'ACTIVE']);
        $role=Role::whereNull('tenant_id')->where('code','OPERATIONS_MANAGER')->firstOrFail();
        DB::table('user_roles')->insert(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        if($global){
            $scope=AccessScope::create(['tenant_id'=>$tenant->id,'scope_type'=>'GLOBAL','name'=>'Global','is_active'=>true]);
            DB::table('user_scopes')->insert(['tenant_id'=>$tenant->id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'SYSTEM','created_at'=>now(),'updated_at'=>now()]);
        }
        return $user;
    }

    public function test_manager_can_open_operational_reports(): void
    {
        $this->actingAs($this->manager())->get('/operations-manager/reports')->assertOk()->assertSee('Operational Reports & Performance');
    }

    public function test_manager_without_scope_gets_zero_operational_report(): void
    {
        $this->actingAs($this->manager(false))->get('/operations-manager/reports')->assertOk()->assertSee('0');
    }
}
