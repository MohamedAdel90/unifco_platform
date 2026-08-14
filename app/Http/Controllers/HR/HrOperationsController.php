<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceEntry,Employee,JobPosition,LeaveRequest,PayrollRun};
use App\Services\AuditService;
use App\Services\HR\HrOperationsService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HrOperationsController extends Controller
{
    public function index(): View
    {
        return view('hr.operations.index',[
            'positions'=>JobPosition::orderBy('code')->get(),'employees'=>Employee::orderBy('employee_no')->get(),
            'attendance'=>AttendanceEntry::latest('work_date')->limit(30)->get(),'leaves'=>LeaveRequest::latest()->limit(30)->get(),
            'payrolls'=>PayrollRun::with('lines')->latest()->limit(20)->get(),
        ]);
    }

    public function storePosition(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['code'=>['required','string','max:40'],'title'=>['required','string','max:120'],'department'=>['nullable','string','max:120']]);
        $p=JobPosition::create([...$d,'organization_id'=>$r->user()->organization_id,'status'=>'ACTIVE']);
        $audit->record('hr.position.created',$p,[],$p->toArray()); return back()->with('status','Job position created.');
    }

    public function assignPosition(Request $r, Employee $employee, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['job_position_id'=>['required','integer']]); $position=JobPosition::findOrFail($d['job_position_id']);
        $before=$employee->toArray(); $employee->update(['job_position_id'=>$position->id]);
        $audit->record('hr.employee.position_assigned',$employee,$before,$employee->fresh()->toArray()); return back()->with('status','Position assigned.');
    }

    public function storeAttendance(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['employee_id'=>['required','integer'],'work_date'=>['required','date'],'worked_hours'=>['required','numeric','min:0','max:24'],'overtime_hours'=>['required','numeric','min:0','max:24']]);
        Employee::findOrFail($d['employee_id']);
        $a=AttendanceEntry::create([...$d,'created_by'=>Auth::id(),'status'=>'RECORDED']);
        $audit->record('hr.attendance.recorded',$a,[],$a->toArray()); return back()->with('status','Attendance recorded.');
    }

    public function storeLeave(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['employee_id'=>['required','integer'],'leave_type'=>['required','string','max:40'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on'],'days'=>['required','numeric','gt:0'],'reason'=>['nullable','string']]);
        Employee::findOrFail($d['employee_id']);
        $leave=LeaveRequest::create([...$d,'requested_by'=>Auth::id(),'status'=>'PENDING']);
        $audit->record('hr.leave.requested',$leave,[],$leave->toArray()); return back()->with('status','Leave request submitted.');
    }

    public function decideLeave(Request $r, LeaveRequest $leave, HrOperationsService $svc): RedirectResponse
    {
        $d=$r->validate(['decision'=>['required','in:APPROVED,REJECTED']]); $svc->decideLeave($leave,$d['decision']); return back()->with('status','Leave request decided.');
    }

    public function storePayroll(Request $r, AuditService $audit): RedirectResponse
    {
        $d=$r->validate(['payroll_no'=>['required','string','max:50'],'period_start'=>['required','date'],'period_end'=>['required','date','after_or_equal:period_start'],'posting_date'=>['required','date'],'currency'=>['required','string','size:3'],'lines'=>['required','array','min:1'],'lines.*.employee_id'=>['required','integer'],'lines.*.basic_pay'=>['required','numeric','min:0'],'lines.*.allowances'=>['required','numeric','min:0'],'lines.*.deductions'=>['required','numeric','min:0']]);
        $run=PayrollRun::create(['organization_id'=>$r->user()->organization_id,'payroll_no'=>$d['payroll_no'],'period_start'=>$d['period_start'],'period_end'=>$d['period_end'],'posting_date'=>$d['posting_date'],'currency'=>strtoupper($d['currency']),'status'=>'DRAFT','created_by'=>Auth::id()]);
        foreach($d['lines'] as $line){ Employee::findOrFail($line['employee_id']); $net=(float)$line['basic_pay']+(float)$line['allowances']-(float)$line['deductions']; abort_if($net<0,422,'Deductions cannot exceed gross pay.'); $run->lines()->create([...$line,'net_pay'=>$net]); }
        $audit->record('hr.payroll.created',$run,[],$run->fresh('lines')->toArray()); return back()->with('status','Payroll run created.');
    }

    public function postPayroll(Request $r, PayrollRun $payroll, HrOperationsService $svc): RedirectResponse
    {
        $d=$r->validate(['expense_account_code'=>['required','string','max:30'],'payable_account_code'=>['required','string','max:30'],'deductions_account_code'=>['required','string','max:30']]);
        $svc->postPayroll($payroll,$d['expense_account_code'],$d['payable_account_code'],$d['deductions_account_code']); return back()->with('status','Payroll posted to Finance.');
    }
}
