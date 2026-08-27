<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,Organization,Tenant,User};
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

        $this->actingAs($admin)->get('/customer')->assertOk()->assertSee('CUSTOMER ADMIN')->assertSee('Invoices')->assertSee('Assets');
        $this->actingAs($site)->get('/customer')->assertOk()->assertSee('SITE MANAGER')->assertSee('Work Orders')->assertDontSee('Invoices');
        $this->actingAs($finance)->get('/customer')->assertOk()->assertSee('FINANCE')->assertSee('Invoices')->assertSee('Quotations')->assertDontSee('Work Orders');
        $this->actingAs($viewer)->get('/customer')->assertOk()->assertSee('READ ONLY');
    }

    public function test_site_manager_cannot_open_finance_section_or_decide_quotation(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $site=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();
        $this->actingAs($site)->get('/customer/invoices')->assertForbidden();
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
}
