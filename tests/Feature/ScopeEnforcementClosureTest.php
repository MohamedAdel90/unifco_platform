<?php

namespace Tests\Feature;

use App\Models\{AccessScope,Customer,Organization,Project,Role,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScopeEnforcementClosureTest extends TestCase
{
    use RefreshDatabase;

    private function structuredUser(Tenant $tenant, Organization $organization, string $email): User
    {
        $user=User::create([
            'tenant_id'=>$tenant->id,
            'organization_id'=>$organization->id,
            'name'=>'Scoped User',
            'email'=>$email,
            'password'=>'password',
            'role'=>'SYSTEM_ADMIN',
            'status'=>'ACTIVE',
        ]);

        $role=Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail();
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,
            'user_id'=>$user->id,
            'role_id'=>$role->id,
            'is_primary'=>true,
            'granted_at'=>now(),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return $user;
    }

    private function grantScope(User $user, string $type, ?int $scopeId=null, ?string $name=null): AccessScope
    {
        $scope=AccessScope::create([
            'tenant_id'=>$user->tenant_id,
            'scope_type'=>$type,
            'scope_id'=>$scopeId,
            'name'=>$name ?: $type,
            'is_active'=>true,
        ]);

        DB::table('user_scopes')->insert([
            'tenant_id'=>$user->tenant_id,
            'user_id'=>$user->id,
            'access_scope_id'=>$scope->id,
            'source'=>'DIRECT',
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return $scope;
    }

    private function project(Tenant $tenant, Organization $organization, string $number): Project
    {
        return Project::withoutGlobalScopes()->create([
            'tenant_id'=>$tenant->id,
            'organization_id'=>$organization->id,
            'project_no'=>$number,
            'name'=>'Project '.$number,
            'status'=>'ACTIVE',
        ]);
    }

    public function test_global_scope_sees_all_rows_in_own_tenant_only(): void
    {
        $tenant=Tenant::create(['name'=>'Scope A','code'=>'SCOPE-A','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ A','code'=>'HQ-A','status'=>'ACTIVE']);
        $otherTenant=Tenant::create(['name'=>'Scope B','code'=>'SCOPE-B','status'=>'ACTIVE']);
        $otherOrg=Organization::create(['tenant_id'=>$otherTenant->id,'name'=>'HQ B','code'=>'HQ-B','status'=>'ACTIVE']);
        $user=$this->structuredUser($tenant,$org,'global-scope@example.test');
        $this->grantScope($user,'GLOBAL');

        $first=$this->project($tenant,$org,'A-1');
        $second=$this->project($tenant,$org,'A-2');
        $foreign=$this->project($otherTenant,$otherOrg,'B-1');

        $this->actingAs($user);
        $visible=Project::query()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$first->id,$second->id],$visible);
        $this->assertNotContains($foreign->id,$visible);
    }

    public function test_project_scope_filters_list_and_detail_queries(): void
    {
        $tenant=Tenant::create(['name'=>'Project Scope','code'=>'PROJECT-SCOPE','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ-PROJ','status'=>'ACTIVE']);
        $user=$this->structuredUser($tenant,$org,'project-scope@example.test');
        $allowed=$this->project($tenant,$org,'P-1');
        $blocked=$this->project($tenant,$org,'P-2');
        $this->grantScope($user,'PROJECT',$allowed->id,'Assigned Project');

        $this->actingAs($user);
        $this->assertSame([$allowed->id],Project::query()->pluck('id')->all());
        $this->assertNotNull(Project::query()->find($allowed->id));
        $this->assertNull(Project::query()->find($blocked->id));
    }

    public function test_structured_user_without_scope_gets_zero_operational_rows(): void
    {
        $tenant=Tenant::create(['name'=>'No Scope','code'=>'NO-SCOPE','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ-NONE','status'=>'ACTIVE']);
        $user=$this->structuredUser($tenant,$org,'no-scope@example.test');
        $this->project($tenant,$org,'N-1');

        $this->actingAs($user);
        $this->assertSame(0,Project::query()->count());
    }

    public function test_customer_root_scope_filters_customer_queries(): void
    {
        $tenant=Tenant::create(['name'=>'Customer Scope','code'=>'CUSTOMER-SCOPE','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ-CUST','status'=>'ACTIVE']);
        $user=$this->structuredUser($tenant,$org,'customer-scope@example.test');

        $allowed=Customer::withoutGlobalScopes()->create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C-1','name'=>'Allowed','status'=>'ACTIVE',
        ]);
        $blocked=Customer::withoutGlobalScopes()->create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C-2','name'=>'Blocked','status'=>'ACTIVE',
        ]);
        $this->grantScope($user,'CUSTOMER',$allowed->id,'Allowed Customer');

        $this->actingAs($user);
        $this->assertSame([$allowed->id],Customer::query()->pluck('id')->all());
        $this->assertNull(Customer::query()->find($blocked->id));
    }
}
