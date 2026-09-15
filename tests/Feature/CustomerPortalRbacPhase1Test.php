<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,User};
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalRbacPhase1Test extends TestCase
{
    use RefreshDatabase;

    public function test_every_customer_login_receives_the_complete_unified_company_workspace(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);

        foreach (['workflow.customer@unifco.local','workflow.site.manager@unifco.local','workflow.finance@unifco.local','workflow.viewer@unifco.local'] as $email) {
            $user=User::where('email',$email)->firstOrFail();
            $this->actingAs($user)->get('/customer')
                ->assertOk()
                ->assertSee('UNIFIED CUSTOMER ACCOUNT')
                ->assertSee('Work Orders')
                ->assertSee('Sites &amp; Assets',false)
                ->assertSee('Invoices')
                ->assertSee('Spare Parts')
                ->assertDontSee('Users &amp; Access',false);
        }
    }

    public function test_previous_portal_roles_can_open_every_customer_section(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.finance@unifco.local')->firstOrFail();

        foreach (['sites','assets','work-orders','visits','maintenance','spare-parts','quotations','contracts','invoices','reports','sla','documents'] as $section) {
            $this->actingAs($user)->get('/customer/'.$section)->assertOk();
        }
    }

    public function test_site_scopes_do_not_hide_other_assets_belonging_to_the_same_customer(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($user->customer_id);
        $otherSite=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'WF-JED-01','name'=>'Jeddah Site','city'=>'Jeddah','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$customer->id,'customer_site_id'=>$otherSite->id,'asset_code'=>'WF-A-ALL','name'=>'Company Generator','status'=>'REGISTERED']);

        $this->actingAs($user)->get('/customer/assets')->assertOk()->assertSee('Company Generator');
        $this->actingAs($user)->get('/customer/assets/'.$asset->id)->assertOk();
    }

    public function test_customer_boundary_still_blocks_another_customers_asset(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $foreignCustomer=Customer::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_code'=>'FOREIGN-1','name'=>'Foreign Customer','status'=>'ACTIVE']);
        $foreignAsset=Asset::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$foreignCustomer->id,'asset_code'=>'FOREIGN-A','name'=>'Foreign Asset','status'=>'REGISTERED']);

        $this->actingAs($user)->get('/customer/assets/'.$foreignAsset->id)->assertNotFound();
    }
}
