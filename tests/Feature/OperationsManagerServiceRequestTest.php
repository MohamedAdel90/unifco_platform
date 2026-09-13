<?php

namespace Tests\Feature;

use App\Models\{AccessScope, Organization, Role, ServiceRequest, Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsManagerServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    private function manager(bool $global = true): User
    {
        $tenant = Tenant::create(['name'=>'Ops Service','code'=>'OPS-SR','status'=>'ACTIVE']);
        $org = Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'OPS-SR-HQ','status'=>'ACTIVE']);
        $user = User::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Operations Manager',
            'email'=>'ops-sr-manager@example.test','password'=>'password','role'=>'OPERATIONS_MANAGER','user_type'=>'INTERNAL','status'=>'ACTIVE',
        ]);
        $role = Role::whereNull('tenant_id')->where('code','OPERATIONS_MANAGER')->firstOrFail();
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,
            'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        if ($global) {
            $scope = AccessScope::create(['tenant_id'=>$tenant->id,'scope_type'=>'GLOBAL','name'=>'Global Operations','is_active'=>true]);
            DB::table('user_scopes')->insert([
                'tenant_id'=>$tenant->id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,'source'=>'SYSTEM',
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        }
        return $user;
    }

    private function serviceRequest(User $manager, array $extra = []): ServiceRequest
    {
        return ServiceRequest::create(array_merge([
            'tenant_id'=>$manager->tenant_id,
            'organization_id'=>$manager->organization_id,
            'request_no'=>'SR-OPS-001',
            'request_type'=>'MAINTENANCE',
            'company_name'=>'Operations Test Customer',
            'email'=>'customer@example.test',
            'mobile'=>'0500000000',
            'service_category'=>'MAINTENANCE',
            'subject'=>'Emergency generator inspection',
            'details'=>'Generator stopped unexpectedly.',
            'site_city'=>'Riyadh',
            'priority'=>'EMERGENCY',
            'status'=>'OPEN',
            'workflow_stage'=>'NEW',
            'current_stage_due_at'=>now()->subMinutes(5),
        ], $extra));
    }

    public function test_manager_can_open_scoped_service_request_control_center(): void
    {
        $manager = $this->manager();
        $this->serviceRequest($manager);

        $this->actingAs($manager)->get('/operations-manager/service-requests')
            ->assertOk()
            ->assertSee('Service Request Control Center')
            ->assertSee('SR-OPS-001')
            ->assertSee('SLA Overdue');
    }

    public function test_manager_can_assign_and_escalate_service_request_within_scope(): void
    {
        $manager = $this->manager();
        $serviceRequest = $this->serviceRequest($manager);
        $technician = User::create([
            'tenant_id'=>$manager->tenant_id,'organization_id'=>$manager->organization_id,'name'=>'Field Technician',
            'email'=>'field-technician@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE',
        ]);

        $this->actingAs($manager)->post(route('operations-manager.service-requests.assign',$serviceRequest),[
            'assigned_engineer_id'=>$technician->id,
        ])->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame($technician->id, $serviceRequest->assigned_engineer_id);
        $this->assertSame('ASSIGNED', $serviceRequest->workflow_stage);

        $this->actingAs($manager)->post(route('operations-manager.service-requests.escalate',$serviceRequest),[
            'reason'=>'Emergency response window exceeded.',
        ])->assertRedirect();

        $this->assertSame('ESCALATED', $serviceRequest->fresh()->workflow_stage);
    }

    public function test_structured_manager_without_scope_cannot_mutate_service_request(): void
    {
        $manager = $this->manager(false);
        $serviceRequest = $this->serviceRequest($manager);
        $technician = User::create([
            'tenant_id'=>$manager->tenant_id,'organization_id'=>$manager->organization_id,'name'=>'Field Technician',
            'email'=>'no-scope-tech@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE',
        ]);

        $this->actingAs($manager)->post(route('operations-manager.service-requests.assign',$serviceRequest),[
            'assigned_engineer_id'=>$technician->id,
        ])->assertForbidden();
    }
}
