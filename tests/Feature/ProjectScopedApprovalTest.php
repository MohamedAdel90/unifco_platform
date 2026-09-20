<?php

namespace Tests\Feature;

use App\Models\{AccessScope,ApprovalRequest,Customer,Project,ProjectUserAssignment,Role,ServiceRequest,Tenant,User};
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectScopedApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::create(['name'=>'Project Approval','code'=>'PRJ-APP','status'=>'ACTIVE']);
    }

    private function role(Tenant $tenant,string $code): Role
    {
        return Role::query()->firstOrCreate(
            ['tenant_id'=>$tenant->id,'code'=>$code],
            ['name_en'=>str($code)->headline(),'is_active'=>true,'grants_business_authority'=>true]
        );
    }

    private function user(Tenant $tenant,Role $role,string $email): User
    {
        $user=User::query()->create([
            'tenant_id'=>$tenant->id,'name'=>$email,'email'=>$email,'password'=>'password',
            'role'=>$role->code,'status'=>'ACTIVE','user_type'=>'INTERNAL',
        ]);
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,
            'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return $user;
    }

    public function test_same_role_user_cannot_decide_project_approval_without_project_assignment(): void
    {
        $tenant=$this->tenant();
        $customer=Customer::query()->create([
            'tenant_id'=>$tenant->id,'customer_code'=>'C-APP','name'=>'Approval Customer','status'=>'ACTIVE',
        ]);
        $project=Project::query()->create([
            'tenant_id'=>$tenant->id,'project_no'=>'P-APP','name'=>'Approval Project',
            'customer_id'=>$customer->id,'budget'=>0,'status'=>'ACTIVE',
        ]);
        $role=$this->role($tenant,'PROJECT_MANAGER');
        $assigned=$this->user($tenant,$role,'assigned-pm@example.test');
        $other=$this->user($tenant,$role,'other-pm@example.test');
        $requester=$this->user($tenant,$this->role($tenant,'CUSTOMER_SERVICE'),'requester@example.test');

        $scope=AccessScope::query()->create([
            'tenant_id'=>$tenant->id,'scope_type'=>'PROJECT','scope_id'=>$project->id,'name'=>$project->project_no,'is_active'=>true,
        ]);
        foreach([$assigned,$other] as $user){
            DB::table('user_scopes')->insert([
                'tenant_id'=>$tenant->id,'user_id'=>$user->id,'access_scope_id'=>$scope->id,
                'source'=>'USER','created_at'=>now(),'updated_at'=>now(),
            ]);
        }
        ProjectUserAssignment::query()->create([
            'tenant_id'=>$tenant->id,'project_id'=>$project->id,'user_id'=>$assigned->id,
            'project_role'=>'PROJECT_MANAGER','access_level'=>'PROJECT','status'=>'ACTIVE',
        ]);

        $serviceRequest=ServiceRequest::query()->create([
            'tenant_id'=>$tenant->id,'customer_id'=>$customer->id,'project_id'=>$project->id,
            'request_no'=>'SR-APP-1','company_name'=>$customer->name,'service_category'=>'MAINTENANCE',
            'details'=>'approval test','request_type'=>'CORRECTIVE','priority'=>'P2','subject'=>'Approval test',
            'status'=>'OPEN','workflow_stage'=>'PROJECT_MANAGER_REVIEW',
        ]);
        $approval=ApprovalRequest::query()->create([
            'tenant_id'=>$tenant->id,'entity_type'=>ServiceRequest::class,'entity_id'=>$serviceRequest->id,
            'action'=>'PROJECT_MANAGER_REVIEW','approval_role'=>'PROJECT_MANAGER','requested_by'=>$requester->id,
            'status'=>'PENDING',
        ]);

        Auth::login($other);
        try {
            app(ApprovalService::class)->decide($approval,'APPROVED');
            $this->fail('Expected project assignment validation to block the decision.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('not the active PROJECT_MANAGER assigned to this project',implode(' ',$e->errors()['approval']??[]));
        }

        $this->assertSame('PENDING',$approval->fresh()->status);
    }
}
