<?php

namespace Tests\Feature;

use App\Models\{Asset,AssetLifecycleEvent,AssetPartInstallation,Customer,CustomerSite,Item,Organization,Tenant,User,WorkOrder};
use App\Services\{AssetAcceptanceContractService,AssetMasterService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB,Storage};
use LogicException;
use Tests\TestCase;

class AssetAcceptanceContractFinalTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'FINAL-A-D','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'FINAL-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'FINAL-CUS','name'=>'ABC Company','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'FINAL-RUH','name'=>'Riyadh Plant','city'=>'Riyadh','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Manager','email'=>'final.manager@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'final.engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        $asset=Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,'asset_code'=>'AST-UNF-000145','name'=>'Transformer TR-01','asset_category'=>'Electrical','asset_type'=>'Transformer',
            'manufacturer'=>'OEM','model_no'=>'TR-X','serial_no'=>'SER-FINAL-001','manufacturer_asset_number'=>'OEM-145','criticality'=>'HIGH','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','physical_location'=>'Electrical Room A','room_code'=>'ER-A',
            'installation_date'=>today()->subYears(8),'manufacture_date'=>today()->subYears(9),'commission_date'=>today()->subYears(8),'useful_life_months'=>120,'expected_replacement_date'=>today()->addMonths(10),'replacement_target_date'=>today()->addMonths(9),'replacement_value'=>150000,'replacement_cost_estimate'=>165000,
            'purchase_date'=>today()->subYears(9),'supplier_name'=>'Supplier Co','po_number'=>'PO-145','purchase_value'=>120000,'warranty_provider'=>'OEM','warranty_start'=>today()->subYears(2),'warranty_expiry'=>today()->addDays(20),'warranty_terms'=>'Parts and labor coverage',
            'operating_hours'=>22000,'meter_value'=>22000,'meter_unit'=>'HOURS','design_capacity'=>1000,'current_load'=>950,'failure_impact'=>'HIGH','verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE','operational_status'=>'ACTIVE','qr_token'=>'QR-FINAL-145','data_completeness_score'=>100,'status'=>'ACTIVE',
        ]);
        return compact('tenant','org','customer','site','manager','engineer','asset');
    }

    public function test_immutable_asset_identity_and_timeline_events_match_acceptance_contract(): void
    {
        $d=$this->fixture();
        $asset=app(AssetMasterService::class)->create($d['tenant']->id,$d['org']->id,$d['engineer']->id,[
            'customer_id'=>$d['customer']->id,'customer_site_id'=>$d['site']->id,'name'=>'New Chiller','asset_category'=>'HVAC','asset_type'=>'Chiller','manufacturer'=>'OEM','model_no'=>'CH-1','serial_no'=>'NEW-CH-001','criticality'=>'HIGH','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','physical_location'=>'Plant Room','installation_date'=>today()->toDateString(),'technical_specifications'=>['cooling_capacity'=>500]
        ]);
        $this->assertSame('AST-UNF-'.str_pad((string)$asset->id,6,'0',STR_PAD_LEFT),$asset->asset_code);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$asset->id,'event_type'=>'ASSET_CREATED']);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$asset->id,'event_type'=>'QR_GENERATED']);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$asset->id,'event_type'=>'ASSET_INSTALLED']);
        $event=AssetLifecycleEvent::where('asset_id',$asset->id)->firstOrFail();
        $this->expectException(LogicException::class); $event->delete();
    }

    public function test_health_score_uses_every_agreed_input_and_criticality_is_impact_times_probability(): void
    {
        $d=$this->fixture(); $a=$d['asset'];
        $a->update(['impact_safety'=>5,'impact_operation'=>5,'impact_financial'=>4,'impact_customer'=>4,'impact_environmental'=>3,'probability_failure'=>4,'probability_condition'=>4,'probability_age'=>4]);
        DB::table('maintenance_plans')->insert(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'asset_id'=>$a->id,'plan_no'=>'PM-FINAL','name'=>'Monthly PM','frequency_type'=>'MONTH','frequency_value'=>1,'next_due_date'=>today()->subDays(10),'priority'=>'HIGH','status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now()]);
        DB::table('asset_inspection_records')->insert(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'asset_id'=>$a->id,'inspection_date'=>today(),'inspection_type'=>'CONDITION','condition_status'=>'POOR','findings'=>'High vibration','inspected_by'=>$d['engineer']->id,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('asset_failures')->insert(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'asset_id'=>$a->id,'failure_mode'=>'Overheat','failed_at'=>now()->subHours(6),'restored_at'=>now(),'downtime_minutes'=>360,'severity'=>'HIGH','status'=>'RESTORED','reported_by'=>$d['engineer']->id,'created_at'=>now(),'updated_at'=>now()]);
        $wo=WorkOrder::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'work_order_no'=>'WO-FINAL-1','asset_id'=>$a->id,'maintenance_type'=>'CORRECTIVE','priority'=>'HIGH','status'=>'OPEN','total_cost'=>500]);
        $item=Item::create(['tenant_id'=>$d['tenant']->id,'item_code'=>'BRG-6205','name'=>'Bearing','uom'=>'EA','status'=>'ACTIVE']);
        AssetPartInstallation::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'work_order_id'=>$wo->id,'asset_id'=>$a->id,'item_id'=>$item->id,'installed_part_number'=>'BRG-6205','installed_serial_number'=>'BRG-SN-1','installed_manufacturer'=>'Bearing Co','warehouse_code'=>'MAIN','quantity'=>1,'unit_cost'=>100,'total_cost'=>100,'installed_by'=>$d['engineer']->id,'installed_at'=>now(),'warranty_end'=>today()->addYear(),'component_status'=>'INSTALLED']);

        $svc=app(AssetAcceptanceContractService::class); $a=$svc->recalculateCriticality($a->fresh()); $a=$svc->recalculateHealth($a->fresh()); $s=$svc->healthSnapshot($a);
        $this->assertSame('A',$a->criticality_class); $this->assertSame('CRITICAL',$a->criticality); $this->assertGreaterThan(0,(float)$a->criticality_matrix_score);
        $this->assertGreaterThan(0,$s['ageMonths']); $this->assertGreaterThan(0,$s['failureCount']); $this->assertGreaterThan(0,$s['workOrderFrequency']); $this->assertGreaterThan(0,$s['downtime']); $this->assertGreaterThan(0,$s['inspectionPenalty']); $this->assertLessThan(100,$s['maintenanceCompliance']); $this->assertGreaterThan(0,$s['partCount']); $this->assertGreaterThan(0,$s['conditionPenalty']);
        $this->assertGreaterThanOrEqual(0,$a->health_score); $this->assertLessThanOrEqual(100,$a->health_score); $this->assertContains($a->health_band,['EXCELLENT','GOOD','ATTENTION','POOR','CRITICAL']);
        $this->assertStringContainsString('WO frequency',$a->replacement_reason); $this->assertStringContainsString('maintenance compliance',$a->replacement_reason); $this->assertStringContainsString('parts',$a->replacement_reason);
    }

    public function test_purchase_warranty_lifecycle_alerts_and_full_component_traceability_are_present(): void
    {
        $d=$this->fixture(); $a=$d['asset']; $alerts=app(AssetAcceptanceContractService::class)->alerts($a);
        $this->assertTrue($alerts['warranty_expires_in_30_days']); $this->assertTrue($alerts['approaching_end_of_useful_life']);
        $this->assertSame('PO-145',$a->po_number); $this->assertSame('Parts and labor coverage',$a->warranty_terms); $this->assertNotNull($a->replacement_target_date); $this->assertEquals(165000,(float)$a->replacement_cost_estimate);

        $wo=WorkOrder::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'work_order_no'=>'WO-FINAL-COMPONENT','asset_id'=>$a->id,'maintenance_type'=>'CORRECTIVE','priority'=>'HIGH','status'=>'COMPLETED','total_cost'=>250]);
        $item=Item::create(['tenant_id'=>$d['tenant']->id,'item_code'=>'FAN-01','name'=>'Cooling Fan','uom'=>'EA','status'=>'ACTIVE']);
        $part=AssetPartInstallation::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'work_order_id'=>$wo->id,'asset_id'=>$a->id,'item_id'=>$item->id,'installed_part_number'=>'FAN-01','installed_serial_number'=>'FAN-SN-9','installed_manufacturer'=>'Fan Co','warehouse_code'=>'MAIN','quantity'=>1,'unit_cost'=>250,'total_cost'=>250,'installed_by'=>$d['engineer']->id,'installed_at'=>now()->subMonth(),'warranty_start'=>today()->subMonth(),'warranty_end'=>today()->addMonths(11),'component_status'=>'REPLACED','removed_at'=>now(),'removed_serial'=>'OLD-FAN','removed_disposition'=>'SCRAPPED']);
        $this->assertSame('FAN-SN-9',$part->installed_serial_number); $this->assertSame('REPLACED',$part->component_status); $this->assertNotNull($part->removed_at); $this->assertSame($wo->id,$part->work_order_id);
    }

    public function test_document_center_photo_gallery_and_metadata_cover_literal_taxonomy(): void
    {
        Storage::fake('local'); $d=$this->fixture(); $a=$d['asset'];
        foreach(['PRIMARY_PHOTO','NAMEPLATE_PHOTO','FRONT_PHOTO','REAR_PHOTO','ELECTRICAL_PANEL_PHOTO','INSTALLATION_ENVIRONMENT_PHOTO','DAMAGE_PHOTO','BEFORE_MAINTENANCE_PHOTO','AFTER_MAINTENANCE_PHOTO','DATASHEET','USER_MANUAL','MAINTENANCE_MANUAL','COMMISSIONING_REPORT','WARRANTY_CERTIFICATE','PURCHASE_DOCUMENT','INSPECTION_CERTIFICATE','CALIBRATION_CERTIFICATE','DRAWING','TEST_REPORT'] as $type){
            $this->actingAs($d['engineer'])->post('/asset-master/'.$a->id.'/acceptance-documents',['document_type'=>$type,'title'=>$type,'version'=>'1.0','issued_at'=>today()->toDateString(),'expires_at'=>today()->addYear()->toDateString(),'file'=>UploadedFile::fake()->create(strtolower($type).'.pdf',5,'application/pdf')])->assertRedirect();
        }
        $this->assertDatabaseCount('asset_documents',19);
        $this->assertDatabaseHas('asset_documents',['asset_id'=>$a->id,'document_type'=>'PRIMARY_PHOTO','version'=>'1.0','uploaded_by'=>$d['engineer']->id]);
        $this->assertDatabaseHas('asset_documents',['asset_id'=>$a->id,'document_type'=>'PURCHASE_DOCUMENT']);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$a->id,'event_type'=>'DOCUMENT_UPLOADED']);
    }

    public function test_asset_360_is_tabbed_and_exposes_agreed_overview_cards_and_sections(): void
    {
        $d=$this->fixture(); $a=$d['asset'];
        $this->actingAs($d['manager'])->get('/asset-master/'.$a->id)->assertOk()
            ->assertSee('Asset Photo (Primary)')->assertSee('Asset QR Code')->assertSee('Open / Download QR')
            ->assertSee('Overview')->assertSee('Technical')->assertSee('Maintenance')->assertSee('Work Orders')->assertSee('Components')->assertSee('Parts')->assertSee('Documents')->assertSee('History')->assertSee('Costs & Lifecycle',false)->assertSee('Governance')
            ->assertSee('Next PM')->assertSee('Open Work Orders')->assertSee('Lifetime Cost')->assertSee('Downtime YTD')->assertSee('Installed Parts')->assertSee('Last Inspection')
            ->assertSee('Data Quality & Evidence',false)->assertSee('Maintenance & Risk Intelligence',false)->assertSee('Governance & Independent Verification',false);
    }
}
