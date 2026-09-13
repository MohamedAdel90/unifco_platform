<?php

namespace Tests\Feature;

use App\Models\{AccessScope, Organization, Role, Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsManagerFieldOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function manager(bool $global=true): User
    {
        $tenant=Tenant::create(['name'=>'Ops Field','code'=>'OPS-FIELD','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'OPS-FIELD-HQ','status'=>'ACTIVE']);
        $user=User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Operations Manager',
            'email'=>'ops-field@example.test','password'=>'password','role'=>'OPERATIONS_MANAGER','user_type'=>'INTERNAL','status'=>'ACTIVE',
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

    public function test_operations_manager_can_open_field_operations_workspace(): void
    {
        $this->actingAs($this->manager())->get('/field/operations')->assertOk();
    }

    public function test_operations_manager_without_scope_can_open_empty_scoped_workspace(): void
    {
        $this->actingAs($this->manager(false))->get('/field/operations')->assertOk();
    }

    public function test_operations_manager_does_not_gain_inspection_template_administration(): void
    {
        $manager=$this->manager();
        $this->actingAs($manager)->post('/field/inspection-templates',[
            'template_no'=>'OPS-TPL-1','name'=>'Restricted Template','checklist'=>"One\nTwo",
        ])->assertForbidden();
    }
}
