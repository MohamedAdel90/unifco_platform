<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{CandidateInterview,Employee,EmployeeOnboardingTask,EmploymentContract,EmploymentOffer,JobPosition,ManpowerRequisition,RecruitmentCandidate,RecruitmentVacancy,User};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecruitmentController extends Controller
{
    public function index(Request $request): View
    {
        $requisitions=ManpowerRequisition::with(['position','vacancies.candidates'])->latest()->limit(30)->get();
        $vacancies=RecruitmentVacancy::withCount('candidates')->with('requisition')->whereIn('status',['OPEN','ON_HOLD'])->latest()->limit(20)->get();
        $candidates=RecruitmentCandidate::with(['vacancy','offer'])->latest()->limit(40)->get();
        $onboarding=EmployeeOnboardingTask::with('employee')->where('status','PENDING')->orderBy('due_on')->limit(20)->get();
        return view('hr.recruitment.index',[
            'requisitions'=>$requisitions,'vacancies'=>$vacancies,'candidates'=>$candidates,'onboarding'=>$onboarding,
            'positions'=>JobPosition::where('status','ACTIVE')->orderBy('department')->orderBy('title')->get(),
            'stats'=>[
                'open_requisitions'=>ManpowerRequisition::whereIn('status',['PENDING_APPROVAL','APPROVED'])->count(),
                'open_vacancies'=>RecruitmentVacancy::where('status','OPEN')->count(),
                'active_candidates'=>RecruitmentCandidate::whereNotIn('stage',['REJECTED','HIRED','WITHDRAWN'])->count(),
                'offers'=>EmploymentOffer::whereIn('status',['DRAFT','ISSUED','ACCEPTED'])->count(),
                'pending_onboarding'=>EmployeeOnboardingTask::where('status','PENDING')->count(),
            ],
        ]);
    }

    public function storeRequisition(Request $request,AuditService $audit): RedirectResponse
    {
        $d=$request->validate([
            'title'=>['required','string','max:160'],'job_position_id'=>['nullable','integer'],'department'=>['nullable','string','max:120'],
            'headcount'=>['required','integer','min:1','max:999'],'employment_type'=>['required',Rule::in(['FULL_TIME','PART_TIME','TEMPORARY','CONTRACTOR'])],
            'work_location'=>['nullable','string','max:160'],'budget_min'=>['nullable','numeric','min:0'],'budget_max'=>['nullable','numeric','gte:budget_min'],
            'needed_by'=>['nullable','date'],'justification'=>['nullable','string','max:3000'],
        ]);
        if(!empty($d['job_position_id'])) JobPosition::findOrFail($d['job_position_id']);
        $tenant=$request->user()->tenant_id;
        $next=(int)ManpowerRequisition::withoutGlobalScopes()->where('tenant_id',$tenant)->max('id')+1;
        $req=ManpowerRequisition::create([...$d,'organization_id'=>$request->user()->organization_id,'requisition_no'=>'MPR-'.now()->format('Y').'-'.str_pad((string)$next,5,'0',STR_PAD_LEFT),'status'=>'PENDING_APPROVAL','requested_by'=>$request->user()->id]);
        $audit->record('hr.recruitment.requisition.created',$req,[],$req->toArray());
        return back()->with('status','Manpower requisition submitted for approval.');
    }

    public function decideRequisition(Request $request,ManpowerRequisition $requisition,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['decision'=>['required',Rule::in(['APPROVED','REJECTED'])]]);
        if($requisition->status!=='PENDING_APPROVAL') throw ValidationException::withMessages(['requisition'=>'Only pending requisitions can be decided.']);
        if((int)$requisition->requested_by===(int)$request->user()->id) throw ValidationException::withMessages(['requisition'=>'Segregation of duties: requester cannot approve their own manpower requisition.']);
        $before=$requisition->toArray();
        $requisition->update(['status'=>$d['decision'],'approved_by'=>$request->user()->id,'approved_at'=>now()]);
        $audit->record('hr.recruitment.requisition.'.strtolower($d['decision']),$requisition,$before,$requisition->fresh()->toArray());
        return back()->with('status','Requisition decision recorded.');
    }

    public function openVacancy(Request $request,ManpowerRequisition $requisition,AuditService $audit): RedirectResponse
    {
        if($requisition->status!=='APPROVED') throw ValidationException::withMessages(['requisition'=>'Only approved requisitions can be converted to vacancies.']);
        $d=$request->validate(['title'=>['nullable','string','max:160'],'description'=>['nullable','string','max:5000'],'opens_on'=>['nullable','date'],'closes_on'=>['nullable','date','after_or_equal:opens_on']]);
        $tenant=$request->user()->tenant_id;
        $next=(int)RecruitmentVacancy::withoutGlobalScopes()->where('tenant_id',$tenant)->max('id')+1;
        $vacancy=RecruitmentVacancy::create([...$d,'organization_id'=>$requisition->organization_id,'manpower_requisition_id'=>$requisition->id,'vacancy_no'=>'VAC-'.now()->format('Y').'-'.str_pad((string)$next,5,'0',STR_PAD_LEFT),'title'=>$d['title']??$requisition->title,'opens_on'=>$d['opens_on']??today(),'status'=>'OPEN']);
        $audit->record('hr.recruitment.vacancy.opened',$vacancy,[],$vacancy->toArray());
        return back()->with('status','Vacancy opened.');
    }

    public function storeCandidate(Request $request,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['recruitment_vacancy_id'=>['required','integer'],'name'=>['required','string','max:160'],'email'=>['nullable','email','max:255'],'mobile'=>['nullable','string','max:40'],'nationality'=>['nullable','string','max:80'],'source'=>['nullable','string','max:60'],'expected_salary'=>['nullable','numeric','min:0'],'notes'=>['nullable','string','max:3000']]);
        $vacancy=RecruitmentVacancy::findOrFail($d['recruitment_vacancy_id']);
        if($vacancy->status!=='OPEN') throw ValidationException::withMessages(['recruitment_vacancy_id'=>'Candidate can only be added to an open vacancy.']);
        $tenant=$request->user()->tenant_id;
        $next=(int)RecruitmentCandidate::withoutGlobalScopes()->where('tenant_id',$tenant)->max('id')+1;
        $candidate=RecruitmentCandidate::create([...$d,'organization_id'=>$vacancy->organization_id,'candidate_no'=>'CAN-'.now()->format('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),'stage'=>'APPLIED']);
        $audit->record('hr.recruitment.candidate.created',$candidate,[],$candidate->toArray());
        return redirect()->route('hr.recruitment.candidates.show',$candidate);
    }

    public function showCandidate(RecruitmentCandidate $candidate): View
    {
        $candidate->load(['vacancy.requisition.position','interviews.interviewer','offer','employee']);
        $tasks=$candidate->employee_id?EmployeeOnboardingTask::where('employee_id',$candidate->employee_id)->orderBy('category')->orderBy('due_on')->get():collect();
        return view('hr.recruitment.candidate',compact('candidate','tasks'));
    }

    public function updateStage(Request $request,RecruitmentCandidate $candidate,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['stage'=>['required',Rule::in(['APPLIED','SCREENING','SHORTLISTED','INTERVIEW','OFFER','REJECTED','WITHDRAWN'])]]);
        if($candidate->stage==='HIRED') throw ValidationException::withMessages(['stage'=>'A hired candidate cannot be moved back into recruitment.']);
        $before=$candidate->toArray(); $candidate->update(['stage'=>$d['stage']]);
        $audit->record('hr.recruitment.candidate.stage_changed',$candidate,$before,$candidate->fresh()->toArray());
        return back()->with('status','Candidate stage updated.');
    }

    public function scheduleInterview(Request $request,RecruitmentCandidate $candidate,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['interview_type'=>['required',Rule::in(['HR','TECHNICAL','MANAGER','FINAL'])],'scheduled_at'=>['required','date'],'interviewer_user_id'=>['nullable','integer']]);
        if(!empty($d['interviewer_user_id'])) User::where('tenant_id',$request->user()->tenant_id)->findOrFail($d['interviewer_user_id']);
        $interview=CandidateInterview::create([...$d,'recruitment_candidate_id'=>$candidate->id,'decision'=>'PENDING']);
        $candidate->update(['stage'=>'INTERVIEW']);
        $audit->record('hr.recruitment.interview.scheduled',$interview,[],$interview->toArray());
        return back()->with('status','Interview scheduled.');
    }

    public function decideInterview(Request $request,RecruitmentCandidate $candidate,CandidateInterview $interview,AuditService $audit): RedirectResponse
    {
        abort_unless((int)$interview->recruitment_candidate_id===(int)$candidate->id,404);
        $d=$request->validate(['decision'=>['required',Rule::in(['PASS','HOLD','FAIL'])],'score'=>['nullable','numeric','min:0','max:100'],'feedback'=>['nullable','string','max:3000']]);
        $before=$interview->toArray(); $interview->update($d);
        if($d['decision']==='PASS') $candidate->update(['stage'=>'SHORTLISTED']);
        if($d['decision']==='FAIL') $candidate->update(['stage'=>'REJECTED']);
        $audit->record('hr.recruitment.interview.decided',$interview,$before,$interview->fresh()->toArray());
        return back()->with('status','Interview result recorded.');
    }

    public function createOffer(Request $request,RecruitmentCandidate $candidate,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['basic_salary'=>['required','numeric','min:0'],'housing_allowance'=>['nullable','numeric','min:0'],'transport_allowance'=>['nullable','numeric','min:0'],'other_allowances'=>['nullable','numeric','min:0'],'proposed_start_date'=>['required','date'],'probation_days'=>['required','integer','min:0','max:180']]);
        if(in_array($candidate->stage,['REJECTED','WITHDRAWN','HIRED'])) throw ValidationException::withMessages(['candidate'=>'This candidate cannot receive an offer in the current stage.']);
        $tenant=$request->user()->tenant_id;
        $next=(int)EmploymentOffer::withoutGlobalScopes()->where('tenant_id',$tenant)->max('id')+1;
        $offer=EmploymentOffer::updateOrCreate(['recruitment_candidate_id'=>$candidate->id],[...$d,'organization_id'=>$candidate->organization_id,'offer_no'=>'OFF-'.now()->format('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),'currency'=>'SAR','status'=>'ISSUED','created_by'=>$request->user()->id]);
        $candidate->update(['stage'=>'OFFER']);
        $audit->record('hr.recruitment.offer.issued',$offer,[],$offer->toArray());
        return back()->with('status','Employment offer issued.');
    }

    public function decideOffer(Request $request,RecruitmentCandidate $candidate,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['decision'=>['required',Rule::in(['ACCEPTED','DECLINED'])]]);
        $offer=$candidate->offer()->firstOrFail();
        if(!in_array($offer->status,['ISSUED','DRAFT'])) throw ValidationException::withMessages(['offer'=>'Offer has already been decided.']);
        $before=$offer->toArray(); $offer->update(['status'=>$d['decision'],'accepted_at'=>$d['decision']==='ACCEPTED'?now():null]);
        $candidate->update(['stage'=>$d['decision']==='ACCEPTED'?'OFFER':'WITHDRAWN']);
        $audit->record('hr.recruitment.offer.'.strtolower($d['decision']),$offer,$before,$offer->fresh()->toArray());
        return back()->with('status','Offer decision recorded.');
    }

    public function hire(Request $request,RecruitmentCandidate $candidate,AuditService $audit): RedirectResponse
    {
        $offer=$candidate->offer()->where('status','ACCEPTED')->first();
        if(!$offer) throw ValidationException::withMessages(['offer'=>'An accepted offer is required before hiring.']);
        if($candidate->employee_id) return redirect()->route('hr.employees.show',$candidate->employee_id);
        $vacancy=$candidate->vacancy()->with('requisition.position')->firstOrFail();
        $tenant=$request->user()->tenant_id;
        $employee=DB::transaction(function() use($request,$candidate,$offer,$vacancy,$tenant){
            $next=(int)Employee::withoutGlobalScopes()->where('tenant_id',$tenant)->max('id')+1;
            $employee=Employee::create([
                'organization_id'=>$candidate->organization_id,'job_position_id'=>$vacancy->requisition->job_position_id,'employee_no'=>'EMP-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),
                'name'=>$candidate->name,'email'=>$candidate->email,'mobile'=>$candidate->mobile,'nationality'=>$candidate->nationality,'hire_date'=>$offer->proposed_start_date,'status'=>'ACTIVE',
                'employment_type'=>$vacancy->requisition->employment_type,'basic_salary'=>$offer->basic_salary,'housing_allowance'=>$offer->housing_allowance,'transport_allowance'=>$offer->transport_allowance,'other_allowances'=>$offer->other_allowances,
                'probation_end_date'=>$offer->probation_days?(clone $offer->proposed_start_date)->addDays($offer->probation_days):null,'work_location'=>$vacancy->requisition->work_location,'country'=>'SA',
            ]);
            EmploymentContract::create(['tenant_id'=>$tenant,'employee_id'=>$employee->id,'contract_no'=>'CTR-'.$employee->employee_no,'contract_type'=>'INDEFINITE','starts_on'=>$offer->proposed_start_date,'probation_ends_on'=>$employee->probation_end_date,'basic_salary'=>$offer->basic_salary,'housing_allowance'=>$offer->housing_allowance,'transport_allowance'=>$offer->transport_allowance,'other_allowances'=>$offer->other_allowances,'currency'=>'SAR','status'=>'ACTIVE','created_by'=>$request->user()->id]);
            foreach([
                ['DOCUMENTS','Verify identity / Iqama / passport'],['PAYROLL','Capture bank and IBAN details'],['GOSI','Complete GOSI registration check'],['IT','Provision system access and equipment'],['HR','Sign employment contract and policies'],['MANAGER','First-day orientation and objectives'],['PROBATION','Schedule probation review'],
            ] as [$category,$name]) EmployeeOnboardingTask::create(['tenant_id'=>$tenant,'employee_id'=>$employee->id,'category'=>$category,'task_name'=>$name,'due_on'=>$offer->proposed_start_date,'status'=>'PENDING']);
            $candidate->update(['employee_id'=>$employee->id,'stage'=>'HIRED']);
            $vacancy->update(['status'=>'FILLED']);
            return $employee;
        });
        $audit->record('hr.recruitment.candidate.hired',$candidate,[],['employee_id'=>$employee->id,'employee_no'=>$employee->employee_no]);
        return redirect()->route('hr.employees.show',$employee)->with('status','Candidate hired and onboarding checklist created.');
    }

    public function completeOnboardingTask(Request $request,EmployeeOnboardingTask $task,AuditService $audit): RedirectResponse
    {
        if($task->status==='COMPLETED') return back();
        $before=$task->toArray(); $task->update(['status'=>'COMPLETED','completed_at'=>now()]);
        $audit->record('hr.onboarding.task.completed',$task,$before,$task->fresh()->toArray());
        return back()->with('status','Onboarding task completed.');
    }
}
