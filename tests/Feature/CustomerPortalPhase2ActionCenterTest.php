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

    public function test_customer_login_receives_financial_actions_regardless_of_legacy_portal_persona(): void
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

        $this->actingAs($admin)->get('/customer')->assertOk()->assertSee('Financial Summary')->assertSee('Action Required');
        $this->actingAs($admin)->get('/customer/actions')->assertOk()->assertSee('Action Required From You')->assertSee('PH2-INV-001');
        $this->actingAs($finance)->get('/customer/actions')->assertOk()->assertSee('Action Required From You')->assertSee('PH2-INV-001');
    }

    public function test_legacy_site_manager_persona_still_sees_customer_financial_actions(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $site=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($site->customer_id);

        FinancialDocument::create([
            'tenant_id'=>$site->tenant_id,'organization_id'=>$site->organization_id,'customer_id'=>$customer->id,
            'document_no'=>'PH2-FULL-INV','document_type'=>'AR_INVOICE','counterparty_name'=>$customer->name,
            'document_date'=>today(),'due_date'=>today()->addDays(3),'currency'=>'SAR','amount'=>1000,'open_amount'=>1000,
            'control_account_code'=>'AR','offset_account_code'=>'REV','status'=>'POSTED',
        ]);

        $this->actingAs($site)->get('/customer/actions')->assertOk()->assertSee('PH2-FULL-INV')->assertSee('Invoices Due');
    }

    public function test_customer_login_can_submit_contract_renewal_once(): void
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

    public function test_invoice_query_is_available_to_full_customer_login_even_with_legacy_persona(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $finance=User::where('email','workflow.finance@unifco.local')->firstOrFail();
        $site=User::where('email','workflow.site.manager@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($finance->customer_id);
        $invoice=$this->invoice($finance,$customer,'PH2-QUERY-INV');

        $this->actingAs($finance)->post('/customer/invoices/'.$invoice->id.'/query',['notes'=>'Please confirm payment allocation'])->assertRedirect();
        $this->assertDatabaseHas('customer_portal_action_requests',['customer_id'=>$customer->id,'action_type'=>'INVOICE_QUERY','reference_id'=>$invoice->id]);
        $this->actingAs($site)->post('/customer/invoices/'.$invoice->id.'/query',['notes'=>'Full customer account follow-up'])->assertRedirect();
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

    public function test_legacy_viewer_persona_has_full_customer_work_acceptance_access(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $viewer=User::where('email','workflow.viewer@unifco.local')->firstOrFail();
        $this->actingAs($viewer)->get('/customer/work-acceptance')->assertOk();
    }

    public function test_action_center_keeps_category_summary_visible_when_no_actions_exist(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $admin=User::where('email','workflow.customer@unifco.local')->firstOrFail();

        $this->actingAs($admin)->get('/customer/actions')
            ->assertOk()
            ->assertSee('Action categories')
            ->assertSee('No action is required from you right now')
            ->assertSee('Submitted Actions');
    }

    public function test_action_center_can_filter_to_invoice_actions_and_marks_overdue_items(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $admin=User::where('email','workflow.customer@unifco.local')->firstOrFail();
        $customer=Customer::findOrFail($admin->customer_id);
        $invoice=$this->invoice($admin,$customer,'PH2-OVERDUE-INV');
        $invoice->update(['due_date'=>today()->subDays(2)]);

        $this->actingAs($admin)->get('/customer/actions?type=invoices')
            ->assertOk()
            ->assertSee('PH2-OVERDUE-INV')
            ->assertSee('Overdue by 2 days')
            ->assertSee('Invoices Requiring Attention')
            ->assertDontSee('Quotations Awaiting Your Decision');
    }

    public function test_action_center_arabic_copy_preserves_the_same_action_categories(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $admin=User::where('email','workflow.customer@unifco.local')->firstOrFail();

        app()->setLocale('ar');
        $this->actingAs($admin)->get('/customer/actions')
            ->assertOk()
            ->assertSee('فئات الإجراءات')
            ->assertSee('عروض الأسعار')
            ->assertSee('الفواتير المستحقة')
            ->assertSee('الإجراءات المرسلة');
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
