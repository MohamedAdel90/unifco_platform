<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{Employee,EmployeeLeaveBalance,LeavePolicy,LeaveRequest};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $r): View
    {
        $year=(int)$r->integer('year',now()->year);
        $balances=EmployeeLeaveBalance::where('balance_year',$year)->latest()->get();
        return view('hr.leave.index',[
            'year'=>$year,
            'employees'=>Employee::where('status','ACTIVE')->orderBy('employee_no')->get(),
            'policies'=>LeavePolicy::where('status','ACTIVE')->orderBy('code')->get(),
            'balances'=>$balances,
            'requests'=>LeaveRequest::latest()->limit(60)->get(),
            'pending'=>LeaveRequest::where('status','PENDING')->count(),
            'approvedThisMonth'=>LeaveRequest::where('status','APPROVED')->whereMonth('starts_on',now()->month)->whereYear('starts_on',now()->year)->sum('days'),
            'availableDays'=>$balances->sum(fn($b)=>$b->availableDays()),
        ]);
    }

    public function storePolicy(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate([
            'code'=>['required','string','max:40'],'name'=>['required','string','max:120'],'leave_type'=>['required','string','max:40'],
            'annual_entitlement_days'=>['required','numeric','min:0','max:365'],'accrual_method'=>['required','in:MONTHLY,ANNUAL,MANUAL'],
            'carry_forward_limit_days'=>['required','numeric','min:0','max:365'],'requires_approval'=>['nullable','boolean'],'paid'=>['nullable','boolean'],
        ]);
        $policy=LeavePolicy::create([...$d,'organization_id'=>$r->user()->organization_id,'requires_approval'=>$r->boolean('requires_approval',true),'paid'=>$r->boolean('paid',true),'status'=>'ACTIVE']);
        $audit->record('hr.leave_policy.created',$policy,[],$policy->toArray());
        return back()->with('status','Leave policy created.');
    }

    public function seedBalance(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['employee_id'=>['required','integer'],'leave_policy_id'=>['required','integer'],'balance_year'=>['required','integer','min:2020','max:2100'],'opening_days'=>['required','numeric','min:0','max:365'],'carried_forward_days'=>['nullable','numeric','min:0','max:365']]);
        $employee=Employee::findOrFail($d['employee_id']);
        $policy=LeavePolicy::findOrFail($d['leave_policy_id']);
        $carried=min((float)($d['carried_forward_days']??0),(float)$policy->carry_forward_limit_days);
        $balance=EmployeeLeaveBalance::updateOrCreate(
            ['employee_id'=>$employee->id,'leave_policy_id'=>$policy->id,'balance_year'=>$d['balance_year']],
            ['opening_days'=>$d['opening_days'],'carried_forward_days'=>$carried]
        );
        $audit->record('hr.leave_balance.seeded',$balance,[],$balance->toArray());
        return back()->with('status','Leave balance initialized.');
    }

    public function accrue(Request $r, EmployeeLeaveBalance $balance, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['months'=>['required','integer','min:1','max:12']]);
        $policy=LeavePolicy::findOrFail($balance->leave_policy_id);
        abort_if($policy->accrual_method!=='MONTHLY',422,'This policy is not monthly-accrual based.');
        $before=$balance->toArray();
        $amount=round(((float)$policy->annual_entitlement_days/12)*$d['months'],2);
        $balance->update(['accrued_days'=>(float)$balance->accrued_days+$amount]);
        $audit->record('hr.leave_balance.accrued',$balance,$before,$balance->fresh()->toArray());
        return back()->with('status','Leave accrual posted.');
    }

    public function store(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate([
            'employee_id'=>['required','integer'],'leave_policy_id'=>['required','integer'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on'],
            'days'=>['required','numeric','gt:0','max:365'],'day_portion'=>['required','in:FULL_DAY,HALF_DAY_AM,HALF_DAY_PM'],'reason'=>['nullable','string','max:1500'],
        ]);
        $employee=Employee::findOrFail($d['employee_id']);
        $policy=LeavePolicy::findOrFail($d['leave_policy_id']);
        $balance=EmployeeLeaveBalance::where('employee_id',$employee->id)->where('leave_policy_id',$policy->id)->where('balance_year',substr($d['starts_on'],0,4))->first();
        abort_if($policy->paid && (!$balance || $balance->availableDays()<(float)$d['days']),422,'Insufficient leave balance for this request.');
        $status=$policy->requires_approval?'PENDING':'APPROVED';
        $leave=LeaveRequest::create([...$d,'leave_type'=>$policy->leave_type,'status'=>$status,'requested_by'=>Auth::id(),'decided_by'=>$status==='APPROVED'?Auth::id():null,'decided_at'=>$status==='APPROVED'?now():null]);
        if ($status==='APPROVED' && $balance) $balance->increment('used_days',(float)$d['days']);
        $audit->record('hr.leave.phase2_requested',$leave,[],$leave->toArray());
        return back()->with('status','Leave request submitted.');
    }

    public function decide(Request $r, LeaveRequest $leave, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['decision'=>['required','in:APPROVED,REJECTED'],'decision_notes'=>['nullable','string','max:1500']]);
        abort_if($leave->status!=='PENDING',422,'Only pending leave requests can be decided.');
        $before=$leave->toArray();
        DB::transaction(function() use($leave,$d){
            if ($d['decision']==='APPROVED' && $leave->leave_policy_id) {
                $balance=EmployeeLeaveBalance::where('employee_id',$leave->employee_id)->where('leave_policy_id',$leave->leave_policy_id)->where('balance_year',$leave->starts_on->year)->lockForUpdate()->first();
                abort_if(!$balance || $balance->availableDays()<(float)$leave->days,422,'Insufficient leave balance at approval time.');
                $balance->increment('used_days',(float)$leave->days);
            }
            $leave->update(['status'=>$d['decision'],'decision_notes'=>$d['decision_notes']??null,'decided_by'=>Auth::id(),'decided_at'=>now()]);
        });
        $audit->record('hr.leave.phase2_decided',$leave,$before,$leave->fresh()->toArray());
        return back()->with('status','Leave decision recorded.');
    }
}
