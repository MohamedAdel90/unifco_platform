<?php

namespace Tests\Feature;

use App\Models\{Customer,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalMobileLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_portal_renders_mobile_sign_out_control(): void
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'MOBILE-1','name'=>'Mobile Client','status'=>'ACTIVE']);
        $user=User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>'Mobile User',
            'email'=>'mobile.customer@example.test','password'=>'StrongPassword123','role'=>'CUSTOMER','status'=>'ACTIVE','customer_portal_role'=>'CUSTOMER_ADMIN',
        ]);

        $this->actingAs($user)->get('/customer')
            ->assertOk()
            ->assertSee('customer-mobile-logout',false)
            ->assertSee('data-customer-mobile-logout',false)
            ->assertSee('Sign out');
    }

    public function test_mobile_logout_control_is_not_injected_for_internal_user(): void
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO2','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ2','status'=>'ACTIVE']);
        $user=User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Internal User','email'=>'internal.mobile@example.test',
            'password'=>'StrongPassword123','role'=>'ADMIN','status'=>'ACTIVE',
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertDontSee('data-customer-mobile-logout',false);
    }
}
