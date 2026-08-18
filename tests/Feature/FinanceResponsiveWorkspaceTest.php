<?php

namespace Tests\Feature;

use App\Models\{FinancialDocument,Journal,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceResponsiveWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_module_uses_responsive_workspace_with_live_metrics(): void
    {
        $tenant = Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO-FIN','status'=>'ACTIVE']);
        $org = Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ-FIN','status'=>'ACTIVE']);
        $user = User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Finance User','email'=>'finance@example.test','password'=>'password12345','role'=>'ADMIN','status'=>'ACTIVE']);

        Journal::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'journal_no'=>'JE-001','journal_date'=>now(),'description'=>'Responsive finance test','status'=>'POSTED']);
        FinancialDocument::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'document_no'=>'AR-001','document_type'=>'AR_INVOICE','counterparty_name'=>'Client','document_date'=>now(),'currency'=>'SAR','amount'=>12500,'open_amount'=>4500,'control_account_code'=>'AR','offset_account_code'=>'REV','status'=>'POSTED']);
        FinancialDocument::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'document_no'=>'AP-001','document_type'=>'AP_INVOICE','counterparty_name'=>'Vendor','document_date'=>now(),'currency'=>'SAR','amount'=>7000,'open_amount'=>2100,'control_account_code'=>'AP','offset_account_code'=>'EXP','status'=>'POSTED']);

        $this->actingAs($user)->get('/modules/finance')
            ->assertOk()
            ->assertSee('Finance workspace')
            ->assertSee('Financial control at a glance')
            ->assertSee('JE-001')
            ->assertSee('4,500.00')
            ->assertSee('2,100.00')
            ->assertSee('@media(max-width:700px)', false)
            ->assertSee('@media(max-width:440px)', false)
            ->assertSee('#1e315b', false)
            ->assertSee('#132137', false)
            ->assertSee('#ce122d', false);
    }
}
