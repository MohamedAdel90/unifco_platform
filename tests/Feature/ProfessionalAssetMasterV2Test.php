<?php

namespace Tests\Feature;

use App\Models\{Asset,AssetCategoryTemplate,Customer,CustomerSite,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalAssetMasterV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_engineer_sees_guided_asset_registration_hierarchy_dynamic_specs_and_registry_filters(): void
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'AM-V2','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'AM-V2-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'CUS-AMV2','name'=>'Asset V2 Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'AMV2-RUH','name'=>'Riyadh Plant','city'=>'Riyadh','status'=>'ACTIVE']);
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'asset.v2@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);

        AssetCategoryTemplate::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'category'=>'Electrical','asset_type'=>'Transformer','name'=>'Transformer Standard','code'=>'TR-STD','system_group'=>'ELECTRICAL','active'=>true,'status'=>'ACTIVE',
            'specification_schema'=>['rated_power_kva'=>['label'=>'Rated Power kVA','type'=>'number'],'primary_voltage'=>['label'=>'Primary Voltage','type'=>'text']],
        ]);

        Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,
            'asset_code'=>'AST-PARENT-001','name'=>'Main Transformer Bank','asset_category'=>'Electrical','asset_type'=>'Transformer',
            'criticality'=>'HIGH','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','verification_status'=>'VERIFIED',
            'lifecycle_status'=>'ACTIVE','operational_status'=>'ACTIVE','status'=>'ACTIVE','qr_token'=>'AMV2-PARENT-QR','data_completeness_score'=>70,
        ]);

        $response=$this->actingAs($engineer)->get('/asset-master');

        $response->assertOk()
            ->assertSee('Step 1 · Identification & Classification')
            ->assertSee('Step 2 · Location & Asset Hierarchy')
            ->assertSee('Step 3 · Technical Profile')
            ->assertSee('Step 4 · Lifecycle, Ownership & Warranty')
            ->assertSee('Step 5 · Submit for Independent Verification')
            ->assertSee('Parent Asset')
            ->assertSee('AST-PARENT-001')
            ->assertSee('Transformer Standard')
            ->assertSee('No JSON entry is required.')
            ->assertSee('Under Verification')
            ->assertSee('Out of Service')
            ->assertSee('Search ID, name, customer, serial…');
    }
}
