<?php

namespace Tests\Feature;

use App\Models\{AccessScope,ApprovalRequest,Asset,Customer,Employee,Project,ProjectUserAssignment,Role,ServiceRequest,Tenant,User,WorkOrder};
use App\Services\{ScopeService,ServiceRequestWorkflowService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectScopedWorkOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_scoped_team_sees_only_linked_work_order_and_can_validate_customer_owned_asset(): void
    {
        $tenant = Tenant::create(['name'=>'Project work orders','code'=>'PRJ-WO-TEST','status'=>'ACTIVE']);
        $customer = Customer::create(['tenant_id'=>$tenant->id,'customer_code'=>'PRJ-WO-C','name'=>'Customer','status'=>'ACTIVE']);
        $projects = [];
        foreach ([1,2] as $number) {
            $projects[] = Project::create([
                'tenant_id'=>$tenant->id,'customer_id'=>$customer->id,
                'project_no'=>'PRJ-WO-'.$number,'name'=>'Project '.$number,'budget'=>0,'status'=>'ACTIVE',
            ]);
        }
        $role = Role::firstOrCreate(['tenant_id'=>$tenant->id,'code'=>'TECHNICAL_SUPERVISOR'],
            ['name_en'=>'Technical Supervisor','is_active'=>true,'grants_business_authority'=>true]);
        $supervisor = User::create([
            'tenant_id'=>$tenant->id,'name'=>'Supervisor','email'=>'project-wo-supervisor@example.test',
            'password'=>'password','role'=>'TECHNICAL_SUPERVISOR','user_type'=>'INTERNAL','status'=>'ACTIVE',
        ]);
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$supervisor->id,'role_id'=>$role->id,
            'is_primary'=>true,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $scope = AccessScope::create([
            'tenant_id'=>$tenant->id,'scope_type'=>'PROJECT','scope_id'=>$projects[0]->id,
            'name'=>'Project 1','is_active'=>true,
        ]);
        DB::table('user_scopes')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$supervisor->id,'access_scope_id'=>$scope->id,
            'source'=>'PROJECT_TEAM','created_at'=>now(),'updated_at'=>now(),
        ]);
        ProjectUserAssignment::create([
            'tenant_id'=>$tenant->id,'project_id'=>$projects[0]->id,'user_id'=>$supervisor->id,
            'project_role'=>'TECHNICAL_SUPERVISOR','access_level'=>'PROJECT','status'=>'ACTIVE',
        ]);

        $orders = [];
        $requests = [];
        foreach ($projects as $index => $project) {
            $asset = Asset::create([
                'tenant_id'=>$tenant->id,'customer_id'=>$customer->id,
                'asset_code'=>'PRJ-WO-ASSET-'.($index+1),'name'=>'Customer pump',
            ]);
            $orders[] = WorkOrder::create([
                'tenant_id'=>$tenant->id,'work_order_no'=>'WO-PRJ-'.($index+1),
                'asset_id'=>$asset->id,'status'=>'OPEN',
            ]);
            $requests[] = ServiceRequest::create([
                'tenant_id'=>$tenant->id,'customer_id'=>$customer->id,'project_id'=>$project->id,
                'asset_id'=>$asset->id,'work_order_id'=>$orders[$index]->id,
                'request_no'=>'SR-PRJ-WO-'.($index+1),'request_type'=>'MAINTENANCE',
                'workflow_key'=>'MAINTENANCE','company_name'=>$customer->name,
                'service_category'=>'Maintenance','subject'=>'Pump','details'=>'UAT',
                'priority'=>'NORMAL','status'=>'OPEN','workflow_stage'=>'TECHNICIAN_ASSIGNMENT',
                'eligibility'=>'IN_CONTRACT',
            ]);
        }
        foreach ([['TECHNICIAN_ASSIGNMENT','TECHNICAL_SUPERVISOR',1,'PENDING'],
                  ['EXECUTION','TECHNICIAN',2,'WAITING']] as [$stage,$approvalRole,$order,$status]) {
            ApprovalRequest::create([
                'tenant_id'=>$tenant->id,'entity_type'=>ServiceRequest::class,'entity_id'=>$requests[0]->id,
                'action'=>$stage,'approval_role'=>$approvalRole,'step_order'=>$order,
                'status'=>$status,'requested_by'=>$supervisor->id,'sla_minutes'=>60,
            ]);
        }

        $employee = Employee::create([
            'tenant_id'=>$tenant->id,'employee_no'=>'PRJ-WO-TECH','name'=>'Project technician',
            'email'=>'project-tech@example.test','status'=>'ACTIVE',
        ]);
        $technicianRole = Role::firstOrCreate(['tenant_id'=>$tenant->id,'code'=>'TECHNICIAN'],
            ['name_en'=>'Technician','is_active'=>true,'grants_business_authority'=>true]);
        $technician = User::create([
            'tenant_id'=>$tenant->id,'employee_id'=>$employee->id,'name'=>'Project technician',
            'email'=>'project-tech@example.test','password'=>'password',
            'role'=>'TECHNICIAN','user_type'=>'INTERNAL','status'=>'ACTIVE',
        ]);
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$technician->id,'role_id'=>$technicianRole->id,
            'is_primary'=>true,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        ProjectUserAssignment::create([
            'tenant_id'=>$tenant->id,'project_id'=>$projects[0]->id,'user_id'=>$technician->id,
            'project_role'=>'TECHNICIAN','access_level'=>'PROJECT','status'=>'ACTIVE',
        ]);
        $requests[0]->update(['assigned_engineer_id'=>$technician->id]);

        $this->actingAs($supervisor);
        $scopeService = app(ScopeService::class);
        $this->assertTrue($scopeService->allows($supervisor,$orders[0]));
        $this->assertFalse($scopeService->allows($supervisor,$orders[1]));
        $this->assertFalse(Asset::query()->whereKey($requests[0]->asset_id)->exists());
        $this->assertSame([$orders[0]->id], WorkOrder::query()
            ->whereIn('id', array_map(fn ($order) => $order->id, $orders))->pluck('id')->all());
        app(ServiceRequestWorkflowService::class)->advance($requests[0], 'TECHNICIAN_ASSIGNMENT', $supervisor->id, 'UAT routing');
        $this->assertSame('EXECUTION', $requests[0]->fresh()->workflow_stage);
        $this->assertDatabaseHas('work_order_assignments',[
            'tenant_id'=>$tenant->id,'work_order_id'=>$orders[0]->id,
            'employee_id'=>$employee->id,'dispatch_status'=>'DISPATCHED',
        ]);
    }
}
