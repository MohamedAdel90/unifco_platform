<?php

namespace Tests\Feature;

use App\Models\{AccessScope,ApprovalRequest,Customer,Project,Role,ServiceRequest,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectScopedApprovalVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_scoped_supervisor_sees_only_approvals_for_requests_in_their_project(): void
    {
        $tenant = Tenant::create(['name' => 'Scoped approvals', 'code' => 'SCOPED-APPROVALS', 'status' => 'ACTIVE']);
        $customer = Customer::create([
            'tenant_id' => $tenant->id, 'customer_code' => 'SCOPE-C1', 'name' => 'Scope Customer', 'status' => 'ACTIVE',
        ]);
        $visibleProject = Project::create([
            'tenant_id' => $tenant->id, 'project_no' => 'SCOPE-P1', 'name' => 'Visible Project',
            'customer_id' => $customer->id, 'budget' => 0, 'status' => 'ACTIVE',
        ]);
        $hiddenProject = Project::create([
            'tenant_id' => $tenant->id, 'project_no' => 'SCOPE-P2', 'name' => 'Hidden Project',
            'customer_id' => $customer->id, 'budget' => 0, 'status' => 'ACTIVE',
        ]);
        $supervisor = User::create([
            'tenant_id' => $tenant->id, 'name' => 'Supervisor', 'email' => 'scoped-supervisor@example.test',
            'password' => 'password', 'role' => 'TECHNICAL_SUPERVISOR', 'user_type' => 'INTERNAL', 'status' => 'ACTIVE',
        ]);
        $role = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'TECHNICAL_SUPERVISOR'],
            ['name_en' => 'Technical Supervisor', 'is_active' => true, 'grants_business_authority' => true]
        );
        DB::table('user_roles')->insert([
            'tenant_id' => $tenant->id, 'user_id' => $supervisor->id, 'role_id' => $role->id,
            'is_primary' => true, 'granted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $scope = AccessScope::create([
            'tenant_id' => $tenant->id, 'scope_type' => 'PROJECT', 'scope_id' => $visibleProject->id,
            'name' => 'Visible Project', 'is_active' => true,
        ]);
        DB::table('user_scopes')->insert([
            'tenant_id' => $tenant->id, 'user_id' => $supervisor->id,
            'access_scope_id' => $scope->id, 'source' => 'PROJECT_TEAM',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $approvals = [];
        foreach ([$visibleProject, $hiddenProject] as $index => $project) {
            $request = ServiceRequest::create([
                'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'project_id' => $project->id,
                'request_no' => 'SR-SCOPE-'.($index + 1), 'request_type' => 'MAINTENANCE',
                'company_name' => $customer->name, 'service_category' => 'Maintenance',
                'subject' => 'Scoped review', 'details' => 'Scope regression',
                'priority' => 'NORMAL', 'status' => 'OPEN',
                'workflow_stage' => 'TECHNICIAN_ASSIGNMENT', 'eligibility' => 'IN_CONTRACT',
            ]);
            $approvals[] = ApprovalRequest::create([
                'tenant_id' => $tenant->id, 'entity_type' => ServiceRequest::class, 'entity_id' => $request->id,
                'action' => 'TECHNICIAN_ASSIGNMENT', 'approval_role' => 'TECHNICAL_SUPERVISOR',
                'assigned_user_id' => $supervisor->id, 'routing_status' => 'ASSIGNED',
                'step_order' => 1, 'status' => 'PENDING', 'requested_by' => $supervisor->id,
            ]);
        }

        $this->actingAs($supervisor);
        $this->assertSame(
            [$approvals[0]->id],
            ApprovalRequest::query()->whereIn('id', array_map(fn ($approval) => $approval->id, $approvals))->pluck('id')->all()
        );
    }
}
