<?php

namespace Tests\Feature;

use App\Models\{Customer,ServiceRequest,User};
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerServiceRequestWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function adminAndRequest(): array
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $admin=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $request=ServiceRequest::create([
            'tenant_id'=>$admin->tenant_id,
            'organization_id'=>$admin->organization_id,
            'customer_id'=>$admin->customer_id,
            'request_no'=>'SR-UNRM-TEST-001',
            'company_name'=>'UNIFCO Workflow Test Customer',
            'request_type'=>'MAINTENANCE',
            'service_category'=>'Maintenance',
            'subject'=>'Routine pump maintenance',
            'details'=>'Pump vibration requires inspection.',
            'priority'=>'NORMAL',
            'status'=>'OPEN',
            'workflow_stage'=>'TRIAGE',
            'workflow_key'=>'MAINTENANCE_ROUTINE',
            'assigned_department'=>'OPERATIONS',
            'next_action'=>'Operations triage and routing',
        ]);
        return [$admin,$request];
    }

    public function test_legacy_requests_section_redirects_to_interactive_workspace(): void
    {
        [$admin]=$this->adminAndRequest();
        $this->actingAs($admin)->get('/customer/requests')
            ->assertRedirect(route('customer.service-requests.index'));
    }

    public function test_customer_can_search_and_open_own_request_360(): void
    {
        [$admin,$request]=$this->adminAndRequest();

        $this->actingAs($admin)->get('/customer/service-requests?q=SR-UNRM-TEST-001')
            ->assertOk()
            ->assertSee('SR-UNRM-TEST-001')
            ->assertSee('View Details')
            ->assertSee(route('customer.service-requests.show',$request),false);

        $this->actingAs($admin)->get(route('customer.service-requests.show',$request))
            ->assertOk()
            ->assertSee('Customer Request 360')
            ->assertSee('TRIAGE')
            ->assertSee('Operations triage and routing')
            ->assertSee('Routine pump maintenance');
    }

    public function test_filters_limit_request_results(): void
    {
        [$admin,$request]=$this->adminAndRequest();
        ServiceRequest::create([
            'tenant_id'=>$admin->tenant_id,
            'organization_id'=>$admin->organization_id,
            'customer_id'=>$admin->customer_id,
            'request_no'=>'SR-UNQ-TEST-002',
            'company_name'=>'UNIFCO Workflow Test Customer',
            'request_type'=>'QUOTATION',
            'service_category'=>'Spare Parts',
            'subject'=>'Spare parts quote',
            'details'=>'Quotation test request.',
            'priority'=>'NORMAL',
            'status'=>'OPEN',
            'workflow_stage'=>'SALES_REVIEW',
        ]);

        $this->actingAs($admin)->get('/customer/service-requests?type=MAINTENANCE&stage=TRIAGE')
            ->assertOk()->assertSee($request->request_no)->assertDontSee('SR-UNQ-TEST-002');
    }

    public function test_customer_cannot_open_another_customers_request(): void
    {
        [$admin]=$this->adminAndRequest();
        $other=Customer::create([
            'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,
            'customer_code'=>'OTHER-REQ','name'=>'Other Request Customer','status'=>'ACTIVE',
        ]);
        $foreign=ServiceRequest::create([
            'tenant_id'=>$admin->tenant_id,
            'organization_id'=>$admin->organization_id,
            'customer_id'=>$other->id,
            'request_no'=>'SR-FOREIGN-001',
            'company_name'=>'Other Request Customer',
            'request_type'=>'MAINTENANCE',
            'service_category'=>'Maintenance',
            'subject'=>'Foreign request',
            'details'=>'Foreign customer request for access control test.',
            'priority'=>'NORMAL',
            'status'=>'OPEN',
            'workflow_stage'=>'TRIAGE',
        ]);

        $this->actingAs($admin)->get(route('customer.service-requests.show',$foreign))->assertNotFound();
    }
}
