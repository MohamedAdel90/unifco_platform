<?php

namespace Tests\Feature;

use App\Models\{Customer,FinancialDocument,ServiceContract,User};
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerPortalPhase2ActionCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_admin_and_finance_receive_financial_actions(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $admin=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $finance=User::where('email','workflow.finance@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($admin->customer_id);

        FinancialDocument::create([
            'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'customer_id'=>$customer->id,
            'document_no'=>'PH2-INV-001','document_type'=>'AR_INVOICE','counterparty_name'=>$customer->name,
            'document_date'=>today(),'due_date'=>today()->addDays(5),'currency'=>'SAR','amount'=>9000,'open_amount'=>9000,
            'control_account_code'=>'AR','offset_account_code'=>'REV','status'=>'POSTED',
        ]);

        $this->actingAs($admin)->get('/customer')->assertOk()->assertSee('Action Required From You');
        $this->actingAs($admin)->get('/customer/actions')->assertOk()->assertSee('Action Required From You')->assertSee('PH2-INV-001');
        $this->actingAs($finance)->get('/customer/actions')->assertOk()->assertSee('Phase 2 customer workflow center')->assertSee('PH2-INV-001');
    }

    public function test_site_manager_action_center_hides_financial_actions(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $site=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($site->customer_id);

        FinancialDocument::create([
            'tenant_id'=>$site->tenant_id,'organization_id'=>$site->organization_id,'customer_id'=>$customer->id,
            'document_no'=>'PH2-HIDDEN-INV','document_type'=>'AR_INVOICE','counterparty_name'=>$customer->name,
            'document_date'=>today(),'due_date'=>today()->addDays(3),'currency'=>'SAR','amount'=>1000,'open_amount'=>1000,
            'control_account_code'=>'AR','offset_account_code'=>'REV','status'=>'POSTED',
        ]);

        $this->actingAs($site)->get('/customer/actions')->assertOk()->assertDontSee('PH2-HIDDEN-INV')->assertSee('Invoices Due');
    }

    public function test_customer_admin_can_submit_contract_renewal_once(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $admin=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $contract=ServiceContract::create([
            'tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'customer_id'=>$admin->customer_id,
            'contract_no'=>'PH2-CTR-001','title'=>'Renewal Contract','starts_on'=>today()->subYear(),'ends_on'=>today()->addDays(30),
            'contract_value'=>120000,'currency'=>'SAR','billing_cycle'=>'MONTHLY','status'=>'ACTIVE',
        ]);

        $this->actingAs($admin)->post('/customer/contracts/'.$contract->id.'/renewal-request',['notes'=>'Keep current SLA'])->assertRedirect();
        $this->assertDatabaseHas('customer_portal_action_requests',['customer_id'=>$admin->customer_id,'action_type'=>'CONTRACT_RENEWAL','reference_id'=>$contract->id,'status'=>'OPEN']);
        $this->assertDatabaseHas('customer_activity_events',['customer_id'=>$admin->customer_id,'event_type'=>'CONTRACT_RENEWAL_REQUESTED','reference_id'=>$contract->id]);
    }

    public function test_finance_can_submit_invoice_query_but_site_manager_cannot(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $finance=User::where('email','workflow.finance@unifco.local')->firstOrFail();
        $site=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($finance->customer_id);
        $invoice=$this->invoice($finance,$customer,'PH2-QUERY-INV');

        $this->actingAs($finance)->post('/customer/invoices/'.$invoice->id.'/query',['notes'=>'Please confirm payment allocation'])->assertRedirect();
        $this->assertDatabaseHas('customer_portal_action_requests',['customer_id'=>$customer->id,'action_type'=>'INVOICE_QUERY','reference_id'=>$invoice->id]);
        $this->actingAs($site)->post('/customer/invoices/'.$invoice->id.'/query',['notes'=>'Should be blocked'])->assertForbidden();
    }

    public function test_payment_proof_reaches_internal_finance_and_can_be_resolved(): void
    {
        Storage::fake('local');
        $this->seed(WorkflowTestUsersSeeder::class);
        $customerFinance=User::where('email','workflow.finance@unifco.local')->firstOrFail();
        $internalFinance=User::where('email','finance@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($customerFinance->customer_id);
        $invoice=$this->invoice($customerFinance,$customer,'PH2-PROOF-INV');

        $this->actingAs($customerFinance)->post('/customer/invoices/'.$invoice->id.'/payment-proof',[
            'proof'=>UploadedFile::fake()->create('payment.pdf',200,'application/pdf'),'notes'=>'Bank transfer reference 123',
        ])->assertRedirect();

        $action=\App\Models\CustomerPortalActionRequest::where('action_type','PAYMENT_PROOF')->firstOrFail();
        Storage::disk('local')->assertExists($action->attachment_path);
        $this->actingAs($internalFinance)->get('/workflow/customer-actions')->assertOk()->assertSee('PAYMENT PROOF')->assertSee('payment.pdf');
        $this->actingAs($internalFinance)->post('/workflow/customer-actions/'.$action->id.'/resolve',[
            'decision'=>'RESOLVED','resolution_notes'=>'Payment proof verified and sent for allocation.',
        ])->assertRedirect();
        $this->assertDatabaseHas('customer_portal_action_requests',['id'=>$action->id,'status'=>'RESOLVED']);
        $this->assertDatabaseHas('customer_activity_events',['customer_id'=>$customer->id,'event_type'=>'CUSTOMER_ACTION_RESOLVED']);
    }

    public function test_viewer_cannot_accept_completed_work(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $viewer=User::where('email','workflow.viewer@unifco.local')->firstOrFail();
        $this->actingAs($viewer)->get('/customer/work-acceptance')->assertForbidden();
    }

    private function invoice(User $user,Customer $customer,string $number): FinancialDocument
    {
        return FinancialDocument::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'customer_id'=>$customer->id,
            'document_no'=>$number,'document_type'=>'AR_INVOICE','counterparty_name'=>$customer->name,
            'document_date'=>today(),'due_date'=>today()->addDays(7),'currency'=>'SAR','amount'=>3000,'open_amount'=>3000,
            'control_account_code'=>'AR','offset_account_code'=>'REV','status'=>'POSTED',
        ]);
    }
}
