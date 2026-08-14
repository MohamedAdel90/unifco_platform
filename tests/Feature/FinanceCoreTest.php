<?php

namespace Tests\Feature;

use App\Models\{ChartAccount,FinancialDocument,FiscalPeriod,User};
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
        FiscalPeriod::create(['organization_id'=>$creator->organization_id,'code'=>'2026-08','starts_on'=>'2026-08-01','ends_on'=>'2026-08-31','status'=>'OPEN']);
        foreach ([['2000','Accounts Payable','LIABILITY','CREDIT'],['5000','Expense','EXPENSE','DEBIT']] as [$code,$name,$type,$normal])
            ChartAccount::create(['organization_id'=>$creator->organization_id,'code'=>$code,'name'=>$name,'type'=>$type,'normal_balance'=>$normal,'posting_allowed'=>true,'status'=>'ACTIVE']);

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

    public function test_payment_reduces_open_amount_and_creates_balanced_journal(): void
    {
        $this->seed(); $user=User::firstOrFail();
        FiscalPeriod::create(['organization_id'=>$user->organization_id,'code'=>'2026-08','starts_on'=>'2026-08-01','ends_on'=>'2026-08-31','status'=>'OPEN']);
        foreach ([['1100','Accounts Receivable','ASSET','DEBIT'],['4000','Revenue','REVENUE','CREDIT'],['1000','Cash','ASSET','DEBIT']] as [$code,$name,$type,$normal])
            ChartAccount::create(['organization_id'=>$user->organization_id,'code'=>$code,'name'=>$name,'type'=>$type,'normal_balance'=>$normal,'posting_allowed'=>true,'status'=>'ACTIVE']);
        $doc=FinancialDocument::create(['organization_id'=>$user->organization_id,'document_no'=>'AR-001','document_type'=>'AR_INVOICE','counterparty_name'=>'Customer A','document_date'=>'2026-08-05','currency'=>'USD','amount'=>200,'control_account_code'=>'1100','offset_account_code'=>'4000','status'=>'POSTED','created_by'=>$user->id,'posted_by'=>$user->id,'posted_at'=>now(),'open_amount'=>200]);
        $this->actingAs($user)->post('/finance/core/documents/'.$doc->id.'/pay',['payment_no'=>'RCPT-001','payment_date'=>'2026-08-12','amount'=>75,'cash_account_code'=>'1000'])->assertRedirect();
        $this->assertDatabaseHas('financial_documents',['id'=>$doc->id,'open_amount'=>125]);
        $this->assertDatabaseHas('payments',['payment_no'=>'RCPT-001','amount'=>75]);
    }
}
