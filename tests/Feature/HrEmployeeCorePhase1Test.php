<?php

namespace Tests\Feature;

use App\Models\{Employee,JobPosition,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_new_employee_route_renders_structured_registration_form(): void
    {
        [,,$admin,$position]=$this->admin();
        $this->actingAs($admin)->get('/hr/employees/create')
            ->assertOk()
            ->assertSee('Create Employee',false)
            ->assertSee('UN-00001',false)
            ->assertSee('First Name',false)
            ->assertSee('Middle Name',false)
            ->assertSee('Last Name',false)
            ->assertSee('Official Employee Email',false)
            ->assertSee('Manager Email',false)
            ->assertSee('Address in Birth Country',false)
            ->assertSee('IBAN Attachment',false)
            ->assertSee($position->title,false);
    }

    public function test_employee_master_generates_sequence_and_stores_structured_profile_and_documents(): void
    {
        Storage::fake('local');
        [$tenant,$org,$admin,$position]=$this->admin();
        $manager=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'UN-00001','name'=>'Line Manager','first_name'=>'Line','last_name'=>'Manager','official_email'=>'manager@unifco.com','status'=>'ACTIVE']);
        $response=$this->actingAs($admin)->post('/hr/employees',[
            'first_name'=>'Ahmed','middle_name'=>'Mohammed','last_name'=>'Ali','email'=>'ahmed.personal@example.test','official_email'=>'ahmed.ali@unifco.com',
            'mobile_country_code'=>'+966','mobile'=>'501234567','hire_date'=>'2026-08-29','status'=>'ACTIVE','job_position_id'=>$position->id,'manager_employee_id'=>$manager->id,
            'nationality'=>'Saudi Arabian','gender'=>'MALE','date_of_birth'=>'1990-01-01','marital_status'=>'MARRIED','national_id'=>'1000000000','gosi_no'=>'GOSI-200',
            'emergency_contact_name'=>'Mohammed Ali','emergency_mobile_country_code'=>'+966','emergency_contact_mobile'=>'509999999','address_line'=>'Riyadh Birth Address','city'=>'Riyadh',
            'employment_type'=>'FULL_TIME','contract_type'=>'INDEFINITE','basic_salary'=>10000,'housing_allowance'=>2500,'transport_allowance'=>1000,'other_allowances'=>500,
            'bank_name'=>'Test Bank','iban'=>'SA0000000000000000000000','work_location'=>'Riyadh HQ',
            'national_id_document'=>UploadedFile::fake()->image('national-id.jpg'),
            'contract_document'=>UploadedFile::fake()->create('contract.pdf',100,'application/pdf'),
            'iban_document'=>UploadedFile::fake()->create('iban.pdf',50,'application/pdf'),
        ]);
        $employee=Employee::where('official_email','ahmed.ali@unifco.com')->firstOrFail();
        $response->assertRedirect(route('hr.employees.show',$employee));
        $this->assertSame('UN-00002',$employee->employee_no);
        $this->assertSame('Ahmed Mohammed Ali',$employee->name);
        $this->assertDatabaseHas('employees',['id'=>$employee->id,'first_name'=>'Ahmed','middle_name'=>'Mohammed','last_name'=>'Ali','official_email'=>'ahmed.ali@unifco.com','mobile_country_code'=>'+966','emergency_mobile_country_code'=>'+966']);
        $this->assertDatabaseHas('employee_documents',['employee_id'=>$employee->id,'document_type'=>'NATIONAL_ID','document_no'=>'1000000000']);
        $this->assertDatabaseHas('employee_documents',['employee_id'=>$employee->id,'document_type'=>'EMPLOYMENT_CONTRACT']);
        $this->assertDatabaseHas('employee_documents',['employee_id'=>$employee->id,'document_type'=>'IBAN_CERTIFICATE','document_no'=>'SA0000000000000000000000']);
        foreach($employee->documents as $document) Storage::disk('local')->assertExists($document->file_path);
        $this->actingAs($admin)->get('/hr/employees/'.$employee->id)->assertOk()->assertSee('Employee 360',false)->assertSee('14,000.00 SAR',false);
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
