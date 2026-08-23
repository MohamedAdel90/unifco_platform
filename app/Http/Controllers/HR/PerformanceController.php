<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{CareerAction,Employee,EmployeeDevelopmentItem,EmployeeGoal,EmployeeSkill,JobPosition,PerformanceCycle,PerformanceReview,ProbationReview};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function index(): View
    {
        $today=Carbon::today();
        $cycles=PerformanceCycle::latest('starts_on')->limit(12)->get();
        $reviews=PerformanceReview::with('employee')->latest('review_date')->limit(20)->get();
        $probationDue=Employee::where('status','ACTIVE')->whereNotNull('probation_end_date')->whereBetween('probation_end_date',[$today,$today->copy()->addDays(30)])->orderBy('probation_end_date')->get();
        $expiringSkills=EmployeeSkill::with('employee')->where('skill_type','CERTIFICATION')->whereNotNull('expires_on')->whereBetween('expires_on',[$today,$today->copy()->addDays(90)])->orderBy('expires_on')->limit(15)->get();
        $career=CareerAction::with(['employee','toPosition'])->whereIn('status',['PENDING_APPROVAL','APPROVED'])->latest()->limit(15)->get();
        return view('hr.performance.index',[
            'cycles'=>$cycles,'reviews'=>$reviews,'probationDue'=>$probationDue,'expiringSkills'=>$expiringSkills,'career'=>$career,
            'employees'=>Employee::where('status','ACTIVE')->orderBy('employee_no')->get(),
            'positions'=>JobPosition::where('status','ACTIVE')->orderBy('department')->orderBy('title')->get(),
            'stats'=>[
                'open_cycles'=>PerformanceCycle::where('status','OPEN')->count(),
                'open_goals'=>EmployeeGoal::whereIn('status',['OPEN','IN_PROGRESS'])->count(),
                'pending_reviews'=>PerformanceReview::where('status','PENDING_APPROVAL')->count(),
                'probation_due'=>$probationDue->count(),
                'expiring_certifications'=>$expiringSkills->count(),
                'career_actions'=>CareerAction::where('status','PENDING_APPROVAL')->count(),
            ],
        ]);
    }

    public function employee(Employee $employee): View
    {
        $employee->load('position');
        $goals=EmployeeGoal::with('cycle')->where('employee_id',$employee->id)->latest()->get();
        $reviews=PerformanceReview::with('cycle')->where('employee_id',$employee->id)->latest('review_date')->get();
        $probation=ProbationReview::where('employee_id',$employee->id)->latest('review_date')->get();
        $development=EmployeeDevelopmentItem::where('employee_id',$employee->id)->orderByRaw("case when status='COMPLETED' then 1 else 0 end")->orderBy('target_date')->get();
        $skills=EmployeeSkill::where('employee_id',$employee->id)->orderBy('skill_type')->orderBy('skill_name')->get();
        $career=CareerAction::with(['fromPosition','toPosition'])->where('employee_id',$employee->id)->latest('effective_on')->get();
        return view('hr.performance.employee',compact('employee','goals','reviews','probation','development','skills','career')+[
            'cycles'=>PerformanceCycle::where('status','OPEN')->orderBy('starts_on')->get(),
            'positions'=>JobPosition::where('status','ACTIVE')->orderBy('department')->orderBy('title')->get(),
        ]);
    }

    public function storeCycle(Request $request,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['code'=>['required','string','max:50'],'name'=>['required','string','max:140'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on']]);
        $cycle=PerformanceCycle::create([...$d,'organization_id'=>$request->user()->organization_id,'status'=>'OPEN']);
        $audit->record('hr.performance.cycle.created',$cycle,[],$cycle->toArray());
        return back()->with('status','Performance cycle created.');
    }

    public function storeGoal(Request $request,Employee $employee,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['performance_cycle_id'=>['nullable','integer'],'title'=>['required','string','max:180'],'description'=>['nullable','string','max:3000'],'weight'=>['required','numeric','min:0','max:100'],'target_value'=>['nullable','numeric'],'actual_value'=>['nullable','numeric'],'unit'=>['nullable','string','max:40'],'due_on'=>['nullable','date']]);
        if(!empty($d['performance_cycle_id'])) PerformanceCycle::findOrFail($d['performance_cycle_id']);
        $goal=EmployeeGoal::create([...$d,'employee_id'=>$employee->id,'status'=>'OPEN','created_by'=>$request->user()->id]);
        $audit->record('hr.performance.goal.created',$goal,[],$goal->toArray());
        return back()->with('status','Employee goal created.');
    }

    public function updateGoal(Request $request,Employee $employee,EmployeeGoal $goal,AuditService $audit): RedirectResponse
    {
        abort_unless((int)$goal->employee_id===(int)$employee->id,404);
        $d=$request->validate(['actual_value'=>['nullable','numeric'],'score'=>['nullable','numeric','min:0','max:100'],'status'=>['required',Rule::in(['OPEN','IN_PROGRESS','COMPLETED','CANCELLED'])]]);
        $before=$goal->toArray(); $goal->update($d);
        $audit->record('hr.performance.goal.updated',$goal,$before,$goal->fresh()->toArray());
        return back()->with('status','Goal progress updated.');
    }

    public function storeReview(Request $request,Employee $employee,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['performance_cycle_id'=>['nullable','integer'],'review_type'=>['required',Rule::in(['QUARTERLY','MID_YEAR','ANNUAL','SPECIAL'])],'review_date'=>['required','date'],'goal_score'=>['required','numeric','min:0','max:100'],'competency_score'=>['required','numeric','min:0','max:100'],'strengths'=>['nullable','string','max:3000'],'development_areas'=>['nullable','string','max:3000'],'manager_comments'=>['nullable','string','max:3000']]);
        if(!empty($d['performance_cycle_id'])) PerformanceCycle::findOrFail($d['performance_cycle_id']);
        $overall=round(((float)$d['goal_score']*.6)+((float)$d['competency_score']*.4),2);
        $rating=$overall>=90?'OUTSTANDING':($overall>=80?'EXCEEDS_EXPECTATIONS':($overall>=70?'MEETS_EXPECTATIONS':($overall>=60?'NEEDS_IMPROVEMENT':'UNSATISFACTORY')));
        $review=PerformanceReview::create([...$d,'employee_id'=>$employee->id,'overall_score'=>$overall,'rating'=>$rating,'status'=>'DRAFT','prepared_by'=>$request->user()->id]);
        $audit->record('hr.performance.review.created',$review,[],$review->toArray());
        return back()->with('status','Performance review saved as draft.');
    }

    public function submitReview(Request $request,PerformanceReview $review,AuditService $audit): RedirectResponse
    {
        if($review->status!=='DRAFT') throw ValidationException::withMessages(['review'=>'Only draft reviews can be submitted.']);
        $before=$review->toArray(); $review->update(['status'=>'PENDING_APPROVAL','submitted_by'=>$request->user()->id,'submitted_at'=>now()]);
        $audit->record('hr.performance.review.submitted',$review,$before,$review->fresh()->toArray());
        return back()->with('status','Review submitted for independent approval.');
    }

    public function decideReview(Request $request,PerformanceReview $review,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['decision'=>['required',Rule::in(['APPROVED','REJECTED'])]]);
        if($review->status!=='PENDING_APPROVAL') throw ValidationException::withMessages(['review'=>'Only pending reviews can be decided.']);
        if((int)$review->prepared_by===(int)$request->user()->id || (int)$review->submitted_by===(int)$request->user()->id) throw ValidationException::withMessages(['review'=>'Segregation of duties: preparer or submitter cannot approve this review.']);
        $before=$review->toArray(); $review->update(['status'=>$d['decision'],'approved_by'=>$request->user()->id,'approved_at'=>now()]);
        $audit->record('hr.performance.review.'.strtolower($d['decision']),$review,$before,$review->fresh()->toArray());
        return back()->with('status','Review decision recorded.');
    }

    public function storeProbation(Request $request,Employee $employee,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['review_date'=>['required','date'],'performance_score'=>['required','numeric','min:0','max:100'],'attendance_score'=>['required','numeric','min:0','max:100'],'conduct_score'=>['required','numeric','min:0','max:100'],'recommendation'=>['required',Rule::in(['CONFIRM','EXTEND','TERMINATE'])],'comments'=>['nullable','string','max:3000']]);
        $overall=round(((float)$d['performance_score']+(float)$d['attendance_score']+(float)$d['conduct_score'])/3,2);
        $review=ProbationReview::create([...$d,'employee_id'=>$employee->id,'probation_end_date'=>$employee->probation_end_date,'overall_score'=>$overall,'reviewed_by'=>$request->user()->id]);
        $audit->record('hr.performance.probation_review.created',$review,[],$review->toArray());
        return back()->with('status','Probation review recorded. Recommendation does not automatically terminate employment.');
    }

    public function storeDevelopment(Request $request,Employee $employee,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['item_type'=>['required',Rule::in(['TRAINING','COACHING','MENTORING','CERTIFICATION','JOB_ROTATION'])],'title'=>['required','string','max:180'],'description'=>['nullable','string','max:3000'],'target_date'=>['nullable','date'],'estimated_cost'=>['nullable','numeric','min:0'],'provider'=>['nullable','string','max:140']]);
        $item=EmployeeDevelopmentItem::create([...$d,'employee_id'=>$employee->id,'status'=>'PLANNED']);
        $audit->record('hr.performance.development.created',$item,[],$item->toArray());
        return back()->with('status','Development item added.');
    }

    public function storeSkill(Request $request,Employee $employee,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['skill_name'=>['required','string','max:140'],'skill_type'=>['required',Rule::in(['SKILL','CERTIFICATION','LICENSE'])],'proficiency'=>['nullable','string','max:30'],'certificate_no'=>['nullable','string','max:100'],'issued_on'=>['nullable','date'],'expires_on'=>['nullable','date','after_or_equal:issued_on'],'issuer'=>['nullable','string','max:140']]);
        $skill=EmployeeSkill::create([...$d,'employee_id'=>$employee->id,'status'=>'VALID']);
        $audit->record('hr.performance.skill.created',$skill,[],$skill->toArray());
        return back()->with('status','Skill / certification added.');
    }

    public function storeCareerAction(Request $request,Employee $employee,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['action_type'=>['required',Rule::in(['PROMOTION','SALARY_REVIEW','TRANSFER'])],'to_job_position_id'=>['nullable','integer'],'new_basic_salary'=>['nullable','numeric','min:0'],'effective_on'=>['required','date'],'reason'=>['nullable','string','max:3000']]);
        if(!empty($d['to_job_position_id'])) JobPosition::findOrFail($d['to_job_position_id']);
        $action=CareerAction::create([...$d,'employee_id'=>$employee->id,'from_job_position_id'=>$employee->job_position_id,'old_basic_salary'=>$employee->basic_salary,'new_basic_salary'=>$d['new_basic_salary']??$employee->basic_salary,'status'=>'PENDING_APPROVAL','requested_by'=>$request->user()->id]);
        $audit->record('hr.performance.career_action.requested',$action,[],$action->toArray());
        return back()->with('status','Career action submitted for approval.');
    }

    public function decideCareerAction(Request $request,CareerAction $action,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['decision'=>['required',Rule::in(['APPROVED','REJECTED'])]]);
        if($action->status!=='PENDING_APPROVAL') throw ValidationException::withMessages(['action'=>'Only pending career actions can be decided.']);
        if((int)$action->requested_by===(int)$request->user()->id) throw ValidationException::withMessages(['action'=>'Segregation of duties: requester cannot approve their own career action.']);
        $before=$action->toArray(); $action->update(['status'=>$d['decision'],'approved_by'=>$request->user()->id,'approved_at'=>now()]);
        $audit->record('hr.performance.career_action.'.strtolower($d['decision']),$action,$before,$action->fresh()->toArray());
        return back()->with('status','Career action decision recorded.');
    }

    public function applyCareerAction(Request $request,CareerAction $action,AuditService $audit): RedirectResponse
    {
        if($action->status!=='APPROVED') throw ValidationException::withMessages(['action'=>'Only approved career actions can be applied.']);
        if($action->effective_on->isFuture()) throw ValidationException::withMessages(['action'=>'Career action cannot be applied before its effective date.']);
        $employee=Employee::findOrFail($action->employee_id); $before=$employee->toArray();
        $changes=[]; if($action->to_job_position_id) $changes['job_position_id']=$action->to_job_position_id; if((float)$action->new_basic_salary!==(float)$employee->basic_salary) $changes['basic_salary']=$action->new_basic_salary;
        $employee->update($changes); $action->update(['status'=>'APPLIED']);
        $audit->record('hr.performance.career_action.applied',$action,$before,$employee->fresh()->toArray());
        return back()->with('status','Approved career action applied to Employee Master.');
    }
}
