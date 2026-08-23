<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{Employee,PayrollPolicy,PayrollRun};
use App\Services\AuditService;
use App\Services\HR\{HrOperationsService,SaudiPayrollService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $tenant=$request->user()->tenant_id;
        return view('hr.payroll.index',[
            'policies'=>PayrollPolicy::where('tenant_id',$tenant)->orderByDesc('effective_from')->get(),
            'runs'=>PayrollRun::with('lines')->latest('period_start')->limit(24)->get(),
            'employees'=>Employee::where('status','ACTIVE')->orderBy('employee_no')->get(),
        ]);
    }

    public function storePolicy(Request $request,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['code'=>['required','string','max:50'],'name'=>['required','string','max:120'],'effective_from'=>['required','date'],'effective_to'=>['nullable','date','after_or_equal:effective_from'],'pension_employee_rate'=>['required','numeric','min:0','max:1'],'pension_employer_rate'=>['required','numeric','min:0','max:1'],'saned_employee_rate'=>['required','numeric','min:0','max:1'],'saned_employer_rate'=>['required','numeric','min:0','max:1'],'occupational_hazard_employer_rate'=>['required','numeric','min:0','max:1'],'overtime_basic_premium_rate'=>['required','numeric','min:0','max:2'],'standard_month_days'=>['required','numeric','min:1','max:31']]);
        $p=PayrollPolicy::create([...$d,'status'=>'ACTIVE']); $audit->record('hr.payroll.policy.created',$p,[],$p->toArray()); return back()->with('status','Payroll policy version created.');
    }

    public function createRun(Request $request,SaudiPayrollService $service,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['payroll_no'=>['required','string','max:50'],'period_start'=>['required','date'],'period_end'=>['required','date','after_or_equal:period_start'],'posting_date'=>['required','date'],'currency'=>['required','string','size:3']]);
        $policy=$service->activePolicy($request->user()->tenant_id,$d['period_end']);
        $run=PayrollRun::create(['organization_id'=>$request->user()->organization_id,'payroll_policy_id'=>$policy->id,'payroll_no'=>$d['payroll_no'],'period_start'=>$d['period_start'],'period_end'=>$d['period_end'],'posting_date'=>$d['posting_date'],'currency'=>strtoupper($d['currency']),'status'=>'DRAFT','created_by'=>Auth::id()]);
        $run=$service->generate($run,$policy); $audit->record('hr.payroll.generated',$run,[],$run->toArray()); return redirect()->route('hr.payroll.show',$run)->with('status','Payroll calculated from employee, attendance, leave and GOSI policy data.');
    }

    public function show(Request $request, PayrollRun $payroll): View
    {
        $payroll->load('lines');
        $employees=Employee::whereIn('id',$payroll->lines->pluck('employee_id'))->get()->keyBy('id');
        $policy=$payroll->payroll_policy_id?PayrollPolicy::find($payroll->payroll_policy_id):null;
        return view('hr.payroll.show',compact('payroll','employees','policy'));
    }

    public function recalculate(Request $request,PayrollRun $payroll,SaudiPayrollService $service,AuditService $audit): RedirectResponse
    {
        abort_unless($payroll->status==='DRAFT',422,'Only draft payroll can be recalculated.');
        $policy=$service->activePolicy($request->user()->tenant_id,$payroll->period_end); $before=$payroll->toArray(); $service->generate($payroll,$policy); $audit->record('hr.payroll.recalculated',$payroll,$before,$payroll->fresh()->toArray()); return back()->with('status','Payroll recalculated.');
    }

    public function post(Request $request,PayrollRun $payroll,HrOperationsService $service): RedirectResponse
    {
        $d=$request->validate(['expense_account_code'=>['required','string','max:30'],'payable_account_code'=>['required','string','max:30'],'deductions_account_code'=>['required','string','max:30']]);
        $service->postPayroll($payroll,$d['expense_account_code'],$d['payable_account_code'],$d['deductions_account_code']); return back()->with('status','Payroll posted to Finance.');
    }
}
