<?php

namespace Tests\Feature;

use App\Models\{Asset,AssetCommissioningRecord,AssetLifecycleEvent,AssetLocation,Customer,CustomerSite,Organization,Tenant,User};
use App\Services\AssetMasterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AssetLifecyclePhaseBTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO','code'=>'ASSET-B','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'ASSET-B-HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'CUS-B','name'=>'Phase B Customer','status'=>'ACTIVE','onboarding_status'=>'ACTIVE']);
        $site=CustomerSite::create(['customer_id'=>$customer->id,'site_code'=>'B-RUH','name'=>'Riyadh Plant','city'=>'Riyadh','status'=>'ACTIVE']);
        $engineer=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Engineer','email'=>'phaseb.engineer@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_ENGINEER','status'=>'ACTIVE']);
        $manager=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Manager','email'=>'phaseb.manager@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        return compact('tenant','org','customer','site','engineer','manager');
    }

    private function asset(array $d,string $serial='GEN-B-001',string $customerCode='GEN-01'): Asset
    {
        $this->actingAs($d['engineer'])->post('/asset-master',[
            'customer_id'=>$d['customer']->id,'customer_site_id'=>$d['site']->id,'name'=>'Generator '.$customerCode,'customer_asset_code'=>$customerCode,
            'asset_category'=>'Electrical','asset_type'=>'Diesel Generator','manufacturer'=>'Caterpillar','model_no'=>'C18','serial_no'=>$serial,
            'criticality'=>'CRITICAL','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>'PREVENTIVE','physical_location'=>'Generator Yard',
            'installation_date'=>'2026-01-10','technical_specifications'=>'{"rating_kva":1000,"voltage":"400V"}',
        ])->assertRedirect();
        return Asset::where('serial_no',$serial)->firstOrFail();
    }

    public function test_location_hierarchy_can_be_created_and_asset_assigned(): void
    {
        $d=$this->setupData(); $asset=$this->asset($d);
        $this->actingAs($d['manager'])->post('/asset-master/locations',[
            'customer_id'=>$d['customer']->id,'customer_site_id'=>$d['site']->id,'location_type'=>'BUILDING','code'=>'BLD-A','name'=>'Main Building',
        ])->assertRedirect();
        $building=AssetLocation::where('code','BLD-A')->firstOrFail();
        $this->actingAs($d['manager'])->post('/asset-master/locations',[
            'customer_id'=>$d['customer']->id,'customer_site_id'=>$d['site']->id,'parent_id'=>$building->id,'location_type'=>'ROOM','code'=>'ELEC-01','name'=>'Electrical Room 01',
        ])->assertRedirect();
        $room=AssetLocation::where('code','ELEC-01')->firstOrFail();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$asset->id.'/assign-location',['asset_location_id'=>$room->id])->assertRedirect();
        $this->assertSame($room->id,(int)$asset->fresh()->asset_location_id);
    }

    public function test_asset_registration_and_verification_create_lifecycle_events(): void
    {
        $d=$this->setupData(); $asset=$this->asset($d);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$asset->id,'event_type'=>'ASSET_REGISTERED','to_status'=>'PENDING_VERIFICATION']);
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/verify',['notes'=>'Verified'])->assertRedirect();
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$asset->id,'event_type'=>'ASSET_VERIFIED','to_status'=>'ACTIVE']);
    }

    public function test_commissioning_requires_independent_checker_and_activates_asset(): void
    {
        $d=$this->setupData(); $asset=$this->asset($d);
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/verify')->assertRedirect();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$asset->id.'/commissioning',[
            'inspection_date'=>'2026-08-27','inspection_result'=>'PASS','checklist'=>'{"safety":"PASS","load_test":"PASS"}','notes'=>'Commissioning complete.',
        ])->assertRedirect();
        $record=AssetCommissioningRecord::where('asset_id',$asset->id)->firstOrFail();
        $this->assertSame('PENDING_APPROVAL',$record->status);
        $this->assertSame('PENDING_APPROVAL',$asset->fresh()->commissioning_status);

        $this->actingAs($d['engineer'])->post('/asset-master/'.$asset->id.'/commissioning/'.$record->id.'/review',['decision'=>'APPROVE'])->assertForbidden();
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/commissioning/'.$record->id.'/review',['decision'=>'APPROVE','notes'=>'Independent check passed.'])->assertRedirect();
        $asset->refresh(); $record->refresh();
        $this->assertSame('COMMISSIONED',$asset->commissioning_status);
        $this->assertSame('ACTIVE',$asset->lifecycle_status);
        $this->assertSame('APPROVED',$record->status);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$asset->id,'event_type'=>'COMMISSIONING_APPROVED']);
    }

    public function test_failed_commissioning_inspection_cannot_be_approved(): void
    {
        $d=$this->setupData(); $asset=$this->asset($d);
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/verify')->assertRedirect();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$asset->id.'/commissioning',[
            'inspection_date'=>'2026-08-27','inspection_result'=>'FAIL','checklist'=>'{"safety":"FAIL"}','notes'=>'Safety check failed.',
        ])->assertRedirect();
        $record=AssetCommissioningRecord::where('asset_id',$asset->id)->firstOrFail();
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/commissioning/'.$record->id.'/review',['decision'=>'APPROVE'])->assertStatus(422);
        $this->assertSame('PENDING_APPROVAL',$record->fresh()->status);
        $this->assertNotSame('COMMISSIONED',$asset->fresh()->commissioning_status);
    }

    public function test_commissioning_approval_cannot_bypass_lifecycle_transition(): void
    {
        $d=$this->setupData(); $asset=$this->asset($d);
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/verify')->assertRedirect();
        $this->actingAs($d['engineer'])->post('/asset-master/'.$asset->id.'/commissioning',[
            'inspection_date'=>'2026-08-27','inspection_result'=>'PASS','checklist'=>'{"safety":"PASS"}',
        ])->assertRedirect();
        $record=AssetCommissioningRecord::where('asset_id',$asset->id)->firstOrFail();
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/transition',['to_status'=>'UNDER_MAINTENANCE'])->assertRedirect();
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/commissioning/'.$record->id.'/review',['decision'=>'APPROVE'])->assertStatus(422);
        $this->assertSame('UNDER_MAINTENANCE',$asset->fresh()->lifecycle_status);
        $this->assertSame('PENDING_APPROVAL',$record->fresh()->status);
    }

    public function test_invalid_lifecycle_transition_is_blocked_and_valid_transition_is_logged(): void
    {
        $d=$this->setupData(); $asset=$this->asset($d);
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/verify')->assertRedirect();
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/transition',['to_status'=>'DISPOSED'])->assertStatus(422);
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/transition',['to_status'=>'UNDER_MAINTENANCE','notes'=>'Planned overhaul'])->assertRedirect();
        $asset->refresh();
        $this->assertSame('UNDER_MAINTENANCE',$asset->lifecycle_status);
        $this->assertDatabaseHas('asset_lifecycle_events',['asset_id'=>$asset->id,'event_type'=>'LIFECYCLE_TRANSITION','from_status'=>'ACTIVE','to_status'=>'UNDER_MAINTENANCE']);
    }

    public function test_verification_cannot_reactivate_asset_outside_controlled_transition(): void
    {
        $d=$this->setupData(); $asset=$this->asset($d);
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/verify')->assertRedirect();
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/transition',['to_status'=>'DECOMMISSIONED'])->assertRedirect();
        $this->actingAs($d['manager'])->post('/asset-master/'.$asset->id.'/verify')->assertStatus(422);
        $this->assertSame('DECOMMISSIONED',$asset->fresh()->lifecycle_status);
    }

    public function test_asset_hierarchy_rejects_cycles(): void
    {
        $d=$this->setupData();
        $first=$this->asset($d,'GEN-B-001','GEN-01');
        $second=$this->asset($d,'GEN-B-002','GEN-02');
        $service=app(AssetMasterService::class);
        $service->update($first,['parent_asset_id'=>$second->id]);
        try {
            $service->update($second,['parent_asset_id'=>$first->id]);
            $this->fail('Expected cyclic hierarchy to be rejected.');
        } catch (HttpException $e) {
            $this->assertSame(422,$e->getStatusCode());
        }
        $this->assertNull($second->fresh()->parent_asset_id);
    }

    public function test_phase_b_data_is_tenant_isolated(): void
    {
        $d=$this->setupData(); $asset=$this->asset($d);
        $otherTenant=Tenant::create(['name'=>'Other','code'=>'OTHER-B','status'=>'ACTIVE']);
        $otherOrg=Organization::create(['tenant_id'=>$otherTenant->id,'name'=>'Other HQ','code'=>'OTHER-HQ','status'=>'ACTIVE']);
        $other=User::create(['tenant_id'=>$otherTenant->id,'organization_id'=>$otherOrg->id,'name'=>'Other Manager','email'=>'other.phaseb@example.test','password'=>'StrongPassword123','role'=>'MAINTENANCE_MANAGER','status'=>'ACTIVE']);
        $this->actingAs($other)->get('/asset-master/'.$asset->id)->assertNotFound();
    }
}
