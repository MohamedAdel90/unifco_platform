<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,User};
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerPortalRbacPhase1Test extends TestCase
{
    use RefreshDatabase;

    public function test_customer_roles_receive_different_sidebar_sections(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $admin=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $site=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();
        $finance=User::where('email','workflow.finance@unifco.local')->firstOrFail();
        $viewer=User::where('email','workflow.viewer@unifco.local')->firstOrFail();

        $this->actingAs($admin)->get('/customer')->assertOk()->assertSee('CUSTOMER ADMIN')->assertSee('Invoices')->assertSee('Assets')->assertSee('Users &amp; Access',false);
        $this->actingAs($site)->get('/customer')->assertOk()->assertSee('SITE MANAGER')->assertSee('Work Orders')->assertDontSee('Invoices')->assertDontSee('Users &amp; Access',false);
        $this->actingAs($finance)->get('/customer')->assertOk()->assertSee('FINANCE')->assertSee('Invoices')->assertSee('Quotations')->assertDontSee('Work Orders')->assertDontSee('Users &amp; Access',false);
        $this->actingAs($viewer)->get('/customer')->assertOk()->assertSee('READ ONLY')->assertDontSee('Users &amp; Access',false);
    }

    public function test_site_manager_cannot_open_finance_or_users_access(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $site=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();
        $this->actingAs($site)->get('/customer/invoices')->assertForbidden();
        $this->actingAs($site)->get('/customer/users-access')->assertForbidden();
    }

    public function test_site_scope_hides_assets_from_other_sites(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $siteUser=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($siteUser->customer_id);
        $allowedSite=CustomerSite::where('customer_id',$customer->id)->where('site_code','WF-RUH-01')->firstOrFail();
        $otherSite=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'WF-JED-01','name'=>'Jeddah Site','city'=>'Jeddah','status'=>'ACTIVE']);
        $allowed=Asset::create(['tenant_id'=>$siteUser->tenant_id,'organization_id'=>$siteUser->organization_id,'customer_id'=>$customer->id,'customer_site_id'=>$allowedSite->id,'asset_code'=>'WF-A-1','name'=>'Allowed Generator','status'=>'REGISTERED']);
        $hidden=Asset::create(['tenant_id'=>$siteUser->tenant_id,'organization_id'=>$siteUser->organization_id,'customer_id'=>$customer->id,'customer_site_id'=>$otherSite->id,'asset_code'=>'WF-A-2','name'=>'Hidden Generator','status'=>'REGISTERED']);

        $this->actingAs($siteUser)->get('/customer/assets')->assertOk()->assertSee('Allowed Generator')->assertDontSee('Hidden Generator');
        $this->actingAs($siteUser)->get('/customer/assets/'.$hidden->id)->assertNotFound();
        $this->actingAs($siteUser)->get('/customer/assets/'.$allowed->id)->assertOk();
    }

    public function test_viewer_cannot_create_service_request(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $viewer=User::where('email','workflow.viewer@unifco.local')->firstOrFail();
        $this->actingAs($viewer)->post('/customer/service-requests',[
            'service_category'=>'Maintenance','subject'=>'Should fail','details'=>'Read only user','priority'=>'NORMAL',
        ])->assertForbidden();
    }

    public function test_customer_admin_can_create_scoped_site_manager(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $admin=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $site=CustomerSite::where('customer_id',$admin->customer_id)->where('site_code','WF-RUH-01')->firstOrFail();

        $this->actingAs($admin)->post('/customer/users-access',[
            'name'=>'Riyadh Operations User','email'=>'riyadh.ops@customer.test','customer_portal_role'=>'SITE_MANAGER','password'=>'CustomerTest!2026','site_ids'=>[$site->id],
        ])->assertRedirect();

        $user=User::where('email','riyadh.ops@customer.test')->firstOrFail();
        $this->assertSame('CUSTOMER',(string)$user->role);
        $this->assertSame('SITE_MANAGER',(string)$user->customer_portal_role);
        $this->assertSame($admin->customer_id,$user->customer_id);
        $this->assertDatabaseHas('customer_portal_user_scopes',['user_id'=>$user->id,'scope_type'=>'SITE','scope_id'=>$site->id]);
    }

    public function test_customer_admin_cannot_assign_scope_from_another_customer(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $admin=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $otherCustomer=Customer::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'customer_code'=>'OTHER-1','name'=>'Other Customer','status'=>'ACTIVE']);
        $foreignSite=CustomerSite::create(['customer_id'=>$otherCustomer->id,'site_code'=>'OTHER-SITE','name'=>'Other Site','city'=>'Jeddah','status'=>'ACTIVE']);

        $this->actingAs($admin)->post('/customer/users-access',[
            'name'=>'Scoped Viewer','email'=>'scoped.viewer@customer.test','customer_portal_role'=>'VIEWER','password'=>'CustomerTest!2026','site_ids'=>[$foreignSite->id],
        ])->assertRedirect();

        $user=User::where('email','scoped.viewer@customer.test')->firstOrFail();
        $this->assertFalse(DB::table('customer_portal_user_scopes')->where('user_id',$user->id)->where('scope_type','SITE')->where('scope_id',$foreignSite->id)->exists());
    }
}
