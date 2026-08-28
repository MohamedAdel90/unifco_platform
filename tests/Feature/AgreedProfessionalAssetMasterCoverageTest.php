<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,CustomerSite,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgreedProfessionalAssetMasterCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_a_template_location_photos_documents_and_asset_360_are_operational(): void
    {
        Storage::fake('local');
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'A-COVER','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'A-COVER-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'CUS-A-COVER','name'=>'Phase A Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'A-COVER-RUH','name'=>'Riyadh Site','city'=>'Riyadh','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Manager','email'=>'a.cover.manager@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'a.cover.engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);

        $this->actingAs($manager)->post('/asset-master/templates',['category'=>'Electrical','asset_type'=>'Generator','name'=>'Generator Standard','specification_schema'=>'{"rated_kva":"number"}'])->assertRedirect();
        $this->assertDatabaseHas('asset_category_templates',['tenant_id'=>$tenant->id,'category'=>'Electrical','asset_type'=>'Generator','name'=>'Generator Standard']);
        $this->actingAs($manager)->post('/asset-master/locations',['customer_id'=>$customer->id,'customer_site_id'=>$site->id,'location_type'=>'ROOM','code'=>'GEN-ROOM','name'=>'Generator Room'])->assertRedirect();
        $this->assertDatabaseHas('asset_locations',['tenant_id'=>$tenant->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,'code'=>'GEN-ROOM']);

        $locationId=(int)\DB::table('asset_locations')->where('code','GEN-ROOM')->value('id');
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,'asset_location_id'=>$locationId,'asset_code'=>'AST-A-COVER','name'=>'Generator A','asset_category'=>'Electrical','asset_type'=>'Generator','manufacturer'=>'OEM','model_no'=>'G-100','serial_no'=>'SER-A-COVER','criticality'=>'HIGH','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','installation_date'=>today()->subYear(),'warranty_start'=>today()->subYear(),'warranty_expiry'=>today()->addYear()->toDateString(),'warranty_provider'=>'OEM','technical_specifications'=>['rated_kva'=>1000],'verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE','operational_status'=>'ACTIVE','qr_token'=>'QR-A-COVER','data_completeness_score'=>100,'status'=>'ACTIVE']);

        $this->actingAs($engineer)->post('/asset-master/'.$asset->id.'/documents',['document_type'=>'PRIMARY_PHOTO','title'=>'Asset photo','file'=>UploadedFile::fake()->image('asset.jpg')])->assertRedirect();
        $this->actingAs($engineer)->post('/asset-master/'.$asset->id.'/documents',['document_type'=>'DATASHEET','title'=>'OEM datasheet','file'=>UploadedFile::fake()->create('datasheet.pdf',20,'application/pdf')])->assertRedirect();
        $this->assertDatabaseHas('asset_documents',['asset_id'=>$asset->id,'document_type'=>'PRIMARY_PHOTO']);
        $this->assertDatabaseHas('asset_documents',['asset_id'=>$asset->id,'document_type'=>'DATASHEET']);

        $this->actingAs($manager)->get('/asset-master/'.$asset->id)->assertOk()->assertSee('Asset 360')->assertSee('SER-A-COVER')->assertSee('Generator Room')->assertSee('OEM')->assertSee('Technical')->assertSee('Documents & Asset Photos',false)->assertSee('QR-A-COVER')->assertSee('Overview')->assertSee('Maintenance')->assertSee('History')->assertSee('Costs');
    }
}
