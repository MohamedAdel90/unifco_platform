<?php

namespace Tests\Feature;

use App\Models\{Customer,FinancialDocument,ServiceContract,User};
use Database\Seeders\WorkflowTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($admin)->get('/customer/actions')->assertOk()->assertSee('Action Required From You')->assertSee('PH2-INV-001');
        $this->actingAs($finance)->get('/customer/actions')->assertOk()->assertSee('Customer Portal Phase 2',false)->assertSee('PH2-INV-001');
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

        $this->actingAs($site)->get('/customer/actions')->assertOk()->assertDontSee('PH2-HIDDEN-INV')->assertSee('Invoices Due')->assertSee('0');
    }

    public function test_viewer_cannot_accept_completed_work(): void
    {
        $this->seed(WorkflowTestUsersSeeder::class);
        $viewer=User::where('email','workflow.viewer@unifco.local')->firstOrFail();
        $this->actingAs($viewer)->get('/customer/work-acceptance')->assertForbidden();
    }
}
