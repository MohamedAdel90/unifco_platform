<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,Organization,Tenant,User};
use App\Services\{AgreedAssetIntelligenceService,AssetMaintenanceIntelligenceService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgreedAssetGapClosureABCDTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'GAP-ABCD','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'GAP-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'GAP-CUS','name'=>'Gap Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'GAP-RUH','name'=>'Riyadh Facility','city'=>'Riyadh','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'gap.engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,'asset_code'=>'AST-UNF-000001','name'=>'Distribution Transformer','asset_category'=>'Electrical','asset_type'=>'Transformer','manufacturer'=>'OEM','manufacturer_asset_number'=>'OEM-TR-001','serial_no'=>'SER-GAP-001','room_code'=>'ER-01','physical_location'=>'Main Building / Basement / Electrical Room 01','criticality'=>'CRITICAL','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','installation_date'=>today()->subYears(4),'commission_date'=>today()->subYears(4),'warranty_start'=>today()->subYears(4),'warranty_expiry'=>today()->addYear(),'warranty_provider'=>'OEM','contract_reference'=>'CTR-001','sla_reference'=>'SLA-GOLD','coverage_type'=>'FULL','operating_hours'=>12000,'meter_value'=>12000,'meter_unit'=>'HOURS','design_capacity'=>1500,'current_load'=>920,'failure_impact'=>'HIGH','useful_life_months'=>240,'verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE','operational_status'=>'ACTIVE','qr_token'=>'QR-GAP-001','status'=>'ACTIVE']);
        return compact('tenant','org','customer','site','user','asset');
    }

    public function test_literal_identity_contract_location_and_operating_profile_fields_are_persisted(): void
    {
        $d=$this->fixture(); $a=$d['asset']->fresh();
        $this->assertSame('AST-UNF-000001',$a->asset_code); $this->assertSame('OEM-TR-001',$a->manufacturer_asset_number); $this->assertSame('ER-01',$a->room_code);
        $this->assertSame('CTR-001',$a->contract_reference); $this->assertSame('SLA-GOLD',$a->sla_reference); $this->assertSame('FULL',$a->coverage_type);
        $this->assertSame('HOURS',$a->meter_unit); $this->assertSame('HIGH',$a->failure_impact); $this->assertEquals(1500,(float)$a->design_capacity); $this->assertEquals(920,(float)$a->current_load);
    }

    public function test_transformer_and_chiller_professional_templates_are_available(): void
    {
        $d=$this->fixture(); $svc=app(AgreedAssetIntelligenceService::class); $svc->ensureStandardTemplates($d['tenant']->id,$d['org']->id);
        $transformer=DB::table('asset_category_templates')->where('tenant_id',$d['tenant']->id)->where('asset_type','Transformer')->first();
        $chiller=DB::table('asset_category_templates')->where('tenant_id',$d['tenant']->id)->where('asset_type','Chiller')->first();
        $this->assertNotNull($transformer); $this->assertNotNull($chiller);
        $this->assertStringContainsString('rated_power_kva',$transformer->specification_schema); $this->assertStringContainsString('vector_group',$transformer->specification_schema);
        $this->assertStringContainsString('cooling_capacity',$chiller->specification_schema); $this->assertStringContainsString('number_of_compressors',$chiller->specification_schema);
    }

    public function test_pm_inspection_meter_failure_and_health_score_close_operating_intelligence_gap(): void
    {
        $d=$this->fixture(); $a=$d['asset']; $svc=app(AgreedAssetIntelligenceService::class);
        $svc->createPmPlan($a,$d['user']->id,['name'=>'Monthly Transformer PM','frequency_type'=>'MONTH','frequency_value'=>1,'next_due_date'=>today()->addMonth()->toDateString()]);
        $svc->recordInspection($a->fresh(),$d['user']->id,['inspection_date'=>today()->toDateString(),'next_inspection'=>today()->addMonths(3)->toDateString(),'condition_status'=>'GOOD']);
        app(AssetMaintenanceIntelligenceService::class)->recordMeter($a->fresh(),$d['user']->id,['reading'=>12100,'reading_date'=>today()->toDateString()]);
        $svc->recordFailure($a->fresh(),$d['user']->id,['failure_mode'=>'Thermal alarm','failed_at'=>now()->subHour()->toDateTimeString(),'restored_at'=>now()->toDateTimeString(),'severity'=>'HIGH']);
        $a=$a->fresh();
        $this->assertSame('Monthly Transformer PM',$a->pm_template); $this->assertSame('1 MONTH',$a->pm_frequency); $this->assertNotNull($a->next_pm); $this->assertNotNull($a->last_inspection); $this->assertNotNull($a->next_inspection);
        $this->assertNotNull($a->health_score); $this->assertGreaterThanOrEqual(0,$a->health_score); $this->assertLessThanOrEqual(100,$a->health_score); $this->assertNotNull($a->health_band); $this->assertNotNull($a->replacement_recommendation);
    }
}
