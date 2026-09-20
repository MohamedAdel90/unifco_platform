<?php

namespace Tests\Feature;

use App\Models\{AccessScope,Asset,Customer,OperationalDomain,Project,ProjectUserAssignment,Role,ServiceRequest,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsRoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Operations Routing',
            'code' => 'OPS-ROUTING',
            'status' => 'ACTIVE',
        ]);
    }

    private function customer(Tenant $tenant): Customer
    {
        return Customer::query()->create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'OPS-ROUTING-CUSTOMER',
            'name' => 'Operations Routing Customer',
            'status' => 'ACTIVE',
        ]);
    }

    private function requestPayload(Tenant $tenant, Customer $customer, Asset $asset, string $requestNo, string $subject): array
    {
        return [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'request_no' => $requestNo,
            'company_name' => $customer->name,
            'service_category' => 'MAINTENANCE',
            'details' => $subject.' details',
            'asset_id' => $asset->id,
            'request_type' => 'CORRECTIVE',
            'priority' => 'P2',
            'subject' => $subject,
            'status' => 'NEW',
        ];
    }

    public function test_request_inherits_operational_domain_from_asset(): void
    {
        $tenant = $this->tenant();
        $customer = $this->customer($tenant);
        $domain = OperationalDomain::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'GENERATORS',
            'name_en' => 'Generators',
            'name_ar' => 'المولدات',
            'is_active' => true,
        ]);
        $asset = Asset::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'asset_code' => 'GEN-TEST-001',
            'name' => 'Generator Test',
            'criticality' => 'HIGH',
            'status' => 'ACTIVE',
            'operational_domain_id' => $domain->id,
        ]);

        $request = ServiceRequest::query()->create(
            $this->requestPayload($tenant, $customer, $asset, 'SR-DOMAIN-001', 'Generator request')
        );

        $this->assertSame((int) $domain->id, (int) $request->fresh()->operational_domain_id);
    }

    public function test_request_remains_unassigned_when_no_matching_operations_manager_exists(): void
    {
        $tenant = $this->tenant();
        $customer = $this->customer($tenant);
        $domain = OperationalDomain::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'BATTERIES',
            'name_en' => 'Batteries',
            'name_ar' => 'البطاريات',
            'is_active' => true,
        ]);
        $asset = Asset::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'asset_code' => 'BAT-TEST-001',
            'name' => 'Battery Test',
            'criticality' => 'HIGH',
            'status' => 'ACTIVE',
            'operational_domain_id' => $domain->id,
        ]);

        $request = ServiceRequest::query()->create(
            $this->requestPayload($tenant, $customer, $asset, 'SR-DOMAIN-002', 'Battery request')
        )->fresh();

        $this->assertSame((int) $domain->id, (int) $request->operational_domain_id);
        $this->assertNull($request->operations_manager_id);
        $this->assertSame('UNASSIGNED', $request->operations_routing_status);
    }

    public function test_project_request_routes_only_to_operations_manager_assigned_to_that_project(): void
    {
        $tenant=$this->tenant();
        $customer=$this->customer($tenant);
        $domain=OperationalDomain::query()->create([
            'tenant_id'=>$tenant->id,'code'=>'HVAC','name_en'=>'HVAC','name_ar'=>'التكييف','is_active'=>true,
        ]);
        $project=Project::query()->create([
            'tenant_id'=>$tenant->id,'project_no'=>'P-ROUTE-01','name'=>'Project A','customer_id'=>$customer->id,'budget'=>0,'status'=>'ACTIVE',
        ]);
        $asset=Asset::query()->create([
            'tenant_id'=>$tenant->id,'customer_id'=>$customer->id,'asset_code'=>'HVAC-001','name'=>'AHU',
            'criticality'=>'HIGH','status'=>'ACTIVE','operational_domain_id'=>$domain->id,
        ]);

        $role=Role::query()->create(['tenant_id'=>$tenant->id,'code'=>'OPERATIONS_MANAGER','name_en'=>'Operations Manager','grants_business_authority'=>true,'is_active'=>true]);
        $assigned=User::query()->create(['tenant_id'=>$tenant->id,'name'=>'Assigned OM','email'=>'assigned-om@example.test','password'=>'password','role'=>'OPERATIONS_MANAGER','status'=>'ACTIVE']);
        $other=User::query()->create(['tenant_id'=>$tenant->id,'name'=>'Other OM','email'=>'other-om@example.test','password'=>'password','role'=>'OPERATIONS_MANAGER','status'=>'ACTIVE']);

        foreach([$assigned,$other] as $manager){
            DB::table('user_roles')->insert(['tenant_id'=>$tenant->id,'user_id'=>$manager->id,'role_id'=>$role->id,'is_primary'=>true,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
            DB::table('user_operational_domains')->insert(['user_id'=>$manager->id,'operational_domain_id'=>$domain->id,'assignment_type'=>'PRIMARY','priority'=>$manager->id,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }

        $scope=AccessScope::query()->create(['tenant_id'=>$tenant->id,'scope_type'=>'PROJECT','scope_id'=>$project->id,'name'=>$project->project_no,'is_active'=>true]);
        DB::table('user_scopes')->insert(['tenant_id'=>$tenant->id,'user_id'=>$assigned->id,'access_scope_id'=>$scope->id,'source'=>'PROJECT_TEAM','created_at'=>now(),'updated_at'=>now()]);
        ProjectUserAssignment::query()->create(['tenant_id'=>$tenant->id,'project_id'=>$project->id,'user_id'=>$assigned->id,'project_role'=>'OPERATIONS_MANAGER','access_level'=>'PROJECT','status'=>'ACTIVE']);

        $request=ServiceRequest::query()->create([
            ...$this->requestPayload($tenant,$customer,$asset,'SR-PROJECT-001','Project routed request'),
            'project_id'=>$project->id,
        ])->fresh();

        $this->assertSame($assigned->id,$request->operations_manager_id);
        $this->assertSame('ASSIGNED',$request->operations_routing_status);
    }

    public function test_project_manager_is_resolved_from_active_project_team_assignment(): void
    {
        $tenant=$this->tenant();
        $customer=$this->customer($tenant);
        $project=Project::query()->create([
            'tenant_id'=>$tenant->id,'project_no'=>'P-PM-01','name'=>'Managed Project','customer_id'=>$customer->id,'budget'=>0,'status'=>'ACTIVE',
        ]);
        $role=Role::query()->create(['tenant_id'=>$tenant->id,'code'=>'PROJECT_MANAGER','name_en'=>'Project Manager','grants_business_authority'=>true,'is_active'=>true]);
        $manager=User::query()->create(['tenant_id'=>$tenant->id,'name'=>'Project Manager','email'=>'pm-project@example.test','password'=>'password','role'=>'PROJECT_MANAGER','status'=>'ACTIVE']);
        DB::table('user_roles')->insert(['tenant_id'=>$tenant->id,'user_id'=>$manager->id,'role_id'=>$role->id,'is_primary'=>true,'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        $scope=AccessScope::query()->create(['tenant_id'=>$tenant->id,'scope_type'=>'PROJECT','scope_id'=>$project->id,'name'=>$project->project_no,'is_active'=>true]);
        DB::table('user_scopes')->insert(['tenant_id'=>$tenant->id,'user_id'=>$manager->id,'access_scope_id'=>$scope->id,'source'=>'PROJECT_TEAM','created_at'=>now(),'updated_at'=>now()]);
        ProjectUserAssignment::query()->create(['tenant_id'=>$tenant->id,'project_id'=>$project->id,'user_id'=>$manager->id,'project_role'=>'PROJECT_MANAGER','access_level'=>'PROJECT','status'=>'ACTIVE']);

        $request=ServiceRequest::query()->create([
            'tenant_id'=>$tenant->id,'customer_id'=>$customer->id,'project_id'=>$project->id,'request_no'=>'SR-PM-001',
            'company_name'=>$customer->name,'service_category'=>'CONSULTATION','details'=>'Project manager route',
            'request_type'=>'CONSULTATION','priority'=>'P3','subject'=>'PM route','status'=>'NEW',
        ])->fresh();

        $this->assertSame($manager->id,$request->project_manager_id);
        $this->assertSame('ASSIGNED',$request->project_routing_status);
    }

}
