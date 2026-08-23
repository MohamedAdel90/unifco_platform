<?php

namespace Tests\Feature;

use App\Models\{Employee,EmployeeOnboardingTask,EmploymentOffer,JobPosition,ManpowerRequisition,Organization,RecruitmentCandidate,RecruitmentVacancy,Tenant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrRecruitmentPhase6Test extends TestCase
{
    use RefreshDatabase;

    private function core(string $code='REC'): array
    {
        $tenant=Tenant::create(['name'=>$code.' Tenant','code'=>$code,'status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>$code.'-HQ','status'=>'ACTIVE']);
        $requester=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'HR Recruiter','email'=>strtolower($code).'-rec@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $approver=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'HR Approver','email'=>strtolower($code).'-app@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $position=JobPosition::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>$code.'-ENG','title'=>'Field Engineer','department'=>'Operations','status'=>'ACTIVE']);
        return [$tenant,$org,$requester,$approver,$position];
    }

    public function test_requisition_requires_independent_approval_then_opens_vacancy(): void
    {
        [$tenant,$org,$requester,$approver,$position]=$this->core();
        $this->actingAs($requester)->post('/hr/recruitment/requisitions',['title'=>'Field Engineer','job_position_id'=>$position->id,'department'=>'Operations','headcount'=>2,'employment_type'=>'FULL_TIME','work_location'=>'Riyadh','budget_min'=>8000,'budget_max'=>12000])->assertRedirect();
        $req=ManpowerRequisition::firstOrFail();
        $this->assertSame('PENDING_APPROVAL',$req->status);
        $this->actingAs($requester)->post('/hr/recruitment/requisitions/'.$req->id.'/decide',['decision'=>'APPROVED'])->assertSessionHasErrors('requisition');
        $this->actingAs($approver)->post('/hr/recruitment/requisitions/'.$req->id.'/decide',['decision'=>'APPROVED'])->assertRedirect();
        $this->actingAs($requester)->post('/hr/recruitment/requisitions/'.$req->id.'/vacancies')->assertRedirect();
        $vacancy=RecruitmentVacancy::firstOrFail();
        $this->assertSame('OPEN',$vacancy->status);
        $this->actingAs($requester)->get('/hr/recruitment')->assertOk()->assertSee('Recruitment Command Center',false)->assertSee($vacancy->vacancy_no,false);
    }

    public function test_candidate_interview_offer_hire_creates_employee_contract_and_onboarding(): void
    {
        [$tenant,$org,$requester,$approver,$position]=$this->core('HIRE');
        $req=ManpowerRequisition::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'job_position_id'=>$position->id,'requisition_no'=>'MPR-HIRE','title'=>'Field Engineer','headcount'=>1,'employment_type'=>'FULL_TIME','work_location'=>'Dammam','status'=>'APPROVED','requested_by'=>$requester->id,'approved_by'=>$approver->id]);
        $vacancy=RecruitmentVacancy::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'manpower_requisition_id'=>$req->id,'vacancy_no'=>'VAC-HIRE','title'=>'Field Engineer','status'=>'OPEN']);
        $this->actingAs($requester)->post('/hr/recruitment/candidates',['recruitment_vacancy_id'=>$vacancy->id,'name'=>'New Hire','email'=>'newhire@example.test','mobile'=>'0500000001','nationality'=>'Saudi','source'=>'Referral','expected_salary'=>10000])->assertRedirect();
        $candidate=RecruitmentCandidate::firstOrFail();
        $this->actingAs($requester)->post('/hr/recruitment/candidates/'.$candidate->id.'/interviews',['interview_type'=>'TECHNICAL','scheduled_at'=>'2026-09-01 10:00:00','interviewer_user_id'=>$approver->id])->assertRedirect();
        $interview=$candidate->interviews()->firstOrFail();
        $this->actingAs($approver)->post('/hr/recruitment/candidates/'.$candidate->id.'/interviews/'.$interview->id.'/decide',['decision'=>'PASS','score'=>88,'feedback'=>'Strong technical fit'])->assertRedirect();
        $this->actingAs($requester)->post('/hr/recruitment/candidates/'.$candidate->id.'/offer',['basic_salary'=>9000,'housing_allowance'=>2250,'transport_allowance'=>750,'other_allowances'=>0,'proposed_start_date'=>'2026-09-15','probation_days'=>90])->assertRedirect();
        $offer=EmploymentOffer::firstOrFail(); $this->assertSame('ISSUED',$offer->status);
        $this->actingAs($requester)->post('/hr/recruitment/candidates/'.$candidate->id.'/offer/decide',['decision'=>'ACCEPTED'])->assertRedirect();
        $this->actingAs($requester)->post('/hr/recruitment/candidates/'.$candidate->id.'/hire')->assertRedirect();
        $employee=Employee::where('email','newhire@example.test')->firstOrFail();
        $this->assertSame($position->id,$employee->job_position_id);
        $this->assertSame('HIRED',$candidate->fresh()->stage);
        $this->assertSame('FILLED',$vacancy->fresh()->status);
        $this->assertDatabaseHas('employment_contracts',['employee_id'=>$employee->id,'basic_salary'=>9000]);
        $this->assertSame(7,EmployeeOnboardingTask::where('employee_id',$employee->id)->count());
        $this->actingAs($requester)->get('/hr/recruitment/candidates/'.$candidate->id)->assertOk()->assertSee('Candidate 360',false)->assertSee('Employee Onboarding Checklist',false);
    }

    public function test_recruitment_is_tenant_scoped(): void
    {
        [$tenantA,$orgA,$userA,$approverA,$positionA]=$this->core('RA');
        [$tenantB,$orgB,$userB,$approverB,$positionB]=$this->core('RB');
        $req=ManpowerRequisition::create(['tenant_id'=>$tenantB->id,'organization_id'=>$orgB->id,'requisition_no'=>'RB-REQ','title'=>'Other Tenant','headcount'=>1,'employment_type'=>'FULL_TIME','status'=>'APPROVED']);
        $vacancy=RecruitmentVacancy::create(['tenant_id'=>$tenantB->id,'organization_id'=>$orgB->id,'manpower_requisition_id'=>$req->id,'vacancy_no'=>'RB-VAC','title'=>'Other Vacancy','status'=>'OPEN']);
        $candidate=RecruitmentCandidate::create(['tenant_id'=>$tenantB->id,'organization_id'=>$orgB->id,'recruitment_vacancy_id'=>$vacancy->id,'candidate_no'=>'RB-CAN','name'=>'Other Candidate','stage'=>'APPLIED']);
        $this->actingAs($userA)->get('/hr/recruitment/candidates/'.$candidate->id)->assertNotFound();
    }

    public function test_phase6_routes_are_registered(): void
    {
        $this->assertTrue(\Route::has('hr.recruitment.index'));
        $this->assertTrue(\Route::has('hr.recruitment.requisitions.decide'));
        $this->assertTrue(\Route::has('hr.recruitment.candidates.show'));
        $this->assertTrue(\Route::has('hr.recruitment.hire'));
        $this->assertTrue(\Route::has('hr.recruitment.onboarding.complete'));
    }
}
