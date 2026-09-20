<?php

namespace Tests\Feature;

use App\Models\{AccessScope,ApprovalAuthority,ApprovalRequest,Customer,Role,ServiceRequest,Tenant,User};
use App\Services\ApprovalAuthorityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApprovalAuthorityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_amount_and_scope_are_enforced_once_authority_is_configured(): void
    {
        $tenant=Tenant::create(['name'=>'Authority Test','code'=>'AUTH-T','status'=>'ACTIVE']);
        $customer=Customer::create([
            'tenant_id'=>$tenant->id,'customer_code'=>'AUTH-C','name'=>'Authority Customer','status'=>'ACTIVE',
        ]);
        $role=Role::query()->firstOrCreate(
            ['tenant_id'=>$tenant->id,'code'=>'FINANCE_MANAGER'],
            ['name_en'=>'Finance Manager','is_active'=>true,'grants_business_authority'=>true]
        );
        $user=User::create([
            'tenant_id'=>$tenant->id,'name'=>'Finance Manager','email'=>'finance-authority@example.test',
            'password'=>'password','role'=>'FINANCE_MANAGER','status'=>'ACTIVE','user_type'=>'INTERNAL',
        ]);
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,
            'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $scope=AccessScope::create([
            'tenant_id'=>$tenant->id,'scope_type'=>'CUSTOMER','scope_id'=>$customer->id,
            'name'=>'Customer Authority','is_active'=>true,
        ]);
        ApprovalAuthority::create([
            'tenant_id'=>$tenant->id,'approval_type'=>'FINANCE_REVIEW','role_id'=>$role->id,
            'level'=>1,'access_scope_id'=>$scope->id,'amount_limit'=>1000,'is_active'=>true,
        ]);

        $request=ServiceRequest::create([
            'tenant_id'=>$tenant->id,'customer_id'=>$customer->id,'request_no'=>'SR-AUTH-1',
            'company_name'=>$customer->name,'service_category'=>'Maintenance','subject'=>'Authority',
            'details'=>'Authority test','priority'=>'NORMAL','status'=>'OPEN',
            'workflow_context'=>['estimated_value'=>900],
        ]);
        $approval=ApprovalRequest::create([
            'tenant_id'=>$tenant->id,'entity_type'=>ServiceRequest::class,'entity_id'=>$request->id,
            'action'=>'FINANCE_REVIEW','approval_role'=>'FINANCE_MANAGER','requested_by'=>$user->id,
            'status'=>'PENDING',
        ]);

        app(ApprovalAuthorityService::class)->assertAllows($user,$approval,$request);
        $this->assertTrue(true);

        $request->update(['workflow_context'=>['estimated_value'=>1500]]);

        $this->expectException(ValidationException::class);
        app(ApprovalAuthorityService::class)->assertAllows($user,$approval,$request->fresh());
    }

    public function test_unconfigured_approval_type_keeps_existing_role_workflow_compatible(): void
    {
        $tenant=Tenant::create(['name'=>'Authority Compat','code'=>'AUTH-COMP','status'=>'ACTIVE']);
        $role=Role::query()->firstOrCreate(
            ['tenant_id'=>$tenant->id,'code'=>'CEO'],
            ['name_en'=>'CEO','is_active'=>true,'grants_business_authority'=>true]
        );
        $user=User::create([
            'tenant_id'=>$tenant->id,'name'=>'CEO','email'=>'ceo-authority@example.test',
            'password'=>'password','role'=>'CEO','status'=>'ACTIVE','user_type'=>'INTERNAL',
        ]);
        DB::table('user_roles')->insert([
            'tenant_id'=>$tenant->id,'user_id'=>$user->id,'role_id'=>$role->id,'is_primary'=>true,
            'granted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $request=ServiceRequest::create([
            'tenant_id'=>$tenant->id,'request_no'=>'SR-AUTH-COMP','company_name'=>'Compat',
            'service_category'=>'Contract','subject'=>'Compat','details'=>'Compat',
            'priority'=>'NORMAL','status'=>'OPEN',
        ]);
        $approval=ApprovalRequest::create([
            'tenant_id'=>$tenant->id,'entity_type'=>ServiceRequest::class,'entity_id'=>$request->id,
            'action'=>'EXECUTIVE_APPROVAL','approval_role'=>'CEO','requested_by'=>$user->id,'status'=>'PENDING',
        ]);

        app(ApprovalAuthorityService::class)->assertAllows($user,$approval,$request);
        $this->assertTrue(true);
    }
}
