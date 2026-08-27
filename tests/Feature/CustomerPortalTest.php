<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,FinancialDocument,MaintenancePlan,Organization,ServiceContract,Tenant,User,WorkOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sidebar_opens_separate_customer_scoped_pages(): void
    {
        $tenant = Tenant::create(['name'=>'UNIFCO','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org = Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $customer = Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C001','name'=>'Portal Client','email'=>'client@example.test','status'=>'ACTIVE']);
        $other = Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C002','name'=>'Other Client','email'=>'other@example.test','status'=>'ACTIVE']);
        $user = User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>'Client User','email'=>'portal@example.test','password'=>'VerySecure1234','role'=>'CUSTOMER','status'=>'ACTIVE']);
        $asset = Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'asset_code'=>'GEN-001','name'=>'Main Generator','status'=>'REGISTERED']);
        Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$other->id,'asset_code'=>'OTHER-001','name'=>'Other Asset','status'=>'REGISTERED']);
        ServiceContract::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'contract_no'=>'AMC-001','title'=>'Annual Maintenance','starts_on'=>now()->startOfYear(),'ends_on'=>now()->endOfYear(),'contract_value'=>120000,'currency'=>'SAR','billing_cycle'=>'MONTHLY','status'=>'ACTIVE']);
        MaintenancePlan::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_id'=>$asset->id,'plan_no'=>'PM-001','name'=>'Monthly PM','frequency_type'=>'DAYS','frequency_value'=>30,'next_due_date'=>now()->addDays(7),'priority'=>'NORMAL','status'=>'ACTIVE']);
        WorkOrder::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'work_order_no'=>'WO-001','asset_id'=>$asset->id,'maintenance_type'=>'PREVENTIVE','priority'=>'NORMAL','status'=>'OPEN']);
        FinancialDocument::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'document_no'=>'INV-001','document_type'=>'AR_INVOICE','counterparty_name'=>$customer->name,'document_date'=>now(),'due_date'=>now()->addDays(30),'currency'=>'SAR','amount'=>10000,'control_account_code'=>'AR','offset_account_code'=>'REV','status'=>'POSTED','open_amount'=>10000]);
        FinancialDocument::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$other->id,'document_no'=>'INV-OTHER','document_type'=>'AR_INVOICE','counterparty_name'=>$other->name,'document_date'=>now(),'currency'=>'SAR','amount'=>20000,'control_account_code'=>'AR','offset_account_code'=>'REV','status'=>'POSTED','open_amount'=>20000]);

        $this->post('/login',['email'=>$user->email,'password'=>'VerySecure1234'])->assertRedirect(route('customer.portal'));
        $this->actingAs($user)->get('/customer')->assertOk()->assertSee('Customer 360')->assertSee('Open Work Orders')->assertDontSee('INV-OTHER');
        $this->actingAs($user)->get('/customer/contracts')->assertOk()->assertSee('Contracts')->assertSee('AMC-001')->assertDontSee('INV-001');
        $this->actingAs($user)->get('/customer/assets')->assertOk()->assertSee('GEN-001')->assertDontSee('OTHER-001');
        $this->actingAs($user)->get('/customer/maintenance')->assertOk()->assertSee('PM-001');
        $this->actingAs($user)->get('/customer/work-orders')->assertOk()->assertSee('WO-001')->assertSee('Request Service');
        $this->actingAs($user)->get('/customer/invoices')->assertOk()->assertSee('INV-001')->assertDontSee('INV-OTHER');
        $this->actingAs($user)->get('/customer/sla')->assertOk()->assertSee('SLA &amp; KPIs', false);
        $this->actingAs($user)->get('/customer/documents')->assertOk()->assertSee('Documents');
        $this->actingAs($user)->get('/customer/notifications')->assertOk()->assertSee('Notifications');
    }

    public function test_login_page_matches_client_portal_reference_shell(): void
    {
        $this->get('/login')->assertOk()->assertSee('One Facility Shop')->assertSee('All Your Facility Needs')->assertSee('One Trusted Partner')->assertSee('Welcome back')->assertSee('SECURE &amp; COMPLIANT', false)->assertSee('ROLE BASED ACCESS')->assertSee('RESPONSIVE DESIGN');
    }

    public function test_non_customer_user_cannot_open_customer_portal(): void
    {
        $tenant = Tenant::create(['name'=>'Test','code'=>'TEST','status'=>'ACTIVE']);
        $org = Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $admin = User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>'admin@example.test','password'=>'password12345','role'=>'ADMIN','status'=>'ACTIVE']);
        $this->actingAs($admin)->get('/customer')->assertForbidden();
        $this->actingAs($admin)->get('/customer/contracts')->assertForbidden();
    }
}
