<?php

namespace Tests\Feature;

use App\Models\{Asset,AssetCategoryTemplate,Customer,CustomerSite,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalAssetMasterV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_engineer_sees_dropdown_first_wizard_dynamic_specs_hierarchy_and_registry_filters(): void
    {
        [$tenant,$org,$customer,$site]=$this->fixture();
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'asset.v21@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        $response=$this->actingAs($engineer)->get('/asset-master');
        $response->assertOk()
            ->assertSee('1 · Identification')->assertSee('2 · Location')->assertSee('3 · Technical')->assertSee('4 · Lifecycle')->assertSee('5 · Review')
            ->assertSee('dropdown-first master data')->assertSee('Parent Asset')->assertSee('Managed Location')
            ->assertSee('Transformer Standard')->assertSee('Structured fields appear automatically',false)
            ->assertSee('Useful Life')->assertSee('Review before submission')->assertSee('Search ID, name, customer, site, serial…')
            ->assertSee('Verification')->assertSee('Completeness');
    }

    public function test_maintenance_manager_can_register_assets_and_sees_independent_verification_control(): void
    {
        [$tenant,$org]=$this->fixture();
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Maintenance Manager','email'=>'manager.asset@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);

        $response=$this->actingAs($manager)->get('/asset-master');
        $response->assertOk()
            ->assertSee('Register Customer Asset')
            ->assertSee('Maker / Checker control:')
            ->assertSee('same user cannot Verify & Activate an asset they created')
            ->assertSee('Create Pending Verification Asset');
    }

    private function fixture(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'AM-V21','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'AM-V21-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'CUS-AMV21','name'=>'Asset V21 Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'AMV21-RUH','name'=>'Riyadh Plant','city'=>'Riyadh','status'=>'ACTIVE']);
        AssetCategoryTemplate::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'category'=>'Electrical','asset_type'=>'Transformer','name'=>'Transformer Standard','code'=>'TR-STD','system_group'=>'ELECTRICAL','active'=>true,'status'=>'ACTIVE','specification_schema'=>['rated_power_kva'=>['label'=>'Rated Power kVA','type'=>'number'],'cooling_type'=>['label'=>'Cooling Type','type'=>'text','options'=>['ONAN','ONAF']]]]);
        Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,'asset_code'=>'AST-PARENT-001','name'=>'Main Transformer Bank','asset_category'=>'Electrical','asset_type'=>'Transformer','manufacturer'=>'ABB','criticality'=>'HIGH','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE','operational_status'=>'ACTIVE','status'=>'ACTIVE','qr_token'=>'AMV21-PARENT-QR','data_completeness_score'=>70]);
        return [$tenant,$org,$customer,$site];
    }
}
