<?php

namespace Tests\Feature;

use App\Models\{Asset,CrmLead,CrmOpportunity,CrmQuotation,Customer,FinancialDocument,Organization,ServiceContract,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalAdvancedOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C1','name'=>'Client One','email'=>'client@example.test','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>'Client','email'=>'client.user@example.test','password'=>'StrongPassword123','role'=>'CUSTOMER','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'asset_code'=>'GEN-1','name'=>'Generator','location_code'=>'RIYADH','status'=>'REGISTERED']);
        $contract=ServiceContract::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'contract_no'=>'CTR-1','title'=>'AMC','starts_on'=>now(),'ends_on'=>now()->addYear(),'contract_value'=>120000,'currency'=>'SAR','billing_cycle'=>'MONTHLY','status'=>'ACTIVE']);
        return compact('tenant','org','customer','user','asset','contract');
    }

    private function opportunity(array $c, string $suffix): CrmOpportunity
    {
        $lead=CrmLead::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'lead_no'=>'LEAD-'.$suffix,
            'name'=>'Portal quotation '.$suffix,'company'=>'Portal Client','email'=>'portal-'.$suffix.'@example.test','status'=>'QUALIFIED',
        ]);
        return CrmOpportunity::create([
            'tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'lead_id'=>$lead->id,'opportunity_no'=>'OPP-'.$suffix,
            'name'=>'Portal opportunity '.$suffix,'stage'=>'PROPOSAL','expected_value'=>7500,'probability'=>50,'status'=>'OPEN',
        ]);
    }

    public function test_customer_can_submit_contract_asset_service_request_with_sla(): void
    {
        $c=$this->context();
        $this->actingAs($c['user'])->post('/customer/service-requests',[
            'service_contract_id'=>$c['contract']->id,'asset_id'=>$c['asset']->id,'service_category'=>'Maintenance',
            'subject'=>'Urgent generator issue','details'=>'Generator is unavailable','site_city'=>'Riyadh','priority'=>'EMERGENCY',
        ])->assertRedirect();

        $this->assertDatabaseHas('service_requests',[
            'customer_id'=>$c['customer']->id,'asset_id'=>$c['asset']->id,'service_contract_id'=>$c['contract']->id,
            'request_type'=>'MAINTENANCE','eligibility'=>'IN_CONTRACT','priority'=>'EMERGENCY','response_sla_minutes'=>10,'resolution_sla_minutes'=>240,'status'=>'OPEN',
        ]);
        $this->assertDatabaseHas('customer_activity_events',['customer_id'=>$c['customer']->id,'event_type'=>'SERVICE_REQUEST_CREATED']);
    }

    public function test_customer_360_exposes_requests_quotations_and_timeline_sections(): void
    {
        $c=$this->context();
        $this->actingAs($c['user'])->get('/customer/requests')->assertOk()->assertSee('Service Requests');
        $this->actingAs($c['user'])->get('/customer/quotations')->assertOk()->assertSee('Quotations');
        $this->actingAs($c['user'])->get('/customer/timeline')->assertOk()->assertSee('Timeline');
        $this->actingAs($c['user'])->get('/customer')->assertOk()->assertSee('Customer 360');
    }

    public function test_customer_can_download_own_invoice_and_contract_as_pdf(): void
    {
        $c=$this->context();
        $invoice=FinancialDocument::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_id'=>$c['customer']->id,'document_no'=>'INV-100','document_type'=>'AR_INVOICE','counterparty_name'=>'Client One','document_date'=>now(),'due_date'=>now()->addDays(30),'currency'=>'SAR','amount'=>5000,'open_amount'=>5000,'control_account_code'=>'AR','offset_account_code'=>'REV','status'=>'POSTED']);

        $this->actingAs($c['user'])->get('/customer/invoices/'.$invoice->id.'/pdf')->assertOk()->assertHeader('Content-Type','application/pdf')->assertSee('%PDF',false);
        $this->actingAs($c['user'])->get('/customer/contracts/'.$c['contract']->id.'/pdf')->assertOk()->assertHeader('Content-Type','application/pdf')->assertSee('%PDF',false);
    }

    public function test_customer_can_approve_only_own_quotation(): void
    {
        $c=$this->context();
        $opportunity=$this->opportunity($c,'1');
        $quotation=CrmQuotation::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'opportunity_id'=>$opportunity->id,'customer_id'=>$c['customer']->id,'quotation_no'=>'QT-1','quotation_date'=>now(),'currency'=>'SAR','amount'=>7500,'status'=>'SENT']);
        $this->actingAs($c['user'])->post('/customer/quotations/'.$quotation->id.'/decision',['decision'=>'APPROVE','notes'=>'Approved'])->assertRedirect();
        $this->assertDatabaseHas('crm_quotations',['id'=>$quotation->id,'status'=>'CUSTOMER_APPROVED','customer_decision_notes'=>'Approved']);
        $this->assertDatabaseHas('customer_activity_events',['customer_id'=>$c['customer']->id,'event_type'=>'QUOTATION_APPROVE']);

        $other=Customer::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'customer_code'=>'C2','name'=>'Other','status'=>'ACTIVE']);
        $otherOpportunity=$this->opportunity($c,'2');
        $otherQuote=CrmQuotation::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'opportunity_id'=>$otherOpportunity->id,'customer_id'=>$other->id,'quotation_no'=>'QT-2','quotation_date'=>now(),'currency'=>'SAR','amount'=>1000,'status'=>'SENT']);
        $this->actingAs($c['user'])->post('/customer/quotations/'.$otherQuote->id.'/decision',['decision'=>'APPROVE'])->assertForbidden();
    }
}
