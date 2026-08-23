<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{Employee,EndOfServicePolicy,FinalSettlement};
use App\Services\AuditService;
use App\Services\HR\SaudiEndOfServiceService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinalSettlementController extends Controller
{
    public function index(Request $request): View
    {
        $status=trim((string)$request->query('status'));
        $q=trim((string)$request->query('q'));
        $settlements=FinalSettlement::with('employee')->when($status,fn($x)=>$x->where('status',$status))
            ->when($q,fn($x)=>$x->where(fn($y)=>$y->where('settlement_no','like',"%{$q}%")->orWhereHas('employee',fn($e)=>$e->where('name','like',"%{$q}%")->orWhere('employee_no','like',"%{$q}%"))))
            ->latest()->paginate(25)->withQueryString();
        return view('hr.settlements.index',[
            'settlements'=>$settlements,
            'employees'=>Employee::whereIn('status',['ACTIVE','ON_LEAVE'])->orderBy('employee_no')->get(),
            'stats'=>[
                'draft'=>FinalSettlement::where('status','DRAFT')->count(),
                'pending'=>FinalSettlement::where('status','PENDING_APPROVAL')->count(),
                'approved'=>FinalSettlement::where('status','APPROVED')->count(),
                'netApproved'=>(float)FinalSettlement::whereIn('status',['APPROVED','PAID'])->sum('net_settlement'),
            ],
            'reasons'=>SaudiEndOfServiceService::REASONS,
        ]);
    }

    public function store(Request $request,SaudiEndOfServiceService $service,AuditService $audit): RedirectResponse
    {
        $d=$this->validatedCalculation($request);
        $employee=Employee::findOrFail($d['employee_id']);
        $policy=$service->activePolicy($request->user()->tenant_id,$d['last_working_day']);
        $calc=$service->calculate($employee,$policy,$d);
        $settlement=DB::transaction(function() use($request,$employee,$policy,$d,$calc){
            $tenant=$request->user()->tenant_id;
            $next=(int)FinalSettlement::withoutGlobalScopes()->where('tenant_id',$tenant)->lockForUpdate()->max('id')+1;
            return FinalSettlement::create([
                ...$calc,'organization_id'=>$employee->organization_id,'employee_id'=>$employee->id,'end_of_service_policy_id'=>$policy->id,
                'settlement_no'=>'FST-'.now()->format('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),'termination_reason'=>$d['termination_reason'],
                'status'=>'DRAFT','notes'=>$d['notes']??null,'created_by'=>$request->user()->id,
            ]);
        });
        $audit->record('hr.final_settlement.created',$settlement,[],$settlement->toArray());
        return redirect()->route('hr.settlements.show',$settlement)->with('status','Final settlement calculated as a draft snapshot.');
    }

    public function show(FinalSettlement $settlement): View
    {
        $settlement->load(['employee','policy']);
        return view('hr.settlements.show',compact('settlement'));
    }

    public function recalculate(Request $request,FinalSettlement $settlement,SaudiEndOfServiceService $service,AuditService $audit): RedirectResponse
    {
        if($settlement->status!=='DRAFT') throw ValidationException::withMessages(['settlement'=>'Only draft settlements can be recalculated.']);
        $d=$request->validate([
            'unused_leave_days'=>['nullable','numeric','min:0'],'unpaid_salary'=>['nullable','numeric','min:0'],'other_earnings'=>['nullable','numeric','min:0'],
            'notice_compensation'=>['nullable','numeric','min:0'],'employee_debt'=>['nullable','numeric','min:0'],'advance_recovery'=>['nullable','numeric','min:0'],'other_deductions'=>['nullable','numeric','min:0'],
        ]);
        $employee=Employee::findOrFail($settlement->employee_id);
        $policy=EndOfServicePolicy::findOrFail($settlement->end_of_service_policy_id);
        $calc=$service->calculate($employee,$policy,[...$d,'service_start_date'=>$settlement->service_start_date,'last_working_day'=>$settlement->last_working_day,'termination_reason'=>$settlement->termination_reason]);
        $before=$settlement->toArray(); $settlement->update($calc);
        $audit->record('hr.final_settlement.recalculated',$settlement,$before,$settlement->fresh()->toArray());
        return back()->with('status','Draft settlement recalculated.');
    }

    public function submit(Request $request,FinalSettlement $settlement,AuditService $audit): RedirectResponse
    {
        if($settlement->status!=='DRAFT') throw ValidationException::withMessages(['settlement'=>'Only draft settlements can be submitted.']);
        $before=$settlement->toArray(); $settlement->update(['status'=>'PENDING_APPROVAL','submitted_by'=>$request->user()->id,'submitted_at'=>now()]);
        $audit->record('hr.final_settlement.submitted',$settlement,$before,$settlement->fresh()->toArray()); return back()->with('status','Settlement submitted for independent approval.');
    }

    public function decide(Request $request,FinalSettlement $settlement,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['decision'=>['required',Rule::in(['APPROVED','REJECTED'])]]);
        if($settlement->status!=='PENDING_APPROVAL') throw ValidationException::withMessages(['settlement'=>'Only pending settlements can be decided.']);
        if((int)$settlement->submitted_by===(int)$request->user()->id || (int)$settlement->created_by===(int)$request->user()->id) throw ValidationException::withMessages(['settlement'=>'Segregation of duties: preparer or submitter cannot approve this settlement.']);
        $before=$settlement->toArray(); $settlement->update(['status'=>$d['decision'],'approved_by'=>$request->user()->id,'approved_at'=>now()]);
        $audit->record('hr.final_settlement.'.strtolower($d['decision']),$settlement,$before,$settlement->fresh()->toArray()); return back()->with('status','Settlement decision recorded.');
    }

    public function markPaid(Request $request,FinalSettlement $settlement,AuditService $audit): RedirectResponse
    {
        if($settlement->status!=='APPROVED') throw ValidationException::withMessages(['settlement'=>'Only approved settlements can be marked paid.']);
        $before=$settlement->toArray(); $settlement->update(['status'=>'PAID','paid_by'=>$request->user()->id,'paid_at'=>now()]);
        $audit->record('hr.final_settlement.paid',$settlement,$before,$settlement->fresh()->toArray()); return back()->with('status','Settlement marked paid. Accounting posting is a separate Finance control.');
    }

    private function validatedCalculation(Request $request): array
    {
        return $request->validate([
            'employee_id'=>['required','integer'],'termination_reason'=>['required',Rule::in(SaudiEndOfServiceService::REASONS)],'service_start_date'=>['nullable','date'],
            'last_working_day'=>['required','date'],'unused_leave_days'=>['nullable','numeric','min:0'],'unpaid_salary'=>['nullable','numeric','min:0'],'other_earnings'=>['nullable','numeric','min:0'],
            'notice_compensation'=>['nullable','numeric','min:0'],'employee_debt'=>['nullable','numeric','min:0'],'advance_recovery'=>['nullable','numeric','min:0'],'other_deductions'=>['nullable','numeric','min:0'],'notes'=>['nullable','string','max:3000'],
        ]);
    }
}
