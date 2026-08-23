<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{BusinessTrip,Employee,EmployeeGoal,EmployeeLeaveBalance,LeavePolicy,LeaveRequest,PayrollLine,PayrollRun,PerformanceReview};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SelfServiceController extends Controller
{
    private function employee(Request $request): Employee
    {
        abort_unless($request->user()->employee_id,403,'Your user account is not linked to an employee profile.');
        return Employee::where('tenant_id',$request->user()->tenant_id)->findOrFail($request->user()->employee_id);
    }

    private function directReports(Request $request)
    {
        $manager=$this->employee($request);
        return Employee::where('manager_employee_id',$manager->id)->where('status','!=','TERMINATED');
    }

    public function employeePortal(Request $request): View
    {
        $employee=$this->employee($request);
        $year=(int)$request->integer('year',now()->year);
        $balances=EmployeeLeaveBalance::where('employee_id',$employee->id)->where('balance_year',$year)->get();
        $policies=LeavePolicy::where('status','ACTIVE')->orderBy('name')->get();
        $leaves=LeaveRequest::where('employee_id',$employee->id)->latest()->limit(20)->get();
        $missions=BusinessTrip::where('employee_id',$employee->id)->latest()->limit(15)->get();
        $goals=EmployeeGoal::with('cycle')->where('employee_id',$employee->id)->latest()->limit(20)->get();
        $reviews=PerformanceReview::where('employee_id',$employee->id)->where('status','APPROVED')->latest('review_date')->limit(8)->get();
        $payroll=PayrollLine::where('employee_id',$employee->id)->latest()->limit(12)->get();
        $runIds=$payroll->pluck('payroll_run_id')->unique();
        $runs=PayrollRun::whereIn('id',$runIds)->get()->keyBy('id');
        $todayAttendance=$employee->attendanceEntries()->whereDate('work_date',today())->latest()->first();

        return view('hr.self-service.employee',compact('employee','year','balances','policies','leaves','missions','goals','reviews','payroll','runs','todayAttendance'));
    }

    public function requestLeave(Request $request,AuditService $audit): RedirectResponse
    {
        $employee=$this->employee($request);
        $d=$request->validate(['leave_policy_id'=>['required','integer'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on'],'day_portion'=>['required',Rule::in(['FULL_DAY','HALF_DAY_AM','HALF_DAY_PM'])],'reason'=>['nullable','string','max:1500']]);
        $policy=LeavePolicy::where('status','ACTIVE')->findOrFail($d['leave_policy_id']);
        $start=Carbon::parse($d['starts_on']); $end=Carbon::parse($d['ends_on']);
        $days=$d['day_portion']==='FULL_DAY' ? $start->diffInDays($end)+1 : .5;
        $balance=EmployeeLeaveBalance::where('employee_id',$employee->id)->where('leave_policy_id',$policy->id)->where('balance_year',$start->year)->first();
        if($policy->paid && (!$balance || $balance->availableDays()<$days)) throw ValidationException::withMessages(['leave'=>'Insufficient leave balance for this request.']);
        $status=$policy->requires_approval?'PENDING':'APPROVED';
        $leave=LeaveRequest::create(['employee_id'=>$employee->id,'leave_policy_id'=>$policy->id,'leave_type'=>$policy->leave_type,'starts_on'=>$start,'ends_on'=>$end,'days'=>$days,'day_portion'=>$d['day_portion'],'reason'=>$d['reason']??null,'status'=>$status,'requested_by'=>$request->user()->id,'decided_by'=>$status==='APPROVED'?$request->user()->id:null,'decided_at'=>$status==='APPROVED'?now():null]);
        if($status==='APPROVED' && $balance) $balance->increment('used_days',$days);
        $audit->record('hr.self_service.leave.requested',$leave,[],$leave->toArray());
        return back()->with('status','Leave request submitted.');
    }

    public function requestMission(Request $request,AuditService $audit): RedirectResponse
    {
        $employee=$this->employee($request);
        $d=$request->validate(['trip_type'=>['required',Rule::in(['LOCAL','INTERNATIONAL','FIELD_MISSION'])],'purpose'=>['required','string','max:500'],'destination_city'=>['required','string','max:120'],'destination_country'=>['required','string','max:80'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on'],'requested_advance'=>['nullable','numeric','min:0'],'travel_method'=>['nullable','string','max:40'],'hotel_required'=>['nullable','boolean'],'transport_required'=>['nullable','boolean']]);
        $days=Carbon::parse($d['starts_on'])->diffInDays(Carbon::parse($d['ends_on']))+1;
        $next=(int)BusinessTrip::withoutGlobalScopes()->where('tenant_id',$request->user()->tenant_id)->max('id')+1;
        $trip=BusinessTrip::create(['organization_id'=>$employee->organization_id,'trip_no'=>'ESS-TRP-'.now()->format('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),'employee_id'=>$employee->id,'trip_type'=>$d['trip_type'],'purpose'=>$d['purpose'],'destination_city'=>$d['destination_city'],'destination_country'=>$d['destination_country'],'starts_on'=>$d['starts_on'],'ends_on'=>$d['ends_on'],'per_diem_rate'=>0,'per_diem_days'=>$days,'per_diem_total'=>0,'requested_advance'=>$d['requested_advance']??0,'advance_status'=>(float)($d['requested_advance']??0)>0?'REQUESTED':'NONE','travel_method'=>$d['travel_method']??null,'hotel_required'=>$request->boolean('hotel_required'),'transport_required'=>$request->boolean('transport_required'),'status'=>'PENDING','requested_by'=>$request->user()->id]);
        $audit->record('hr.self_service.mission.requested',$trip,[],$trip->toArray());
        return back()->with('status','Mission request submitted. HR can assign the applicable per-diem policy before approval.');
    }

    public function updateGoal(Request $request,EmployeeGoal $goal,AuditService $audit): RedirectResponse
    {
        $employee=$this->employee($request);
        abort_unless((int)$goal->employee_id===(int)$employee->id,404);
        $d=$request->validate(['actual_value'=>['nullable','numeric'],'status'=>['required',Rule::in(['OPEN','IN_PROGRESS','COMPLETED'])]]);
        $before=$goal->toArray(); $goal->update($d);
        $audit->record('hr.self_service.goal.updated',$goal,$before,$goal->fresh()->toArray());
        return back()->with('status','Goal progress updated.');
    }

    public function managerPortal(Request $request): View
    {
        $manager=$this->employee($request);
        $team=$this->directReports($request)->with('position')->orderBy('name')->get();
        abort_if($team->isEmpty() && !in_array($request->user()->role,['ADMIN','MANAGER','SUPERVISOR']),403,'No manager self-service scope is assigned to this account.');
        $ids=$team->pluck('id');
        $pendingLeaves=LeaveRequest::whereIn('employee_id',$ids)->where('status','PENDING')->latest()->get();
        $pendingMissions=BusinessTrip::with('employee')->whereIn('employee_id',$ids)->where('status','PENDING')->latest()->get();
        $goals=EmployeeGoal::with('employee')->whereIn('employee_id',$ids)->whereIn('status',['OPEN','IN_PROGRESS'])->latest()->limit(30)->get();
        $reviews=PerformanceReview::with('employee')->whereIn('employee_id',$ids)->latest('review_date')->limit(20)->get();
        return view('hr.self-service.manager',compact('manager','team','pendingLeaves','pendingMissions','goals','reviews'));
    }

    public function decideTeamLeave(Request $request,LeaveRequest $leave,AuditService $audit): RedirectResponse
    {
        $teamIds=$this->directReports($request)->pluck('id');
        abort_unless($teamIds->contains($leave->employee_id),403);
        $d=$request->validate(['decision'=>['required',Rule::in(['APPROVED','REJECTED'])],'decision_notes'=>['nullable','string','max:1500']]);
        if($leave->status!=='PENDING') throw ValidationException::withMessages(['leave'=>'Only pending leave requests can be decided.']);
        $before=$leave->toArray();
        DB::transaction(function() use($leave,$d,$request){
            if($d['decision']==='APPROVED' && $leave->leave_policy_id){
                $balance=EmployeeLeaveBalance::where('employee_id',$leave->employee_id)->where('leave_policy_id',$leave->leave_policy_id)->where('balance_year',$leave->starts_on->year)->lockForUpdate()->first();
                abort_if(!$balance || $balance->availableDays()<(float)$leave->days,422,'Insufficient leave balance at approval time.');
                $balance->increment('used_days',(float)$leave->days);
            }
            $leave->update(['status'=>$d['decision'],'decision_notes'=>$d['decision_notes']??null,'decided_by'=>$request->user()->id,'decided_at'=>now()]);
        });
        $audit->record('hr.manager_self_service.leave.'.strtolower($d['decision']),$leave,$before,$leave->fresh()->toArray());
        return back()->with('status','Team leave decision recorded.');
    }

    public function decideTeamMission(Request $request,BusinessTrip $mission,AuditService $audit): RedirectResponse
    {
        $teamIds=$this->directReports($request)->pluck('id');
        abort_unless($teamIds->contains($mission->employee_id),403);
        $d=$request->validate(['decision'=>['required',Rule::in(['APPROVED','REJECTED'])]]);
        if($mission->status!=='PENDING') throw ValidationException::withMessages(['mission'=>'Only pending missions can be decided.']);
        if((int)$mission->requested_by===(int)$request->user()->id) throw ValidationException::withMessages(['mission'=>'Requester cannot approve their own mission.']);
        $before=$mission->toArray(); $mission->update(['status'=>$d['decision'],'approved_by'=>$request->user()->id,'approved_at'=>now()]);
        $audit->record('hr.manager_self_service.mission.'.strtolower($d['decision']),$mission,$before,$mission->fresh()->toArray());
        return back()->with('status','Team mission decision recorded.');
    }
}
