<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,MaintenancePlan,MaintenanceVisitReport,Organization,ServiceContract,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalAlertsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_maintenance_alert_and_technical_report_pdf_are_customer_scoped(): void
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'U1','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C1','name'=>'Client','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>'Client','email'=>'c@example.test','password'=>'StrongPassword123','role'=>'CUSTOMER','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'asset_code'=>'A1','name'=>'Generator','status'=>'REGISTERED']);
        $contract=ServiceContract::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'contract_no'=>'C-1','title'=>'AMC','starts_on'=>now(),'ends_on'=>now()->addMonths(10),'contract_value'=>1,'currency'=>'SAR','billing_cycle'=>'MONTHLY','status'=>'ACTIVE']);
        MaintenancePlan::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_id'=>$asset->id,'service_contract_id'=>$contract->id,'plan_no'=>'PM-DUE','name'=>'Due PM','frequency_type'=>'DAYS','frequency_value'=>30,'next_due_date'=>now()->addDays(3),'priority'=>'NORMAL','status'=>'ACTIVE']);
        $report=MaintenanceVisitReport::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'service_contract_id'=>$contract->id,'asset_id'=>$asset->id,'report_no'=>'TVR-1','visit_date'=>now(),'visit_type'=>'PREVENTIVE','findings'=>'OK','work_performed'=>'Inspection','technician_name'=>'Tech']);

        $this->actingAs($user)->get('/customer')->assertOk()->assertSee('PM-DUE')->assertSee('صيانة مستحقة');
        $this->actingAs($user)->get('/customer/visit-reports/'.$report->id.'/pdf')->assertOk()->assertHeader('Content-Type','application/pdf')->assertSee('%PDF',false);
    }
}
