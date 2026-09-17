<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,OperationalDomain,ServiceRequest,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsRoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Operations Routing',
            'code' => 'OPS-ROUTING',
            'status' => 'ACTIVE',
        ]);
    }

    private function customer(Tenant $tenant): Customer
    {
        return Customer::query()->create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'OPS-ROUTING-CUSTOMER',
            'name' => 'Operations Routing Customer',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_request_inherits_operational_domain_from_asset(): void
    {
        $tenant = $this->tenant();
        $customer = $this->customer($tenant);
        $domain = OperationalDomain::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'GENERATORS',
            'name_en' => 'Generators',
            'name_ar' => 'المولدات',
            'is_active' => true,
        ]);
        $asset = Asset::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'asset_code' => 'GEN-TEST-001',
            'name' => 'Generator Test',
            'criticality' => 'HIGH',
            'status' => 'ACTIVE',
            'operational_domain_id' => $domain->id,
        ]);

        $request = ServiceRequest::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'request_no' => 'SR-DOMAIN-001',
            'asset_id' => $asset->id,
            'request_type' => 'CORRECTIVE',
            'priority' => 'P2',
            'subject' => 'Generator request',
            'status' => 'NEW',
        ]);

        $this->assertSame((int) $domain->id, (int) $request->fresh()->operational_domain_id);
    }

    public function test_request_remains_unassigned_when_no_matching_operations_manager_exists(): void
    {
        $tenant = $this->tenant();
        $customer = $this->customer($tenant);
        $domain = OperationalDomain::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'BATTERIES',
            'name_en' => 'Batteries',
            'name_ar' => 'البطاريات',
            'is_active' => true,
        ]);
        $asset = Asset::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'asset_code' => 'BAT-TEST-001',
            'name' => 'Battery Test',
            'criticality' => 'HIGH',
            'status' => 'ACTIVE',
            'operational_domain_id' => $domain->id,
        ]);

        $request = ServiceRequest::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'request_no' => 'SR-DOMAIN-002',
            'asset_id' => $asset->id,
            'request_type' => 'CORRECTIVE',
            'priority' => 'P2',
            'subject' => 'Battery request',
            'status' => 'NEW',
        ])->fresh();

        $this->assertSame((int) $domain->id, (int) $request->operational_domain_id);
        $this->assertNull($request->operations_manager_id);
        $this->assertSame('UNASSIGNED', $request->operations_routing_status);
    }
}
