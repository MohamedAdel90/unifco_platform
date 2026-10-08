<?php

namespace Tests\Feature;

use App\Models\{ChartAccount,Customer,FinancialDocument,FiscalPeriod,ServiceRequest,User};
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_chart_account_and_period(): void
    {
        $this->seed(); $user=User::firstOrFail();
        $this->actingAs($user)->post('/finance/core/accounts',['code'=>'1000','name'=>'Cash','type'=>'ASSET','normal_balance'=>'DEBIT'])->assertRedirect();
        $this->actingAs($user)->post('/finance/core/periods',['code'=>'2026-08','starts_on'=>'2026-08-01','ends_on'=>'2026-08-31'])->assertRedirect();
        $this->assertDatabaseHas('chart_accounts',['tenant_id'=>$user->tenant_id,'code'=>'1000']);
        $this->assertDatabaseHas('fiscal_periods',['tenant_id'=>$user->tenant_id,'code'=>'2026-08','status'=>'OPEN']);
    }

    public function test_ap_invoice_requires_open_period_and_separate_poster(): void
    {
        $this->seed(); $creator=User::firstOrFail();
        FiscalPeriod::create(['tenant_id'=>$creator->tenant_id,'organization_id'=>$creator->organization_id,'code'=>'2026-08','starts_on'=>'2026-08-01','ends_on'=>'2026-08-31','status'=>'OPEN']);
        foreach ([['2000','Accounts Payable','LIABILITY','CREDIT'],['5000','Expense','EXPENSE','DEBIT']] as [$code,$name,$type,$normal])
            ChartAccount::create(['tenant_id'=>$creator->tenant_id,'organization_id'=>$creator->organization_id,'code'=>$code,'name'=>$name,'type'=>$type,'normal_balance'=>$normal,'posting_allowed'=>true,'status'=>'ACTIVE']);

        $this->actingAs($creator)->post('/finance/core/documents',[
            'document_no'=>'AP-001','document_type'=>'AP_INVOICE','counterparty_name'=>'Vendor A','document_date'=>'2026-08-10','currency'=>'USD','amount'=>150,
            'control_account_code'=>'2000','offset_account_code'=>'5000',
        ])->assertRedirect();
        $document=FinancialDocument::firstOrFail();
        $this->actingAs($creator)->post('/finance/core/documents/'.$document->id.'/post')->assertSessionHasErrors('document');

        $poster=User::create(['tenant_id'=>$creator->tenant_id,'organization_id'=>$creator->organization_id,'name'=>'Finance Poster','email'=>'poster@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $this->actingAs($poster)->post('/finance/core/documents/'.$document->id.'/post')->assertRedirect();
        $this->assertDatabaseHas('financial_documents',['id'=>$document->id,'status'=>'POSTED','open_amount'=>150]);
        $this->assertDatabaseHas('journals',['journal_no'=>'DOC-AP-001','status'=>'POSTED']);
    }

    public function test_accountant_can_post_manager_created_request_invoice_with_customer_link(): void
    {
        $this->seed();
        $this->seed(WorkflowTestUsersSeeder::class);
        $manager=User::where('email','finance@unifco.local')->firstOrFail();
        $accountant=User::where('email','accountant@unifco.local')->firstOrFail();
        $customer=Customer::where('customer_code','WF-TEST-001')->firstOrFail();
        $serviceRequest=ServiceRequest::create([
            'tenant_id'=>$customer->tenant_id,'organization_id'=>$customer->organization_id,
            'customer_id'=>$customer->id,'request_no'=>'SR-UAT-INVOICE-021',
            'request_type'=>'QUOTATION','company_name'=>$customer->name,'email'=>$customer->email,
            'service_category'=>'Maintenance','subject'=>'UAT invoice',
            'details'=>'No real payment due','priority'=>'NORMAL',
            'status'=>'COMPLETED','workflow_stage'=>'COMPLETED',
        ]);
        FiscalPeriod::firstOrCreate(
            ['tenant_id'=>$customer->tenant_id,'code'=>'UAT-'.today()->format('Ym')],
            ['organization_id'=>$customer->organization_id,'starts_on'=>today()->startOfMonth(),
             'ends_on'=>today()->endOfMonth(),'status'=>'OPEN']
        );
        foreach ([['1200','Accounts Receivable','ASSET','DEBIT'],['4100','Service Revenue','REVENUE','CREDIT']] as [$code,$name,$type,$normal]) {
            ChartAccount::firstOrCreate(
                ['tenant_id'=>$customer->tenant_id,'code'=>$code],
                ['organization_id'=>$customer->organization_id,'name'=>$name,'type'=>$type,
                 'normal_balance'=>$normal,'posting_allowed'=>true,'status'=>'ACTIVE']
            );
        }

        $this->actingAs($manager)->post('/finance/core/documents',[
            'document_no'=>'INV-SR-'.$serviceRequest->id,'document_type'=>'AR_INVOICE',
            'counterparty_name'=>$customer->name.' — UAT TEST ONLY',
            'document_date'=>today()->toDateString(),'due_date'=>today()->addDays(30)->toDateString(),
            'currency'=>'SAR','amount'=>300,'control_account_code'=>'1200','offset_account_code'=>'4100',
        ])->assertRedirect();
        $invoice=FinancialDocument::where('document_no','INV-SR-'.$serviceRequest->id)->firstOrFail();
        $this->assertSame($serviceRequest->id,$invoice->service_request_id);
        $this->actingAs($manager)->post('/finance/core/documents/'.$invoice->id.'/post')->assertSessionHasErrors('document');
        $this->actingAs($accountant)->post('/finance/core/documents/'.$invoice->id.'/post')->assertRedirect();
        $this->assertDatabaseHas('financial_documents',[
            'id'=>$invoice->id,'status'=>'POSTED','customer_id'=>$customer->id,
            'service_request_id'=>$serviceRequest->id,'open_amount'=>300,
        ]);
        $this->assertSame($invoice->id,$serviceRequest->fresh()->workflow_context['invoice_id']);
        $this->assertTrue(FinancialDocument::whereKey($invoice->id)->visibleToCustomer()->exists());
    }

    public function test_payment_reduces_open_amount_and_creates_balanced_journal(): void
    {
        $this->seed(); $user=User::firstOrFail();
        FiscalPeriod::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'code'=>'2026-08','starts_on'=>'2026-08-01','ends_on'=>'2026-08-31','status'=>'OPEN']);
        foreach ([['1100','Accounts Receivable','ASSET','DEBIT'],['4000','Revenue','REVENUE','CREDIT'],['1000','Cash','ASSET','DEBIT']] as [$code,$name,$type,$normal])
            ChartAccount::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'code'=>$code,'name'=>$name,'type'=>$type,'normal_balance'=>$normal,'posting_allowed'=>true,'status'=>'ACTIVE']);
        $doc=FinancialDocument::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'document_no'=>'AR-001','document_type'=>'AR_INVOICE','counterparty_name'=>'Customer A','document_date'=>'2026-08-05','currency'=>'USD','amount'=>200,'control_account_code'=>'1100','offset_account_code'=>'4000','status'=>'POSTED','created_by'=>$user->id,'posted_by'=>$user->id,'posted_at'=>now(),'open_amount'=>200]);
        $this->actingAs($user)->post('/finance/core/documents/'.$doc->id.'/pay',['payment_no'=>'RCPT-001','payment_date'=>'2026-08-12','amount'=>75,'cash_account_code'=>'1000'])->assertRedirect();
        $this->assertDatabaseHas('financial_documents',['id'=>$doc->id,'open_amount'=>125]);
        $this->assertDatabaseHas('payments',['payment_no'=>'RCPT-001','amount'=>75]);
    }
}
