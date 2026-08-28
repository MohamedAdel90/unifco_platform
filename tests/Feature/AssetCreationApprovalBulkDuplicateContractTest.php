<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,Organization,Tenant,User};
use App\Services\{AssetMasterService,CustomerAssetGovernanceService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetCreationApprovalBulkDuplicateContractTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'SCREEN-CONTRACT','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'SCREEN-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'SCREEN-CUS','name'=>'ABC Company','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'SCREEN-RUH','name'=>'Riyadh Plant','city'=>'Riyadh','status'=>'ACTIVE']);
        $site2=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'SCREEN-JED','name'=>'Jeddah Plant','city'=>'Jeddah','status'=>'ACTIVE']);
        $customerAdmin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>'Customer Admin','email'=>'screen.customer@example.test','password'=>'StrongPassword123','role'=>'CUSTOMER_ADMIN','status'=>'ACTIVE']);
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'screen.engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Manager','email'=>'screen.manager@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        return compact('tenant','org','customer','site','site2','customerAdmin','engineer','manager');
    }

    public function test_profile_can_be_76_percent_with_the_four_literal_missing_items(): void
    {
        $d=$this->fixture();
        $asset=Asset::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'customer_id'=>$d['customer']->id,'customer_site_id'=>$d['site']->id,'asset_code'=>'AST-UNF-000076','name'=>'Transformer','asset_category'=>'Electrical','asset_type'=>'Transformer','manufacturer'=>'OEM','model_no'=>'TR-1','serial_no'=>null,'warranty_expiry'=>null,'maintenance_strategy'=>null,'ownership_type'=>'CUSTOMER_OWNED','physical_location'=>'Room A','installation_date'=>today(),'criticality'=>'HIGH','technical_specifications'=>['capacity'=>'1000 kVA'],'qr_token'=>'QR-76','verification_status'=>'PENDING','lifecycle_status'=>'PENDING_VERIFICATION','status'=>'REGISTERED']);
        $svc=app(AssetMasterService::class); $asset=$svc->refreshCompleteness($asset);
        $this->assertSame(76,$asset->data_completeness_score);
        $this->assertSame(['Serial Number','Warranty Date','Nameplate Photo','PM Strategy'],$svc->profileMissingFields($asset));
        $this->assertSame(['Serial Number','PM Strategy'],$svc->minimumVerificationMissing($asset));
    }

    public function test_initial_customer_registration_needs_only_site_category_and_asset_name_then_waits_for_verification(): void
    {
        $d=$this->fixture(); $gov=app(CustomerAssetGovernanceService::class);
        $id=$gov->submit($d['customerAdmin'],['customer_site_id'=>$d['site']->id,'name'=>'Minimal Customer Asset','asset_category'=>'HVAC','ownership_type'=>'CUSTOMER_OWNED']);
        $this->assertDatabaseHas('customer_asset_submissions',['id'=>$id,'status'=>'PENDING_VERIFICATION','name'=>'Minimal Customer Asset']);
        $asset=$gov->review($d['manager'],$id,true,'Accepted into master for enrichment.');
        $this->assertNotNull($asset); $this->assertSame('PENDING_VERIFICATION',$asset->lifecycle_status); $this->assertSame('PENDING',$asset->verification_status); $this->assertNotSame('ACTIVE',$asset->status);
        $this->assertDatabaseHas('customer_asset_submissions',['id'=>$id,'status'=>'APPROVED','asset_id'=>$asset->id]);
    }

    public function test_maintenance_engineer_creates_and_manager_verify_activate_requires_minimum_verification_data(): void
    {
        $d=$this->fixture(); $svc=app(AssetMasterService::class);
        $asset=$svc->create($d['tenant']->id,$d['org']->id,$d['engineer']->id,['customer_id'=>$d['customer']->id,'customer_site_id'=>$d['site']->id,'name'=>'Pump P-1','asset_category'=>'Mechanical','asset_type'=>'Pump','manufacturer'=>'OEM','model_no'=>'P1','serial_no'=>'P-SN-1','criticality'=>'MEDIUM','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','physical_location'=>'Pump Room','installation_date'=>today(),'technical_specifications'=>['flow'=>'10 m3/h']]);
        $this->assertSame('PENDING_VERIFICATION',$asset->lifecycle_status);
        $asset=$svc->verify($asset,$d['manager']->id,'Verified independently.');
        $this->assertSame('ACTIVE',$asset->lifecycle_status); $this->assertSame('VERIFIED',$asset->verification_status);
    }

    public function test_duplicate_check_uses_customer_site_serial_manufacturer_model_and_customer_asset_code(): void
    {
        $d=$this->fixture(); $svc=app(AssetMasterService::class);
        $existing=Asset::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'customer_id'=>$d['customer']->id,'customer_site_id'=>$d['site']->id,'asset_code'=>'AST-UNF-000901','customer_asset_code'=>'CUS-901','name'=>'Chiller','asset_category'=>'HVAC','asset_type'=>'Chiller','manufacturer'=>'Carrier','model_no'=>'30XA','serial_no'=>'CH-SN-901','status'=>'ACTIVE']);
        $match=$svc->findStrongDuplicate($d['tenant']->id,$d['customer']->id,['customer_site_id'=>$d['site']->id,'serial_no'=>'ch-sn-901','manufacturer'=>'carrier','model_no'=>'30xa']);
        $this->assertNotNull($match); $this->assertSame($existing->id,$match->id);
        $this->assertNull($svc->findStrongDuplicate($d['tenant']->id,$d['customer']->id,['customer_site_id'=>$d['site2']->id,'serial_no'=>'CH-SN-901','manufacturer'=>'Carrier','model_no'=>'30XA']));
        $byCode=$svc->findStrongDuplicate($d['tenant']->id,$d['customer']->id,['customer_site_id'=>$d['site']->id,'customer_asset_code'=>'cus-901']); $this->assertSame($existing->id,$byCode?->id);
    }

    public function test_bulk_import_runs_imported_validation_queue_duplicate_check_and_approval_flow(): void
    {
        $d=$this->fixture(); $csv="name,asset_category,customer_site_id,serial_no,manufacturer,model_no,customer_asset_code\nPump B,Mechanical,{$d['site']->id},PB-001,OEM,P-1,CUS-PB-1\n";
        $file=UploadedFile::fake()->createWithContent('assets.csv',$csv);
        $result=app(CustomerAssetGovernanceService::class)->import($d['customerAdmin'],$file);
        $this->assertCount(1,$result['created']); $this->assertSame(['IMPORTED','VALIDATION_QUEUE','DUPLICATE_CHECK','APPROVED'],$result['workflow']);
        $id=$result['created'][0];
        $events=DB::table('customer_asset_submission_events')->where('customer_asset_submission_id',$id)->orderBy('id')->pluck('event_type')->all();
        $this->assertSame(['IMPORTED','VALIDATION_QUEUE','DUPLICATE_CHECK'],$events);
        app(CustomerAssetGovernanceService::class)->review($d['manager'],$id,true);
        $this->assertDatabaseHas('customer_asset_submission_events',['customer_asset_submission_id'=>$id,'event_type'=>'APPROVED']);
    }

    public function test_lifecycle_contract_contains_all_proposed_states(): void
    {
        $this->assertSame(['DRAFT','PENDING_VERIFICATION','ACTIVE','UNDER_MAINTENANCE','OUT_OF_SERVICE','DECOMMISSIONED','DISPOSED'],AssetMasterService::LIFECYCLE);
    }
}
