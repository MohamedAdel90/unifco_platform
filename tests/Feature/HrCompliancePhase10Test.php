<?php

namespace Tests\Feature;

use App\Models\{Employee,EmploymentContract,HrComplianceCase,HrComplianceProfile,HrComplianceScanRun,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrCompliancePhase10Test extends TestCase
{
    use RefreshDatabase;

    private function core(): array
    {
        $tenant=Tenant::create(['name'=>'Compliance Tenant','code'=>'CMP','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'Saudi HQ','code'=>'CMP-HQ','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'HR Compliance Admin','email'=>'compliance@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $saudi=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'SA-001','name'=>'Saudi Employee','nationality'=>'Saudi','national_id'=>'1010101010','status'=>'ACTIVE','hire_date'=>'2024-01-01','basic_salary'=>8000,'housing_allowance'=>2000,'iban'=>'SA0001']);
        $expat=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'EX-001','name'=>'Expat Employee','nationality'=>'Indian','status'=>'ACTIVE','hire_date'=>'2024-02-01','basic_salary'=>6000,'housing_allowance'=>1500,'iqama_no'=>'2000000000','iqama_expiry'=>now()->addDays(20),'passport_no'=>'P123','passport_expiry'=>now()->addDays(60)]);
        $contract=EmploymentContract::create(['tenant_id'=>$tenant->id,'employee_id'=>$saudi->id,'contract_no'=>'CTR-001','contract_type'=>'UNLIMITED','starts_on'=>'2024-01-01','basic_salary'=>8000,'housing_allowance'=>2000,'transport_allowance'=>0,'other_allowances'=>0,'currency'=>'SAR','status'=>'ACTIVE','created_by'=>$admin->id]);
        return compact('tenant','org','admin','saudi','expat','contract');
    }

    public function test_compliance_dashboard_is_available_to_hr_admin(): void
    {
        $c=$this->core();
        $this->actingAs($c['admin'])->get('/hr/compliance')->assertOk()->assertSee('Saudi HR Compliance Command Center',false)->assertSee('Nitaqat',false)->assertSee('Qiwa',false);
    }

    public function test_scan_creates_actionable_readiness_cases_without_inventing_official_nitaqat_band(): void
    {
        $c=$this->core();
        $this->actingAs($c['admin'])->post('/hr/compliance/scan')->assertRedirect();
        $this->assertGreaterThan(0,HrComplianceCase::whereIn('status',['OPEN','IN_PROGRESS'])->count());
        $this->assertDatabaseHas('hr_compliance_cases',['category'=>'IQAMA','severity'=>'CRITICAL','employee_id'=>$c['expat']->id]);
        $this->assertDatabaseHas('hr_compliance_cases',['category'=>'QIWA','source_key'=>'qiwa:'.$c['contract']->id]);
        $this->assertDatabaseHas('hr_compliance_cases',['category'=>'NITAQAT','source_key'=>'nitaqat-review']);
        $this->assertSame('NOT_REVIEWED',HrComplianceProfile::firstOrFail()->nitaqat_reported_band);
        $this->assertNotNull(HrComplianceScanRun::first());
    }

    public function test_qiwa_and_gosi_evidence_can_be_recorded_then_scan_clears_source_findings(): void
    {
        $c=$this->core();
        $this->actingAs($c['admin'])->post('/hr/compliance/scan')->assertRedirect();
        $this->actingAs($c['admin'])->post('/hr/compliance/contracts/'.$c['contract']->id.'/qiwa',['qiwa_status'=>'DOCUMENTED','qiwa_contract_ref'=>'QIWA-REF-1','qiwa_documented_on'=>today()->toDateString()])->assertRedirect();
        $this->actingAs($c['admin'])->post('/hr/compliance/employees/'.$c['saudi']->id.'/gosi',['gosi_status'=>'REGISTERED','gosi_no'=>'GOSI-001','gosi_registered_on'=>today()->toDateString()])->assertRedirect();
        $this->actingAs($c['admin'])->post('/hr/compliance/profile',['economic_activity'=>'Facilities Management','qiwa_contract_target_pct'=>90,'nitaqat_reported_band'=>'HIGH_GREEN','wps_status'=>'COMPLIANT','last_wps_period'=>today()->startOfMonth()->toDateString(),'mudad_reference'=>'MUDAD-1','last_gosi_reconciliation_on'=>today()->toDateString(),'last_qiwa_reconciliation_on'=>today()->toDateString(),'last_nitaqat_review_on'=>today()->toDateString()])->assertRedirect();
        $this->actingAs($c['admin'])->post('/hr/compliance/scan')->assertRedirect();
        $this->assertSame('DOCUMENTED',$c['contract']->fresh()->qiwa_status);
        $this->assertSame('REGISTERED',$c['saudi']->fresh()->gosi_status);
        $this->assertDatabaseHas('hr_compliance_cases',['source_key'=>'qiwa:'.$c['contract']->id,'status'=>'RESOLVED']);
    }

    public function test_phase10_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.compliance.index'));
        $this->assertTrue(\Route::has('hr.compliance.scan'));
        $this->assertTrue(\Route::has('hr.compliance.employees.gosi'));
        $this->assertTrue(\Route::has('hr.compliance.contracts.qiwa'));
    }
}
