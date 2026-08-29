<?php

namespace Tests\Feature;

use App\Models\{Employee,JobPosition,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrEmployeeCorePhase1Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): array
    {
        $tenant=Tenant::create(['name'=>'HR Tenant','code'=>'HRT','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'UNIFCO HQ','code'=>'HRT-HQ','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'HR Admin','email'=>'hr-admin@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $position=JobPosition::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>'OPS-01','title'=>'Operations Supervisor','department'=>'Operations','status'=>'ACTIVE']);
        return [$tenant,$org,$admin,$position];
    }

    public function test_hr_dashboard_and_employee_directory_render_new_ui(): void
    {
        [$tenant,$org,$admin,$position]=$this->admin();
        Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'job_position_id'=>$position->id,'employee_no'=>'EMP-100','name'=>'Alice Johnson','email'=>'alice@example.test','hire_date'=>'2021-01-10','status'=>'ACTIVE','basic_salary'=>9000,'housing_allowance'=>2250,'transport_allowance'=>800]);
        $this->actingAs($admin)->get('/hr/dashboard')->assertOk()->assertSee('HR Command Center',false)->assertSee('Monthly Compensation',false)->assertSee('/hr/attendance',false)->assertSee('/hr/leave',false)->assertSee('/hr/payroll',false)->assertSee('/hr/missions',false);
        $this->actingAs($admin)->get('/hr/employees')->assertOk()->assertSee('Employee Directory',false)->assertSee('Employee 360',false)->assertSee('12,050.00 SAR',false);
    }

    public function test_new_employee_route_renders_create_form_instead_of_dynamic_employee_404(): void
    {
        [,,$admin,$position]=$this->admin();
        $this->actingAs($admin)->get('/hr/employees/create')
            ->assertOk()
            ->assertSee('Create Employee',false)
            ->assertSee('Personal & Contact',false)
            ->assertSee($position->title,false)
            ->assertSee('Create Employee',false);
    }

    public function test_employee_master_can_store_saudi_hr_fields_and_render_employee_360(): void
    {
        [$tenant,$org,$admin,$position]=$this->admin();
        $response=$this->actingAs($admin)->post('/hr/employees',[
            'employee_no'=>'EMP-200','name'=>'Saudi Core Employee','email'=>'core@example.test','mobile'=>'0500000000','hire_date'=>'2024-01-01','status'=>'ACTIVE',
            'job_position_id'=>$position->id,'nationality'=>'Saudi','gender'=>'MALE','date_of_birth'=>'1990-01-01','marital_status'=>'MARRIED','national_id'=>'1000000000','gosi_no'=>'GOSI-200',
            'employment_type'=>'FULL_TIME','contract_type'=>'INDEFINITE','basic_salary'=>10000,'housing_allowance'=>2500,'transport_allowance'=>1000,'other_allowances'=>500,
            'bank_name'=>'Test Bank','iban'=>'SA0000000000000000000000','city'=>'Riyadh','country'=>'SA','work_location'=>'Riyadh HQ',
        ]);
        $employee=Employee::where('employee_no','EMP-200')->firstOrFail();
        $response->assertRedirect(route('hr.employees.show',$employee));
        $this->assertDatabaseHas('employees',['id'=>$employee->id,'tenant_id'=>$tenant->id,'national_id'=>'1000000000','contract_type'=>'INDEFINITE','basic_salary'=>10000]);
        $this->actingAs($admin)->get('/hr/employees/'.$employee->id)->assertOk()->assertSee('Employee 360',false)->assertSee('14,000.00 SAR',false)->assertSee('End of Service Readiness',false)->assertSee('Policy engine pending',false);
    }

    public function test_employee_360_accepts_contract_and_document_snapshots(): void
    {
        [$tenant,$org,$admin,$position]=$this->admin();
        $employee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'job_position_id'=>$position->id,'employee_no'=>'EMP-300','name'=>'Contract Employee','hire_date'=>'2025-01-01','status'=>'ACTIVE']);
        $this->actingAs($admin)->post('/hr/employees/'.$employee->id.'/contracts',[
            'contract_no'=>'CTR-300','contract_type'=>'FIXED_TERM','starts_on'=>'2025-01-01','ends_on'=>'2026-12-31','basic_salary'=>8000,'housing_allowance'=>2000,'transport_allowance'=>500,'other_allowances'=>0,'status'=>'ACTIVE',
        ])->assertRedirect();
        $this->actingAs($admin)->post('/hr/employees/'.$employee->id.'/documents',[
            'document_type'=>'IQAMA','document_no'=>'IQ-300','issued_on'=>'2025-01-01','expires_on'=>'2026-10-01','status'=>'VALID',
        ])->assertRedirect();
        $this->assertDatabaseHas('employment_contracts',['tenant_id'=>$tenant->id,'employee_id'=>$employee->id,'contract_no'=>'CTR-300','currency'=>'SAR']);
        $this->assertDatabaseHas('employee_documents',['tenant_id'=>$tenant->id,'employee_id'=>$employee->id,'document_type'=>'IQAMA','document_no'=>'IQ-300']);
        $this->actingAs($admin)->get('/hr/employees/'.$employee->id)->assertOk()->assertSee('CTR-300',false)->assertSee('IQ-300',false);
    }

    public function test_hr_phase1_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.dashboard'));
        $this->assertTrue(\Route::has('hr.employees.index'));
        $this->assertTrue(\Route::has('hr.employees.create'));
        $this->assertTrue(\Route::has('hr.employees.store'));
        $this->assertTrue(\Route::has('hr.employees.show'));
        $this->assertTrue(\Route::has('hr.employees.edit'));
        $this->assertTrue(\Route::has('hr.employees.update'));
        $this->assertTrue(\Route::has('hr.employees.activate'));
        $this->assertTrue(\Route::has('hr.employees.deactivate'));
        $this->assertTrue(\Route::has('hr.employees.contracts.store'));
        $this->assertTrue(\Route::has('hr.employees.documents.store'));
    }
}
