<?php

namespace Tests\Feature;

use App\Models\{Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtIdentityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_jwt_login_me_tenant_enforcement_and_user_api(): void
    {
        config(['app.key'=>'base64:'.base64_encode(random_bytes(32))]);
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'JWT','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'JWT-HQ','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>'jwt-admin@example.test','password'=>'StrongPassword123','role'=>'ADMIN','status'=>'ACTIVE']);

        $login=$this->postJson('/api/v1/auth/login',['email'=>$admin->email,'password'=>'StrongPassword123'])->assertOk()->assertJsonPath('token_type','Bearer');
        $jwt=$login->json('access_token'); $this->assertNotEmpty($jwt);

        $this->withHeader('Authorization','Bearer '.$jwt)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('tenant_id',$tenant->id);
        $this->withHeaders(['Authorization'=>'Bearer '.$jwt,'X-Tenant-Id'=>(string)($tenant->id+99)])->getJson('/api/v1/auth/me')->assertForbidden();
        $this->withHeaders(['Authorization'=>'Bearer '.$jwt,'X-Tenant-Id'=>(string)$tenant->id])->postJson('/api/v1/identity/users',[
            'name'=>'New User','email'=>'new.jwt@example.test','password'=>'AnotherStrong123','role'=>'TECHNICIAN','organization_id'=>$org->id,
        ])->assertCreated()->assertJsonPath('role','TECHNICIAN');
        $this->assertDatabaseHas('users',['email'=>'new.jwt@example.test','tenant_id'=>$tenant->id]);
    }

    public function test_protected_jwt_endpoint_rejects_missing_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
