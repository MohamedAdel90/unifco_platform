<?php

namespace Tests\Feature;

use App\Models\{Asset,AssetCustody,AssetTransfer,Customer,CustomerSite,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetCustodyPhaseCTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'ASSET-C','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'ASSET-C-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'CUS-C','name'=>'Phase C Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'C-RUH','name'=>'Riyadh Site','city'=>'Riyadh','status'=>'ACTIVE']);
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'phasec.engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Manager','email'=>'phasec.manager@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $custodian=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Custodian','email'=>'phasec.custodian@example.test','password'=>'StrongPassword123','role'=>'PROJECT_MANAGER','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'customer_site_id'=>$site->id,'asset_code'=>'AST-C-001','name'=>'Phase C Asset','asset_category'=>'Electrical','asset_type'=>'Generator','criticality'=>'HIGH','ownership_type'=>'CUSTOMER_OWNED','verification_status'=>'VERIFIED','lifecycle_status'=>'ACTIVE','operational_status'=>'ACTIVE','commissioning_status'=>'COMMISSIONED','status'=>'ACTIVE']);
        return compact('tenant','org','customer','site','engineer','manager','custodian','asset');
    }

    public function test_verified_active_asset_can_receive_custody(): void
    {
        $d=$this->data();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/custody',['custodian_user_id'=>$d['custodian']->id,'department'=>'Operations'])->assertRedirect();
        $this->assertDatabaseHas('asset_custodies',['asset_id'=>$d['asset']->id,'custodian_user_id'=>$d['custodian']->id,'status'=>'ACTIVE']);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$d['asset']->id,'event_type'=>'CUSTODY_ASSIGNED']);
    }

    public function test_duplicate_active_custody_is_blocked(): void
    {
        $d=$this->data(); $url='/asset-master/'.$d['asset']->id.'/custody';
        $this->actingAs($d['engineer'])->post($url,['custodian_user_id'=>$d['custodian']->id])->assertRedirect();
        $this->actingAs($d['engineer'])->post($url,['custodian_name'=>'Second Custodian'])->assertStatus(422);
        $this->assertSame(1,AssetCustody::where('asset_id',$d['asset']->id)->where('status','ACTIVE')->count());
    }

    public function test_transfer_requires_independent_approval_and_replaces_custody(): void
    {
        $d=$this->data();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/custody',['custodian_name'=>'Old Custodian'])->assertRedirect();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/transfers',['custodian_user_id'=>$d['custodian']->id,'department'=>'Field Ops','reason'=>'Operational reassignment'])->assertRedirect();
        $transfer=AssetTransfer::firstOrFail();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/transfers/'.$transfer->id.'/review',['decision'=>'APPROVE'])->assertForbidden();
        $this->actingAs($d['manager'])->post('/asset-master/'.$d['asset']->id.'/transfers/'.$transfer->id.'/review',['decision'=>'APPROVE','notes'=>'Approved'])->assertRedirect();
        $this->assertSame('APPROVED',$transfer->fresh()->status);
        $this->assertSame(1,AssetCustody::where('asset_id',$d['asset']->id)->where('status','ACTIVE')->count());
        $this->assertDatabaseHas('asset_custodies',['asset_id'=>$d['asset']->id,'custodian_user_id'=>$d['custodian']->id,'status'=>'ACTIVE']);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$d['asset']->id,'event_type'=>'ASSET_TRANSFERRED']);
    }

    public function test_transfer_requester_cannot_self_approve_even_if_checker(): void
    {
        $d=$this->data();
        $this->actingAs($d['manager'])->post('/asset-master/'.$d['asset']->id.'/transfers',['custodian_name'=>'New Custodian','reason'=>'Branch transfer'])->assertRedirect();
        $transfer=AssetTransfer::firstOrFail();
        $this->actingAs($d['manager'])->post('/asset-master/'.$d['asset']->id.'/transfers/'.$transfer->id.'/review',['decision'=>'APPROVE'])->assertStatus(422);
        $this->assertSame('PENDING_APPROVAL',$transfer->fresh()->status);
    }

    public function test_ineligible_asset_cannot_be_assigned_or_transferred(): void
    {
        $d=$this->data(); $d['asset']->update(['lifecycle_status'=>'DECOMMISSIONED']);
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/custody',['custodian_name'=>'Custodian'])->assertStatus(422);
        $this->actingAs($d['engineer'])->post('/asset-master/'.$d['asset']->id.'/transfers',['custodian_name'=>'Custodian','reason'=>'Move'])->assertStatus(422);
    }

    public function test_phase_c_is_tenant_isolated(): void
    {
        $d=$this->data();
        $otherTenant=Tenant::create(['name'=>'Other','code'=>'OTHER-C','status'=>'ACTIVE']);
        $otherOrg=Organization::create(['tenant_id'=>$otherTenant->id,'name'=>'Other HQ','code'=>'OTHER-C-HQ','status'=>'ACTIVE']);
        $other=User::create(['tenant_id'=>$otherTenant->id,'organization_id'=>$otherOrg->id,'name'=>'Other','email'=>'phasec.other@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $this->actingAs($other)->get('/asset-master/'.$d['asset']->id.'/custody')->assertNotFound();
    }
}
