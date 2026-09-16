<?php

namespace Tests\Feature;

use App\Models\{Asset,OperationalDomain,ServiceRequest};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsRoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_inherits_operational_domain_from_asset(): void
    {
        $domain = OperationalDomain::query()->create([
            'code'=>'GENERATORS','name_en'=>'Generators','name_ar'=>'المولدات','is_active'=>true,
        ]);
        $asset = Asset::query()->create([
            'asset_code'=>'GEN-TEST-001','name'=>'Generator Test','operational_domain_id'=>$domain->id,
        ]);

        $request = ServiceRequest::query()->create([
            'request_no'=>'SR-DOMAIN-001','asset_id'=>$asset->id,'subject'=>'Generator request','status'=>'NEW',
        ]);

        $this->assertSame($domain->id, $request->fresh()->operational_domain_id);
    }

    public function test_request_remains_unassigned_when_no_matching_operations_manager_exists(): void
    {
        $domain = OperationalDomain::query()->create([
            'code'=>'BATTERIES','name_en'=>'Batteries','name_ar'=>'البطاريات','is_active'=>true,
        ]);
        $asset = Asset::query()->create([
            'asset_code'=>'BAT-TEST-001','name'=>'Battery Test','operational_domain_id'=>$domain->id,
        ]);

        $request = ServiceRequest::query()->create([
            'request_no'=>'SR-DOMAIN-002','asset_id'=>$asset->id,'subject'=>'Battery request','status'=>'NEW',
        ])->fresh();

        $this->assertNull($request->operations_manager_id);
        $this->assertSame('UNASSIGNED', $request->operations_routing_status);
    }
}
