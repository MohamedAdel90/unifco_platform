<?php

namespace Tests\Feature;

use App\Models\{Employee,HrServiceRequest,Organization,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrDocumentsPhase9Test extends TestCase
{
    use RefreshDatabase;

    private function core(): array
    {
        $tenant=Tenant::create(['name'=>'Docs Tenant','code'=>'DOCS','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'DOC-HQ','status'=>'ACTIVE']);
        $hrEmployee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'HR-001','name'=>'HR Approver','status'=>'ACTIVE']);
        $employee=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'EMP-001','name'=>'Employee One','status'=>'ACTIVE','email'=>'old@example.test','mobile'=>'0500000000','hire_date'=>'2022-01-01','basic_salary'=>8000,'housing_allowance'=>2000]);
        $outsider=Employee::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_no'=>'EMP-999','name'=>'Other Employee','status'=>'ACTIVE','basic_salary'=>30000]);
        $hr=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_id'=>$hrEmployee->id,'name'=>'HR Admin','email'=>'hr-docs@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $user=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_id'=>$employee->id,'name'=>'Employee User','email'=>'employee-docs@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $other=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'employee_id'=>$outsider->id,'name'=>'Other User','email'=>'other-docs@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        return compact('tenant','org','hrEmployee','employee','outsider','hr','user','other');
    }

    public function test_employee_can_submit_own_salary_certificate_request(): void
    {
        $c=$this->core();
        $this->actingAs($c['user'])->post('/hr/self-service/documents',['request_type'=>'SALARY_CERTIFICATE','language'=>'EN','recipient_name'=>'Bank','purpose'=>'Account opening'])->assertRedirect();
        $r=HrServiceRequest::firstOrFail();
        $this->assertSame($c['employee']->id,$r->employee_id);
        $this->assertSame('PENDING',$r->status);
        $this->assertSame($c['user']->id,$r->requested_by);
    }

    public function test_profile_update_is_applied_only_after_independent_approval(): void
    {
        $c=$this->core();
        $this->actingAs($c['user'])->post('/hr/self-service/documents',['request_type'=>'PROFILE_UPDATE','language'=>'EN','mobile'=>'0555555555','email'=>'new@example.test'])->assertRedirect();
        $r=HrServiceRequest::firstOrFail();
        $this->assertSame('0500000000',$c['employee']->fresh()->mobile);
        $this->actingAs($c['hr'])->post('/hr/documents/'.$r->id.'/decide',['decision'=>'APPROVED'])->assertRedirect();
        $this->assertSame('0555555555',$c['employee']->fresh()->mobile);
        $this->assertSame('new@example.test',$c['employee']->fresh()->email);
    }

    public function test_requester_cannot_approve_own_request(): void
    {
        $c=$this->core();
        $r=HrServiceRequest::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'request_no'=>'HRR-TEST-1','employee_id'=>$c['hrEmployee']->id,'request_type'=>'GENERAL','language'=>'EN','status'=>'PENDING','requested_by'=>$c['hr']->id]);
        $this->actingAs($c['hr'])->post('/hr/documents/'.$r->id.'/decide',['decision'=>'APPROVED'])->assertSessionHasErrors('request');
        $this->assertSame('PENDING',$r->fresh()->status);
    }

    public function test_approved_letter_can_be_issued_as_immutable_snapshot_and_opened_by_owner(): void
    {
        $c=$this->core();
        $this->actingAs($c['user'])->post('/hr/self-service/documents',['request_type'=>'SALARY_CERTIFICATE','language'=>'AR','recipient_name'=>'بنك الاختبار','purpose'=>'فتح حساب'])->assertRedirect();
        $r=HrServiceRequest::firstOrFail();
        $this->actingAs($c['hr'])->get('/hr/documents')->assertOk();
        $this->actingAs($c['hr'])->post('/hr/documents/'.$r->id.'/decide',['decision'=>'APPROVED'])->assertRedirect();
        $this->actingAs($c['hr'])->post('/hr/documents/'.$r->id.'/issue')->assertRedirect();
        $r->refresh();
        $this->assertSame('ISSUED',$r->status);
        $this->assertNotNull($r->document_no);
        $this->assertNotNull($r->verification_token);
        $this->assertStringContainsString('Employee One',$r->snapshot['body']);
        $this->actingAs($c['user'])->get('/hr/documents/'.$r->id)->assertOk()->assertSee($r->document_no,false);
        $this->actingAs($c['user'])->get('/hr/documents/'.$r->id.'/print')->assertOk()->assertSee('Print / Save as PDF',false);
        $this->actingAs($c['other'])->get('/hr/documents/'.$r->id)->assertForbidden();
    }

    public function test_hr_reader_does_not_require_an_employee_link_to_open_requests(): void
    {
        $c=$this->core();
        $unlinkedAdmin=User::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'name'=>'Unlinked HR Admin','email'=>'unlinked-hr-docs@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $r=HrServiceRequest::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'request_no'=>'HRR-TEST-2','employee_id'=>$c['employee']->id,'request_type'=>'GENERAL','language'=>'EN','status'=>'PENDING','requested_by'=>$c['user']->id]);
        $this->actingAs($unlinkedAdmin)->get('/hr/documents/'.$r->id)->assertOk()->assertSee('HRR-TEST-2',false);
    }

    public function test_unlinked_user_cannot_open_employee_document_self_service(): void
    {
        $c=$this->core();
        $unlinked=User::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'name'=>'No Employee','email'=>'no-emp-docs@example.test','password'=>'password','role'=>'TECHNICIAN','status'=>'ACTIVE']);
        $this->actingAs($unlinked)->get('/hr/self-service/documents')->assertForbidden();
    }

    public function test_phase9_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.self-service.documents.index'));
        $this->assertTrue(\Route::has('hr.documents.index'));
        $this->assertTrue(\Route::has('hr.documents.issue'));
        $this->assertTrue(\Route::has('hr.documents.print'));
    }
}
