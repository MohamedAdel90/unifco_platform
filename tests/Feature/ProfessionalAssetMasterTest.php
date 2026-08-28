<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalAssetMasterTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(string $role='ADMIN'): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'ASSET-A','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'ASSET-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'CUS-ASSET','name'=>'Asset Test Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'AST-RUH','name'=>'Riyadh Facility','city'=>'Riyadh','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>$role.' Asset User','email'=>strtolower($role).'.asset@example.test','password'=>'StrongPassword123','role'=>$role,'status'=>'ACTIVE']);
        return compact('tenant','org','customer','site','user');
    }

    private function payload(Customer $customer,CustomerSite $site,array $extra=[]): array
    {
        return array_merge(['customer_id'=>$customer->id,'customer_site_id'=>$site->id,'name'=>'Transformer TR-01','customer_asset_code'=>'TR-01','asset_category'=>'Electrical','asset_type'=>'Distribution Transformer','manufacturer'=>'ABB','model_no'=>'TX1000','serial_no'=>'SN-TR-0001','criticality'=>'CRITICAL','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','physical_location'=>'Main Building · Electrical Room 01','installation_date'=>'2025-01-15','commission_date'=>'2025-01-20','warranty_start'=>'2025-01-20','warranty_expiry'=>'2027-01-19','warranty_provider'=>'ABB Service','technical_specifications'=>'{"rated_power_kva":1000,"primary_voltage":"13.8kV","secondary_voltage":"400V"}'],$extra);
    }

    public function test_engineer_legacy_eam_create_route_redirects_to_professional_asset_master(): void
    {
        $d=$this->setupData('MAINTENANCE_ENGINEER');
        $this->actingAs($d['user'])->get('/eam/assets/create')->assertRedirect(route('asset-master.index'));
        $this->actingAs($d['user'])->get('/eam/assets')->assertRedirect(route('asset-master.index'));
        $this->actingAs($d['user'])->get('/asset-master')->assertOk()->assertSee('Register Customer Asset')->assertSee('Create Pending Verification Asset');
    }

    public function test_engineer_can_register_professional_asset_pending_verification(): void
    {
        $d=$this->setupData('MAINTENANCE_ENGINEER');
        $this->actingAs($d['user'])->post('/asset-master',$this->payload($d['customer'],$d['site']))->assertRedirect();
        $asset=Asset::where('serial_no','SN-TR-0001')->firstOrFail();
        $this->assertStringStartsWith('AST-UNF-',$asset->asset_code);
        $this->assertSame('PENDING_VERIFICATION',$asset->lifecycle_status);
        $this->assertSame('PENDING',$asset->verification_status);
        $this->assertGreaterThanOrEqual(70,$asset->data_completeness_score);
        $this->assertSame(1000,$asset->technical_specifications['rated_power_kva']);
        $this->assertSame('2027-01-19',$asset->warranty_expiry?->toDateString());
        $this->assertDatabaseHas('assets',['id'=>$asset->id,'warranty_expiry'=>'2027-01-19']);
        $this->assertNotEmpty($asset->qr_token);
    }

    public function test_duplicate_serial_for_same_customer_and_site_is_blocked(): void
    {
        $d=$this->setupData();
        $this->actingAs($d['user'])->post('/asset-master',$this->payload($d['customer'],$d['site']))->assertRedirect();
        $this->actingAs($d['user'])->post('/asset-master',$this->payload($d['customer'],$d['site'],['name'=>'Transformer Duplicate','customer_asset_code'=>'TR-02']))->assertStatus(422);
        $this->assertSame(1,Asset::where('tenant_id',$d['tenant']->id)->where('serial_no','SN-TR-0001')->count());
    }

    public function test_manager_can_independently_verify_complete_asset_and_activate_it(): void
    {
        $d=$this->setupData('MAINTENANCE_ENGINEER');
        $manager=User::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'name'=>'Manager','email'=>'manager.verify.asset@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $this->actingAs($d['user'])->post('/asset-master',$this->payload($d['customer'],$d['site']))->assertRedirect();
        $asset=Asset::where('serial_no','SN-TR-0001')->firstOrFail();
        $this->actingAs($manager)->post('/asset-master/'.$asset->id.'/verify',['notes'=>'Nameplate, site and technical data checked.'])->assertRedirect();
        $asset->refresh();
        $this->assertSame('VERIFIED',$asset->verification_status);
        $this->assertSame('ACTIVE',$asset->lifecycle_status);
        $this->assertSame('ACTIVE',$asset->status);
        $this->assertSame($manager->id,(int)$asset->verified_by);
    }

    public function test_manager_cannot_create_asset_because_creation_and_approval_are_separated(): void
    {
        $d=$this->setupData('MAINTENANCE_MANAGER');
        $this->actingAs($d['user'])->post('/asset-master',$this->payload($d['customer'],$d['site']))->assertForbidden();
        $this->assertDatabaseMissing('assets',['serial_no'=>'SN-TR-0001']);
    }

    public function test_engineer_cannot_verify_asset(): void
    {
        $d=$this->setupData('MAINTENANCE_ENGINEER');
        $this->actingAs($d['user'])->post('/asset-master',$this->payload($d['customer'],$d['site']))->assertRedirect();
        $asset=Asset::where('serial_no','SN-TR-0001')->firstOrFail();
        $this->actingAs($d['user'])->post('/asset-master/'.$asset->id.'/verify')->assertForbidden();
    }

    public function test_asset_360_shows_professional_identity_and_tabbed_acceptance_sections(): void
    {
        $d=$this->setupData();
        $this->actingAs($d['user'])->post('/asset-master',$this->payload($d['customer'],$d['site']))->assertRedirect();
        $asset=Asset::where('serial_no','SN-TR-0001')->firstOrFail();
        $this->actingAs($d['user'])->get('/asset-master/'.$asset->id)->assertOk()->assertSee('Asset 360')->assertSee('Transformer TR-01')->assertSee('SN-TR-0001')->assertSee('2027-01-19')->assertSee('Technical')->assertSee('Full Component Traceability')->assertSee('Documents & Asset Photos',false)->assertSee('QR Code')->assertSee('Overview')->assertSee('Maintenance')->assertSee('History')->assertSee('Costs');
    }
}
