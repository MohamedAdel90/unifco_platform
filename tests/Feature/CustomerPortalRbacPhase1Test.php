<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,User};
use App\Services\CustomerPortalAccessService;
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalRbacPhase1Test extends TestCase
{
    use RefreshDatabase;

    public function test_customer_login_has_one_full_cross_functional_portal_scope(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $access=app(CustomerPortalAccessService::class);

        // CUSTOMER_ADMIN remains only as a compatibility label. Access is now
        // customer-account based, not persona/department based.
        $this->assertSame('CUSTOMER_ADMIN',$access->role($user));
        foreach(['requests','sites','assets','work-orders','maintenance','spare-parts','quotations','contracts','invoices','reports','documents','notifications'] as $section){
            $this->assertTrue($access->canSection($user,$section),$section.' should be visible to the single customer login.');
        }

        $this->assertTrue($access->canCreateServiceRequest($user));
        $this->assertTrue($access->canDecideQuotation($user));
        $this->assertTrue($access->canAcceptWork($user));
        $this->assertFalse($access->isReadOnly($user));
    }

    public function test_legacy_customer_portal_role_does_not_hide_finance_or_operations(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();

        // Legacy persona values may still exist during migration, but the new
        // policy is customer-account based: one login sees the whole customer.
        $this->actingAs($user)->get('/customer/invoices')->assertOk();
        $this->actingAs($user)->get('/customer/contracts')->assertOk();
        $this->actingAs($user)->get('/customer/assets')->assertOk();
        $this->actingAs($user)->get('/customer/work-orders')->assertOk();
    }

    public function test_single_customer_login_is_not_allowed_to_create_more_portal_users(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $access=app(CustomerPortalAccessService::class);

        $this->assertFalse($access->canManageUsers($user));
        $this->actingAs($user)->get('/customer/users-access')->assertForbidden();
    }

    public function test_single_login_sees_all_sites_and_assets_but_never_another_customer(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($user->customer_id);

        $siteA=CustomerSite::where('customer_id',$customer->id)->firstOrFail();
        $siteB=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'WF-JED-01','name'=>'Jeddah Site','city'=>'Jeddah','status'=>'ACTIVE']);
        $assetA=Asset::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$customer->id,'customer_site_id'=>$siteA->id,'asset_code'=>'FULL-A-1','name'=>'Riyadh Generator','status'=>'REGISTERED']);
        $assetB=Asset::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$customer->id,'customer_site_id'=>$siteB->id,'asset_code'=>'FULL-A-2','name'=>'Jeddah Generator','status'=>'REGISTERED']);

        $other=Customer::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_code'=>'OTHER-FULL','name'=>'Other Customer','status'=>'ACTIVE']);
        $otherSite=CustomerSite::create(['customer_id'=>$other->id,'site_code'=>'OTHER-SITE','name'=>'Other Site','city'=>'Dammam','status'=>'ACTIVE']);
        $foreign=Asset::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$other->id,'customer_site_id'=>$otherSite->id,'asset_code'=>'FOREIGN-A-1','name'=>'Foreign Generator','status'=>'REGISTERED']);

        $this->actingAs($user)->get('/customer/assets')
            ->assertOk()
            ->assertSee($assetA->name)
            ->assertSee($assetB->name)
            ->assertDontSee($foreign->name);
        $this->actingAs($user)->get('/customer/assets/'.$foreign->id)->assertNotFound();
    }
}
