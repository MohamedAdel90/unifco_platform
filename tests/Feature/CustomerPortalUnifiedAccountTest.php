<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,ServiceRequest,User};
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalUnifiedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pages_share_the_unified_portal_shell(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $request=ServiceRequest::where('customer_id',$user->customer_id)->first();

        $this->actingAs($user)->get('/customer/actions')->assertOk()->assertSee('UNIFIED CUSTOMER ACCOUNT')->assertSee('Inbox &amp; Support',false);
        $this->actingAs($user)->get('/customer/service-requests')->assertOk()->assertSee('UNIFIED CUSTOMER ACCOUNT')->assertSee('New Service Request');
        $this->actingAs($user)->get('/customer/inbox')->assertOk()->assertSee('UNIFIED CUSTOMER ACCOUNT')->assertSee('Inbox &amp; Support',false);
        $this->actingAs($user)->get('/customer/profile?lang=en')->assertOk()->assertSee('UNIFIED CUSTOMER ACCOUNT')->assertSee('Unified Login Security');
        $this->actingAs($user)->get('/customer/work-acceptance')->assertOk()->assertSee('UNIFIED CUSTOMER ACCOUNT')->assertSee('Company Decision History');
        if($request){
            $this->actingAs($user)->get(route('customer.service-requests.show',$request))->assertOk()->assertSee('UNIFIED CUSTOMER ACCOUNT')->assertSee('Related Records');
        }
    }

    public function test_global_search_finds_only_records_from_the_authenticated_customer(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($user->customer_id);
        Asset::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$customer->id,'asset_code'=>'SEARCH-ASSET-001','name'=>'Customer Search Pump','status'=>'REGISTERED']);
        $foreign=Customer::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_code'=>'SEARCH-FOREIGN','name'=>'Foreign Search Customer','status'=>'ACTIVE']);
        Asset::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$foreign->id,'asset_code'=>'SEARCH-ASSET-FOREIGN','name'=>'Foreign Search Pump','status'=>'REGISTERED']);

        $this->actingAs($user)->get('/customer/search?q=Search+Pump')
            ->assertOk()->assertSee('Customer Search Pump')->assertDontSee('Foreign Search Pump');
    }

    public function test_unified_request_form_prefills_the_authenticated_customer_and_supports_presets(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($user->customer_id);

        $this->actingAs($user)->get('/request-service?emergency=1')
            ->assertOk()->assertSee('value="'.$customer->customer_code.'"',false)->assertSee('const preset={emergency:true',false);
        $this->actingAs($user)->get('/request-service?quotation=1&subtype=parts')
            ->assertOk()->assertSee('SPARE_PARTS_QUOTE',false)->assertSee('quotation:true',false);
        $this->actingAs($user)->get('/request-service?consultation=1')
            ->assertOk()->assertSee('consultation:true',false);
    }

    public function test_customer_dashboard_presents_a_decision_ready_operating_summary(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $user=User::where('email','workflow.customer@unifco.local')->firstOrFail();

        $this->actingAs($user)->get('/customer?days=30')
            ->assertOk()
            ->assertSee('Open Requests')
            ->assertSee('Work Orders Status')
            ->assertSee('Asset Health')
            ->assertSee('Contracts & SLA', false)
            ->assertSee('Financial Summary')
            ->assertSee('Request Trend')
            ->assertSee('Technical Consultation');
    }
}
