<?php

namespace Tests\Feature;

use App\Models\{Asset,AssetCoverage,AssetCoverageClaim,Customer,CustomerSite,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetWarrantyInsurancePhaseDTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'ASSET-D','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'ASSET-D-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'CUS-D','name'=>'Phase D Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'D-RUH','name'=>'Riyadh Site','city'=>'Riyadh','status'=>'ACTIVE']);
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'phased.engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Manager','email'=>'phased.manager@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,'asset_code'=>'AST-D-001','name'=>'Phase D Asset','asset_category'=>'Electrical','asset_type'=>'Generator','criticality'=>'HIGH','ownership_type'=>'CUSTOMER_OWNED','verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE','operational_status'=>'ACTIVE','commissioning_status'=>'COMMISSIONED','status'=>'ACTIVE']);
        return compact('tenant','org','customer','site','engineer','manager','asset');
    }

    public function test_warranty_and_insurance_coverages_are_recorded_with_lifecycle_events(): void
    {
        $d=$this->data();
        foreach(['WARRANTY','INSURANCE'] as $type){
            $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/coverage',[
                'coverage_type'=>$type,'provider'=>$type.' Provider','reference_no'=>$type.'-001','starts_at'=>today()->toDateString(),'expires_at'=>today()->addYear()->toDateString(),'coverage_amount'=>100000,'currency'=>'SAR','scope'=>'Parts and labor',
            ])->assertRedirect();
        }
        $this->assertDatabaseHas('asset_coverages',['asset_id'=>$d['asset']->id,'coverage_type'=>'WARRANTY','status'=>'ACTIVE']);
        $this->assertDatabaseHas('asset_coverages',['asset_id'=>$d['asset']->id,'coverage_type'=>'INSURANCE','status'=>'ACTIVE']);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$d['asset']->id,'event_type'=>'WARRANTY_COVERAGE_ADDED']);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$d['asset']->id,'event_type'=>'INSURANCE_COVERAGE_ADDED']);
    }

    public function test_coverage_register_surfaces_expiry_alerts(): void
    {
        $d=$this->data();
        AssetCoverage::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'asset_id'=>$d['asset']->id,'coverage_type'=>'WARRANTY','provider'=>'OEM','starts_at'=>today()->subYear(),'expires_at'=>today()->addDays(10),'status'=>'ACTIVE','created_by'=>$d['engineer']->id]);
        $this->actingAs($d['engineer'])->get('/asset-master/'.$d['asset']->id.'/coverage')->assertOk()->assertSee('expiring within 30 days');
    }

    public function test_claim_requires_independent_checker_and_tracks_resolution(): void
    {
        $d=$this->data();
        $coverage=AssetCoverage::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'asset_id'=>$d['asset']->id,'coverage_type'=>'INSURANCE','provider'=>'Insurer','starts_at'=>today()->subMonth(),'expires_at'=>today()->addYear(),'coverage_amount'=>50000,'currency'=>'SAR','status'=>'ACTIVE','created_by'=>$d['engineer']->id]);
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/coverage/'.$coverage->id.'/claims',['claim_no'=>'CLM-001','claim_date'=>today()->toDateString(),'claimed_amount'=>10000,'description'=>'Covered equipment failure'])->assertRedirect();
        $claim=AssetCoverageClaim::firstOrFail();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/coverage/claims/'.$claim->id.'/review',['decision'=>'APPROVE','approved_amount'=>9000])->assertForbidden();
        $this->actingAs($d['manager'])->post('/asset-master/'.$d['asset']->id.'/coverage/claims/'.$claim->id.'/review',['decision'=>'APPROVE','approved_amount'=>9000,'resolution_notes'=>'Approved by insurer'])->assertRedirect();
        $this->assertDatabaseHas('asset_coverage_claims',['id'=>$claim->id,'status'=>'APPROVED','approved_amount'=>9000]);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$d['asset']->id,'event_type'=>'COVERAGE_CLAIM_APPROVED']);
    }

    public function test_claim_submitter_cannot_self_review_even_if_checker(): void
    {
        $d=$this->data();
        $coverage=AssetCoverage::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'asset_id'=>$d['asset']->id,'coverage_type'=>'WARRANTY','provider'=>'OEM','starts_at'=>today()->subMonth(),'expires_at'=>today()->addYear(),'status'=>'ACTIVE','created_by'=>$d['manager']->id]);
        $this->actingAs($d['manager'])->post('/asset-master/'.$d['asset']->id.'/coverage/'.$coverage->id.'/claims',['claim_date'=>today()->toDateString(),'description'=>'Warranty repair'])->assertRedirect();
        $claim=AssetCoverageClaim::firstOrFail();
        $this->actingAs($d['manager'])->post('/asset-master/'.$d['asset']->id.'/coverage/claims/'.$claim->id.'/review',['decision'=>'APPROVE'])->assertStatus(422);
        $this->assertSame('SUBMITTED',$claim->fresh()->status);
    }

    public function test_coverage_can_be_renewed_with_traceability(): void
    {
        $d=$this->data();
        $coverage=AssetCoverage::create(['tenant_id'=>$d['tenant']->id,'organization_id'=>$d['org']->id,'asset_id'=>$d['asset']->id,'coverage_type'=>'INSURANCE','provider'=>'Insurer','reference_no'=>'POL-OLD','starts_at'=>today()->subYear(),'expires_at'=>today()->addDays(5),'status'=>'ACTIVE','created_by'=>$d['engineer']->id]);
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/coverage/'.$coverage->id.'/renew',['reference_no'=>'POL-NEW','starts_at'=>today()->addDays(6)->toDateString(),'expires_at'=>today()->addYear()->toDateString()])->assertRedirect();
        $this->assertSame('RENEWED',$coverage->fresh()->status);
        $this->assertDatabaseHas('asset_coverages',['asset_id'=>$d['asset']->id,'reference_no'=>'POL-NEW','renewed_from_id'=>$coverage->id,'status'=>'ACTIVE']);
    }

    public function test_phase_d_is_tenant_isolated(): void
    {
        $d=$this->data();
        $otherTenant=Tenant::create(['name'=>'Other','code'=>'OTHER-D','status'=>'ACTIVE']);
        $otherOrg=Organization::create(['tenant_id'=>$otherTenant->id,'name'=>'Other HQ','code'=>'OTHER-D-HQ','status'=>'ACTIVE']);
        $other=User::create(['tenant_id'=>$otherTenant->id,'organization_id'=>$otherOrg->id,'name'=>'Other','email'=>'phased.other@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $this->actingAs($other)->get('/asset-master/'.$d['asset']->id.'/coverage')->assertNotFound();
    }
}
