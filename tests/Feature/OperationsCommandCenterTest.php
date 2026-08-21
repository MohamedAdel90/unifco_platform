<?php

namespace Tests\Feature;

use App\Models\{Customer,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $tenant=Tenant::create(['name'=>'Command Center Tenant','code'=>'CMD','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        return User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Command Center Admin',
            'email'=>'command-center@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE',
        ]);
    }

    public function test_admin_can_render_role_aware_command_center(): void
    {
        $admin=$this->admin();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('UNIFCO Operations Command Center')
            ->assertSee('EXECUTIVE VIEW')
            ->assertSee('Global Scope Filters')
            ->assertSee('Month-over-Month')
            ->assertSee('Operational Health Score')
            ->assertSee('Action Required');
    }

    public function test_dashboard_accepts_customer_scope_filter(): void
    {
        $admin=$this->admin();
        $customer=Customer::create([
            'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,
            'customer_code'=>'CUST-001','name'=>'Scoped Customer','status'=>'ACTIVE',
        ]);

        $this->actingAs($admin)->get('/dashboard?customer_id='.$customer->id.'&days=60')
            ->assertOk()
            ->assertSee('Scoped Customer')
            ->assertSee('FILTERED SCOPE')
            ->assertSee('60 days');
    }

    public function test_customer_role_is_redirected_to_customer_portal(): void
    {
        $admin=$this->admin();
        $customer=Customer::create([
            'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,
            'customer_code'=>'PORTAL-001','name'=>'Portal Customer','status'=>'ACTIVE',
        ]);
        $user=User::create([
            'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'customer_id'=>$customer->id,
            'name'=>'Portal User','email'=>'portal-user@example.test','password'=>'password','role'=>'CUSTOMER','status'=>'ACTIVE',
        ]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('customer.portal'));
    }
}
