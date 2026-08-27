<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,Organization,Tenant,User};
use App\Services\AssetMaintenanceIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetMaintenanceIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'MI','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'MI-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'CUS-MI','name'=>'MI Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'MI-RUH','name'=>'MI Site','city'=>'Riyadh','status'=>'ACTIVE']);
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'mi.engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Manager','email'=>'mi.manager@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $asset=Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,
            'asset_code'=>'AST-MI-001','name'=>'Generator MI-01','asset_category'=>'Electrical','asset_type'=>'Generator','criticality'=>'CRITICAL',
            'ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','installation_date'=>now()->subMonths(24)->toDateString(),
            'useful_life_months'=>120,'verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE','operational_status'=>'ACTIVE','status'=>'ACTIVE','meter_value'=>0,
        ]);
        return compact('tenant','org','customer','site','engineer','manager','asset');
    }

    public function test_meter_reading_updates_asset_and_rejects_rollback(): void
    {
        $d=$this->setupData();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/intelligence/meter',[
            'reading'=>1250.5,'reading_date'=>now()->toDateString(),'notes'=>'Monthly reading',
        ])->assertRedirect();
        $this->assertDatabaseHas('asset_meter_readings',['asset_id'=>$d['asset']->id,'reading'=>'1250.5000']);
        $this->assertEquals(1250.5,(float)$d['asset']->fresh()->meter_value);

        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/intelligence/meter',[
            'reading'=>1200,'reading_date'=>now()->addDay()->toDateString(),
        ])->assertStatus(422);
    }

    public function test_health_snapshot_penalizes_failures_downtime_and_overdue_pm(): void
    {
        $d=$this->setupData();
        DB::table('asset_failures')->insert([
            'tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'asset_id'=>$d['asset']->id,
            'failure_mode'=>'Overheat','failed_at'=>now()->subDays(10),'restored_at'=>now()->subDays(9),'downtime_minutes'=>1440,
            'severity'=>'HIGH','status'=>'CLOSED','created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('maintenance_plans')->insert([
            'tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'asset_id'=>$d['asset']->id,'plan_no'=>'PM-MI-001','name'=>'Monthly PM',
            'frequency_type'=>'MONTH','frequency_value'=>1,'next_due_date'=>now()->subDays(5)->toDateString(),'priority'=>'HIGH','status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now(),
        ]);
        $service=app(AssetMaintenanceIntelligenceService::class);
        $snapshot=$service->snapshot($d['asset']);
        $this->assertSame(1,$snapshot['failureCount']);
        $this->assertSame(1440,$snapshot['downtime']);
        $this->assertSame(1,$snapshot['overdue']);
        $this->assertLessThan(100,$snapshot['score']);
    }

    public function test_manager_recalculation_persists_health_and_engineer_cannot_force_recalculate(): void
    {
        $d=$this->setupData();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/intelligence/recalculate')->assertForbidden();
        $this->actingAs($d['manager'])->post('/asset-master/'.$d['asset']->id.'/intelligence/recalculate')->assertRedirect();
        $asset=$d['asset']->fresh();
        $this->assertNotNull($asset->health_score);
        $this->assertNotNull($asset->health_band);
        $this->assertNotNull($asset->last_health_calculated_at);
    }

    public function test_intelligence_dashboard_is_tenant_scoped_and_shows_reliability_sections(): void
    {
        $d=$this->setupData();
        $this->actingAs($d['manager'])->get('/asset-master/'.$d['asset']->id.'/intelligence')
            ->assertOk()->assertSee('Asset Maintenance Intelligence')->assertSee('Health Score')->assertSee('Reliability')->assertSee('Meter Readings')->assertSee('Maintenance Plans')->assertSee('Failure History');

        $otherTenant=Tenant::create(['name'=>'Other','code'=>'MI-OTHER','status'=>'ACTIVE']);
        $otherOrg=Organization::create(['tenant_id'=>$otherTenant->id,'name'=>'Other HQ','code'=>'MI-OTHER-HQ','status'=>'ACTIVE']);
        $other=User::create(['tenant_id'=>$otherTenant->id,'organization_id'=>$otherOrg->id,'name'=>'Other','email'=>'mi.other@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $this->actingAs($other)->get('/asset-master/'.$d['asset']->id.'/intelligence')->assertNotFound();
    }
}
