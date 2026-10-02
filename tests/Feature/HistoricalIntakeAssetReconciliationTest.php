<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,Organization,PublicServiceRequest,ServiceRequest,Tenant,WorkOrder};
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
        $result = [];
        foreach (['UNRM-926000023' => 'TRIAGE', 'UNUM-926000024' => 'EMERGENCY_DISPATCH'] as $reference => $stage) {
            $workOrder = WorkOrder::create([
                'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
                'work_order_no' => 'WO-'.$reference, 'asset_id' => $placeholder->id,
                'maintenance_type' => 'CORRECTIVE', 'status' => 'OPEN',
            ]);
            $service = ServiceRequest::create([
                'tenant_id' => $tenant->id, 'organization_id' => $organization->id,
                'customer_id' => $customer->id, 'asset_id' => $placeholder->id,
                'work_order_id' => $workOrder->id, 'request_no' => 'SR-'.$reference,
                'request_type' => 'MAINTENANCE', 'request_subtype' => $stage === 'TRIAGE' ? 'ROUTINE_MAINTENANCE' : 'URGENT_MAINTENANCE',
                'service_category' => 'Maintenance', 'subject' => 'Equipment maintenance',
                'details' => 'Customer submitted equipment details', 'company_name' => $customer->name,
                'email' => $customer->email, 'mobile' => $customer->phone,
                'status' => 'OPEN', 'workflow_stage' => $stage, 'eligibility' => 'CHARGEABLE',
            ]);
            $public = PublicServiceRequest::create([
                'reference_no' => $reference, 'request_type' => 'MAINTENANCE',
                'request_intent' => 'SERVICE_REQUEST', 'request_subtype' => $service->request_subtype,
                'service_category' => 'Maintenance', 'subject' => 'Equipment maintenance',
                'details' => 'Customer submitted equipment details', 'company_name' => $customer->name,
                'commercial_registration' => '1010000123', 'email' => $customer->email,
                'mobile' => $customer->phone, 'tenant_id' => $tenant->id,
                'organization_id' => $organization->id, 'service_request_id' => $service->id,
                'asset_type' => 'Generator', 'equipment_brand' => 'Example Brand',
                'site_name' => 'Customer Site', 'status' => 'CONVERTED_TO_WORK_ORDER',
                'submitted_at' => now(),
            ]);
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
        $this->assertSame(3, Asset::count());
        $this->assertDatabaseCount('audit_logs', 2);
        $this->artisan('unifco:reconcile-historical-intake-assets', ['--apply' => true])->assertSuccessful();
        $this->assertSame(3, Asset::count());
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
}
