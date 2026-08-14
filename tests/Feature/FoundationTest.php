<?php

namespace Tests\Feature;

use App\Models\{Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_dashboard(): void
    {
        $tenant = Tenant::create(['name'=>'Test Tenant','code'=>'TEST','status'=>'ACTIVE']);
        $org = Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $user = User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>'admin@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $this->actingAs($user)->get('/')->assertOk()->assertSee('Platform Dashboard');
    }

    public function test_module_workspace_is_available(): void
    {
        $tenant = Tenant::create(['name'=>'Test Tenant','code'=>'TEST','status'=>'ACTIVE']);
        $user = User::create(['tenant_id'=>$tenant->id,'name'=>'Admin','email'=>'admin@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $this->actingAs($user)->get('/modules/finance')->assertOk()->assertSee('Finance workspace');
    }
}
