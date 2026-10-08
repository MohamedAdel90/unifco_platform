<?php

namespace Tests\Feature;

use App\Models\{Asset,CrmOpportunity,CrmQuotation,Customer,Organization,PublicServiceRequest,ServiceRequest,Tenant,WorkOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalIntakeAssetReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function legacyRequests(): array
    {
        $tenant = Tenant::create(['name' => 'UNIFCO', 'code' => 'INTAKE-TEST', 'status' => 'ACTIVE']);
        $organization = Organization::create(['tenant_id' => $tenant->id, 'name' => 'HQ', 'code' => 'INTAKE-HQ', 'status' => 'ACTIVE']);
        $customer = Customer::create([
            'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
            'customer_code' => 'INTAKE-CUSTOMER', 'name' => 'Intake Customer',
            'email' => 'intake@example.test', 'phone' => '0500000111', 'status' => 'ACTIVE',
        ]);
        $placeholder = Asset::create([
            'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
            'asset_code' => 'PUBLIC-SERVICE-INBOX', 'name' => 'Shared placeholder', 'status' => 'REGISTERED',
        ]);
        $newCustomer = Customer::create([
            'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
            'customer_code' => 'NEW-INTAKE-CUSTOMER', 'name' => 'New Intake Customer',
            'email' => 'new-intake@example.test', 'phone' => '0500000222', 'status' => 'ACTIVE',
        ]);
        $result = [];
        foreach (['UNRM-926000023' => 'TRIAGE', 'UNUM-926000024' => 'EMERGENCY_DISPATCH',
            'UNRM-926000029' => 'TRIAGE', 'UNUM-926000030' => 'EMERGENCY_DISPATCH'] as $reference => $stage) {
            $requestCustomer = in_array($reference, ['UNRM-926000029', 'UNUM-926000030'], true) ? $newCustomer : $customer;
            $workOrder = WorkOrder::create([
                'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
                'work_order_no' => 'WO-'.$reference, 'asset_id' => $placeholder->id,
                'maintenance_type' => 'CORRECTIVE', 'status' => 'OPEN',
            ]);
            $service = ServiceRequest::create([
                'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
                'customer_id' => $requestCustomer->id, 'asset_id' => $placeholder->id,
                'work_order_id' => $workOrder->id, 'request_no' => 'SR-'.$reference,
                'request_type' => 'MAINTENANCE', 'request_subtype' => $stage === 'TRIAGE' ? 'ROUTINE_MAINTENANCE' : 'URGENT_MAINTENANCE',
                'service_category' => 'Maintenance', 'subject' => 'Equipment maintenance',
                'details' => 'Customer submitted equipment details', 'company_name' => $requestCustomer->name,
                'email' => $requestCustomer->email, 'mobile' => $requestCustomer->phone,
                'status' => 'OPEN', 'workflow_stage' => $stage, 'eligibility' => 'CHARGEABLE',
            ]);
            $public = PublicServiceRequest::create([
                'reference_no' => $reference, 'request_type' => 'MAINTENANCE',
                'request_intent' => 'SERVICE_REQUEST', 'request_subtype' => $service->request_subtype,
                'service_category' => 'Maintenance', 'subject' => 'Equipment maintenance',
                'details' => 'Customer submitted equipment details', 'company_name' => $requestCustomer->name,
                'commercial_registration' => '1010000123', 'email' => $requestCustomer->email,
                'mobile' => $requestCustomer->phone, 'tenant_id' => $tenant->id,
                'organization_id' => $organization->id, 'service_request_id' => $service->id,
                'asset_type' => 'Generator', 'equipment_brand' => 'Example Brand',
                'site_name' => 'Customer Site', 'status' => 'CONVERTED_TO_WORK_ORDER',
                'submitted_at' => now(),
            ]);
            if ($reference === 'UNRM-926000029') {
                $opportunity = CrmOpportunity::create([
                    'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
                    'customer_id' => $requestCustomer->id, 'opportunity_no' => 'OPP-'.$reference,
                    'name' => 'Intake quotation opportunity', 'status' => 'OPEN',
                ]);
                $quotation = CrmQuotation::create([
                    'opportunity_id' => $opportunity->id,
                    'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
                    'customer_id' => $requestCustomer->id, 'quotation_no' => 'QT-'.$reference,
                    'quotation_date' => today(), 'currency' => 'SAR', 'amount' => 0, 'status' => 'DRAFT',
                ]);
                $service->update(['quotation_id' => $quotation->id]);
                $public->update(['status' => 'CONVERTED_TO_QUOTATION']);
            }
            $result[$reference] = [$service, $public, $workOrder];
        }

        return $result;
    }

    public function test_dry_run_preserves_records_and_apply_reconciles_each_request_atomically(): void
    {
        $records = $this->legacyRequests();
        $this->artisan('unifco:reconcile-historical-intake-assets')->assertSuccessful();
        $this->assertSame(1, Asset::count());
        $this->artisan('unifco:reconcile-historical-intake-assets', ['--apply' => true])->assertSuccessful();

        foreach ($records as $reference => [$service, $public, $workOrder]) {
            $asset = Asset::where('asset_code', 'INTAKE-'.$reference)->firstOrFail();
            $this->assertSame((int) $service->customer_id, (int) $asset->customer_id);
            $this->assertSame('DRAFT', $asset->verification_status);
            $this->assertSame('Example Brand', $asset->manufacturer);
            $this->assertSame((int) $asset->id, (int) $service->fresh()->asset_id);
            $this->assertSame((int) $asset->id, (int) $public->fresh()->asset_id);
            $this->assertSame((int) $asset->id, (int) $workOrder->fresh()->asset_id);
        }
        $this->assertSame(5, Asset::count());
        $this->assertDatabaseCount('audit_logs', 4);
        $this->artisan('unifco:reconcile-historical-intake-assets', ['--apply' => true])->assertSuccessful();
        $this->assertSame(5, Asset::count());
    }

    public function test_apply_refuses_a_started_work_order_without_partial_mutation(): void
    {
        $records = $this->legacyRequests();
        $originalAssetId = $records['UNRM-926000023'][0]->asset_id;
        $records['UNRM-926000023'][2]->update(['started_at' => now()]);
        $this->artisan('unifco:reconcile-historical-intake-assets', ['--apply' => true])->assertFailed();

        $this->assertNull($records['UNRM-926000023'][1]->fresh()->asset_id);
        $this->assertSame((int) $originalAssetId, (int) $records['UNRM-926000023'][0]->fresh()->asset_id);
        $this->assertDatabaseMissing('assets', ['asset_code' => 'INTAKE-UNRM-926000023']);
        $this->assertDatabaseHas('assets', ['asset_code' => 'INTAKE-UNUM-926000024']);
    }

    public function test_apply_refuses_foreign_customer_link_for_new_customer_request(): void
    {
        $records = $this->legacyRequests();
        [$service, $public, $workOrder] = $records['UNRM-926000029'];
        $foreignTenant = Tenant::create(['name' => 'Foreign', 'code' => 'FOREIGN-INTAKE', 'status' => 'ACTIVE']);
        $foreignCustomer = Customer::create([
            'tenant_id' => $foreignTenant->id, 'customer_code' => 'FOREIGN-CUSTOMER',
            'name' => 'Foreign Customer', 'status' => 'ACTIVE',
        ]);
        $service->update(['customer_id' => $foreignCustomer->id]);

        $this->artisan('unifco:reconcile-historical-intake-assets', ['--apply' => true])->assertFailed();

        $this->assertSame((int) $service->asset_id, (int) $service->fresh()->asset_id);
        $this->assertSame((int) $workOrder->asset_id, (int) $workOrder->fresh()->asset_id);
        $this->assertNull($public->fresh()->asset_id);
        $this->assertDatabaseMissing('assets', ['asset_code' => 'INTAKE-UNRM-926000029']);
    }
    public function test_quotation_intake_refuses_an_approved_quote_without_mutation(): void
    {
        $records = $this->legacyRequests();
        [$service, $public, $workOrder] = $records['UNRM-926000029'];
        CrmQuotation::findOrFail($service->quotation_id)->update([
            'status' => 'CUSTOMER_APPROVED', 'customer_approved_at' => now(),
        ]);
        $this->artisan('unifco:reconcile-historical-intake-assets', ['--apply' => true])->assertFailed();
        $this->assertNull($public->fresh()->asset_id);
        $this->assertSame((int) $service->asset_id, (int) $service->fresh()->asset_id);
        $this->assertSame((int) $workOrder->asset_id, (int) $workOrder->fresh()->asset_id);
        $this->assertDatabaseMissing('assets', ['asset_code' => 'INTAKE-UNRM-926000029']);
    }

}
