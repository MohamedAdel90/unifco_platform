<?php

namespace Tests\Feature;

use App\Models\{Asset,AssetPartInstallation,Customer,CustomerSite,Item,Organization,Tenant,User,WorkOrder};
use App\Services\AgreedAssetIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class AgreedAssetPhasesABCDTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'AGREED-ABCD','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'AGREED-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'CUS-ABCD','name'=>'Governance Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'ABCD-RUH','name'=>'Riyadh Site','city'=>'Riyadh','status'=>'ACTIVE']);
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'abcd.engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Manager','email'=>'abcd.manager@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $checker=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Independent Checker','email'=>'abcd.checker@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $customerUser=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>'Customer User','email'=>'abcd.customer@example.test','password'=>'StrongPassword123','role'=>'CUSTOMER','customer_portal_role'=>'ADMIN','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,'asset_code'=>'AST-ABCD-001','name'=>'Generator 1','asset_category'=>'Electrical','asset_type'=>'Generator','manufacturer'=>'OEM','model_no'=>'G100','serial_no'=>'SN-ABCD-001','criticality'=>'CRITICAL','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','physical_location'=>'Plant Room','installation_date'=>today()->subYears(2),'verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE','operational_status'=>'ACTIVE','status'=>'ACTIVE','useful_life_months'=>120,'replacement_value'=>100000,'net_book_value'=>70000]);
        return compact('tenant','org','customer','site','engineer','manager','checker','customerUser','asset');
    }

    public function test_phase_b_pm_meter_inspection_failure_health_downtime_and_reliability_are_operational(): void
    {
        $d=$this->data(); $a=$d['asset'];
        $this->actingAs($d['engineer'])->post("/asset-master/{$a->id}/intelligence/pm",['name'=>'Monthly PM','frequency_type'=>'MONTH','frequency_value'=>1,'next_due_date'=>today()->addMonth()->toDateString()])->assertRedirect();
        $this->actingAs($d['engineer'])->post("/asset-master/{$a->id}/intelligence/meter",['reading'=>250,'reading_date'=>today()->toDateString()])->assertRedirect();
        $this->actingAs($d['engineer'])->post("/asset-master/{$a->id}/intelligence/inspection",['inspection_date'=>today()->toDateString(),'inspection_type'=>'CONDITION','condition_status'=>'FAIR','findings'=>'Minor vibration'])->assertRedirect();
        $this->actingAs($d['engineer'])->post("/asset-master/{$a->id}/intelligence/failure",['failure_mode'=>'Overheat','failed_at'=>now()->subHours(3)->toDateTimeString(),'restored_at'=>now()->toDateTimeString(),'severity'=>'HIGH'])->assertRedirect();
        $snapshot=app(AgreedAssetIntelligenceService::class)->phaseBSnapshot($a->fresh());
        $this->assertSame(1,$snapshot['pm_plans']); $this->assertSame(1,$snapshot['meter_readings']); $this->assertSame(1,$snapshot['inspections']);
        $this->assertSame(1,$snapshot['failureCount']); $this->assertGreaterThan(0,$snapshot['downtime']); $this->assertNotNull($snapshot['mttr']); $this->assertIsInt($snapshot['score']);
        $this->assertArrayHasKey('mtbf',$snapshot); $this->assertArrayHasKey('band',$snapshot);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$a->id,'event_type'=>'ASSET_FAILURE_RECORDED']);
    }

    public function test_phase_c_hierarchy_components_spares_and_cost_replacement_intelligence_are_calculated(): void
    {
        $d=$this->data(); $a=$d['asset'];
        Asset::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'customer_id'=>$d['customer']->id,'customer_site_id'=>$d['site']->id,'parent_asset_id'=>$a->id,'asset_code'=>'AST-ABCD-CHILD','name'=>'Starter Motor','asset_category'=>'Component','asset_type'=>'Motor','verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE','status'=>'ACTIVE']);
        $wo=WorkOrder::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'work_order_no'=>'WO-ABCD-001','asset_id'=>$a->id,'maintenance_type'=>'CORRECTIVE','priority'=>'HIGH','status'=>'COMPLETED','labor_hours'=>4,'labor_cost'=>800,'material_cost'=>1200,'external_cost'=>200,'total_cost'=>2200,'completed_at'=>now()]);
        $item=Item::create(['tenant_id'=>$d['tenant']->id,'item_code'=>'PART-ABCD','name'=>'Bearing Kit','uom'=>'EA','status'=>'ACTIVE']);
        AssetPartInstallation::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'work_order_id'=>$wo->id,'asset_id'=>$a->id,'item_id'=>$item->id,'warehouse_code'=>'MAIN','quantity'=>1,'unit_cost'=>1200,'total_cost'=>1200,'installed_by'=>$d['engineer']->id,'installed_at'=>now()]);
        $a->update(['replacement_recommendation'=>'MONITOR','replacement_reason'=>'Health and age assessment']);
        $s=app(AgreedAssetIntelligenceService::class)->phaseCSnapshot($a->fresh());
        $this->assertSame(1,$s['child_assets']); $this->assertSame(1,$s['installed_components']); $this->assertSame(1,$s['spare_parts_history']);
        $this->assertSame(800.0,$s['labor_cost']); $this->assertSame(1200.0,$s['material_cost']); $this->assertSame(2200.0,$s['lifetime_maintenance_cost']);
        $this->assertSame('MONITOR',$s['replacement_recommendation']); $this->assertSame(100000.0,$s['replacement_value']);
    }

    public function test_phase_d_customer_adds_asset_then_unifco_approves_then_independently_verifies_and_audits(): void
    {
        $d=$this->data();
        $payload=['customer_site_id'=>$d['site']->id,'name'=>'Customer UPS','serial_no'=>'CUST-UPS-001','asset_category'=>'Electrical','asset_type'=>'UPS','manufacturer'=>'OEM','model_no'=>'U100','criticality'=>'HIGH','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','installation_date'=>today()->subYear()->toDateString(),'physical_location'=>'UPS Room','technical_specifications'=>'{"capacity":"100kVA"}'];
        $this->actingAs($d['customerUser'])->post('/customer-assets/submissions',$payload)->assertRedirect();
        $submission=DB::table('customer_asset_submissions')->where('serial_no','CUST-UPS-001')->first(); $this->assertNotNull($submission); $this->assertSame('PENDING_VERIFICATION',$submission->status);

        $bad=$payload; $bad['serial_no']='CUST-UPS-002'; $bad['ownership_type']='UNIFCO_MANAGED';
        $this->actingAs($d['customerUser'])->post('/customer-assets/submissions',$bad)->assertRedirect()->assertSessionHasErrors('ownership_type');

        $other=Customer::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'customer_code'=>'CUS-OTHER','name'=>'Other Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $otherSite=CustomerSite::create(['customer_id'=>$other->id,'site_code'=>'OTHER-RUH','name'=>'Other Site','city'=>'Riyadh','status'=>'ACTIVE']);
        $wrongSite=$payload; $wrongSite['serial_no']='CUST-UPS-003'; $wrongSite['customer_site_id']=$otherSite->id;
        $this->actingAs($d['customerUser'])->post('/customer-assets/submissions',$wrongSite)->assertStatus(422);

        $this->actingAs($d['customerUser'])->post("/customer-assets/submissions/{$submission->id}/review",['decision'=>'APPROVE'])->assertForbidden();
        $this->actingAs($d['manager'])->post("/customer-assets/submissions/{$submission->id}/review",['decision'=>'APPROVE','notes'=>'Accepted into Asset Master.'])->assertRedirect();
        $approved=DB::table('customer_asset_submissions')->where('id',$submission->id)->first(); $this->assertSame('APPROVED',$approved->status); $this->assertNotNull($approved->asset_id);
        $this->assertDatabaseHas('assets',['id'=>$approved->asset_id,'serial_no'=>'CUST-UPS-001','ownership_type'=>'CUSTOMER_OWNED','verification_status'=>'PENDING','lifecycle_status'=>'PENDING_VERIFICATION']);

        $this->actingAs($d['checker'])->post("/asset-master/{$approved->asset_id}/verify",['notes'=>'Independent Verify & Activate.'])->assertRedirect();
        $this->assertDatabaseHas('assets',['id'=>$approved->asset_id,'verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE']);
        $this->assertDatabaseHas('customer_asset_submission_events',['customer_asset_submission_id'=>$submission->id,'event_type'=>'SUBMITTED']);
        $this->assertDatabaseHas('customer_asset_submission_events',['customer_asset_submission_id'=>$submission->id,'event_type'=>'APPROVED']);
        $this->actingAs($d['customerUser'])->get("/customer-assets/submissions/{$submission->id}/audit")->assertOk()->assertJsonCount(2);
    }

    public function test_phase_d_bulk_excel_xlsx_import_creates_pending_verification_submissions(): void
    {
        $d=$this->data(); $path=tempnam(sys_get_temp_dir(),'abcd').'.xlsx'; $this->makeXlsx($path,[$d['site']->id]);
        $file=new UploadedFile($path,'assets.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',null,true);
        $this->actingAs($d['customerUser'])->post('/customer-assets/import',['file'=>$file])->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseHas('customer_asset_submissions',['serial_no'=>'XLSX-001','source'=>'EXCEL','status'=>'PENDING_VERIFICATION','ownership_type'=>'CUSTOMER_OWNED']);
        $submission=DB::table('customer_asset_submissions')->where('serial_no','XLSX-001')->first();
        $this->assertSame(['IMPORTED','VALIDATION_QUEUE','DUPLICATE_CHECK'],DB::table('customer_asset_submission_events')->where('customer_asset_submission_id',$submission->id)->orderBy('id')->pluck('event_type')->all());
        @unlink($path);
    }

    private function makeXlsx(string $path,array $siteIds): void
    {
        $headers=['name','asset_category','customer_site_id','asset_type','manufacturer','model_no','serial_no','physical_location','installation_date','criticality'];
        $values=['Excel Generator','Electrical',(string)$siteIds[0],'Generator','OEM','GX','XLSX-001','Generator Room',today()->subYear()->toDateString(),'HIGH'];
        $strings=array_merge($headers,$values); $shared='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($strings).'" uniqueCount="'.count($strings).'">'; foreach($strings as $s) $shared.='<si><t>'.htmlspecialchars($s,ENT_XML1).'</t></si>'; $shared.='</sst>';
        $cols=['A','B','C','D','E','F','G','H','I','J']; $sheet='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1">'; foreach($headers as $i=>$v) $sheet.='<c r="'.$cols[$i].'1" t="s"><v>'.$i.'</v></c>'; $sheet.='</row><row r="2">'; foreach($values as $i=>$v) $sheet.='<c r="'.$cols[$i].'2" t="s"><v>'.(count($headers)+$i).'</v></c>'; $sheet.='</row></sheetData></worksheet>';
        $zip=new ZipArchive(); $zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE); $zip->addFromString('xl/sharedStrings.xml',$shared); $zip->addFromString('xl/worksheets/sheet1.xml',$sheet); $zip->close();
    }
}
