<?php

namespace Tests\Feature;

use App\Models\{Customer,Employee,Project,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Wave4BusinessWorkspacesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email='admin@test.local', string $tenantCode='T1'): User
    {
        $tenant=Tenant::firstOrCreate(['code'=>$tenantCode],['name'=>$tenantCode,'status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'name'=>$email,'email'=>$email,'password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
    }

    public function test_hr_crm_and_projects_crud_are_tenant_scoped(): void
    {
        $user=$this->admin(); $this->actingAs($user);
        $this->post('/hr/employees',['employee_no'=>'E-1','name'=>'Employee One','email'=>'e1@test.local'])->assertRedirect();
        $this->post('/crm/customers',['customer_code'=>'C-1','name'=>'Customer One','email'=>'c1@test.local'])->assertRedirect();
        $customer=Customer::first();
        $this->post('/projects',['project_no'=>'P-1','name'=>'Project One','customer_id'=>$customer->id,'budget'=>1000])->assertRedirect();
        $this->assertDatabaseHas('employees',['tenant_id'=>$user->tenant_id,'employee_no'=>'E-1']);
        $this->assertDatabaseHas('customers',['tenant_id'=>$user->tenant_id,'customer_code'=>'C-1']);
        $this->assertDatabaseHas('projects',['tenant_id'=>$user->tenant_id,'project_no'=>'P-1','status'=>'DRAFT']);
        $this->assertDatabaseHas('audit_logs',['tenant_id'=>$user->tenant_id,'action'=>'projects.project.created']);
    }

    public function test_other_tenant_records_are_hidden_by_global_scope(): void
    {
        $u1=$this->admin('a@test.local','A'); $u2=$this->admin('b@test.local','B');
        $this->actingAs($u1); Employee::create(['employee_no'=>'A-1','name'=>'A','status'=>'ACTIVE']);
        $this->actingAs($u2); Employee::create(['employee_no'=>'B-1','name'=>'B','status'=>'ACTIVE']);
        $this->assertEquals(['B-1'],Employee::pluck('employee_no')->all());
    }
}
