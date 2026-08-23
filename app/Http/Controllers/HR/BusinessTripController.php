<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{BusinessTrip,BusinessTripExpense,Customer,Employee,Project};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BusinessTripController extends Controller
{
    public function index(Request $request): View
    {
        $q=trim((string)$request->query('q')); $status=trim((string)$request->query('status'));
        $trips=BusinessTrip::with(['employee','project','customer'])->when($q,fn($x)=>$x->where(fn($y)=>$y->where('trip_no','like',"%{$q}%")->orWhere('destination_city','like',"%{$q}%")->orWhere('purpose','like',"%{$q}%")))->when($status,fn($x)=>$x->where('status',$status))->orderByDesc('id')->paginate(25)->withQueryString();
        return view('hr.missions.index',[
            'trips'=>$trips,
            'employees'=>Employee::where('status','ACTIVE')->orderBy('employee_no')->get(),
            'projects'=>Project::whereIn('status',['PLANNED','ACTIVE','IN_PROGRESS'])->orderBy('project_no')->get(),
            'customers'=>Customer::where('status','ACTIVE')->orderBy('name')->get(),
            'stats'=>[
                'pending'=>BusinessTrip::where('status','PENDING')->count(),
                'active'=>BusinessTrip::whereIn('status',['APPROVED','IN_PROGRESS'])->count(),
                'unsettled'=>BusinessTrip::where('status','SETTLEMENT_PENDING')->count(),
                'advances'=>(float)BusinessTrip::whereIn('advance_status',['APPROVED','PAID'])->sum('approved_advance'),
            ],
        ]);
    }

    public function store(Request $request,AuditService $audit): RedirectResponse
    {
        $d=$request->validate([
            'employee_id'=>['required','integer'],'trip_type'=>['required',Rule::in(['LOCAL','INTERNATIONAL','FIELD_MISSION'])],'purpose'=>['required','string','max:500'],
            'destination_city'=>['required','string','max:120'],'destination_country'=>['required','string','max:80'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on'],
            'project_id'=>['nullable','integer'],'customer_id'=>['nullable','integer'],'per_diem_rate'=>['required','numeric','min:0'],'requested_advance'=>['nullable','numeric','min:0'],
            'travel_method'=>['nullable','string','max:40'],'hotel_required'=>['nullable','boolean'],'transport_required'=>['nullable','boolean'],
        ]);
        $tenant=$request->user()->tenant_id;
        $employee=Employee::where('tenant_id',$tenant)->findOrFail($d['employee_id']);
        if(!empty($d['project_id'])) abort_unless(Project::where('tenant_id',$tenant)->whereKey($d['project_id'])->exists(),422);
        if(!empty($d['customer_id'])) abort_unless(Customer::where('tenant_id',$tenant)->whereKey($d['customer_id'])->exists(),422);
        $days=Carbon::parse($d['starts_on'])->diffInDays(Carbon::parse($d['ends_on']))+1;
        $trip=DB::transaction(function() use($request,$d,$employee,$days,$tenant){
            $next=(int)BusinessTrip::withoutGlobalScopes()->where('tenant_id',$tenant)->lockForUpdate()->max('id')+1;
            return BusinessTrip::create([
                'organization_id'=>$employee->organization_id,'trip_no'=>'TRP-'.now()->format('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),'employee_id'=>$employee->id,
                'project_id'=>$d['project_id']??null,'customer_id'=>$d['customer_id']??null,'trip_type'=>$d['trip_type'],'purpose'=>$d['purpose'],'destination_city'=>$d['destination_city'],'destination_country'=>$d['destination_country'],
                'starts_on'=>$d['starts_on'],'ends_on'=>$d['ends_on'],'per_diem_rate'=>$d['per_diem_rate'],'per_diem_days'=>$days,'per_diem_total'=>round($days*(float)$d['per_diem_rate'],2),
                'requested_advance'=>$d['requested_advance']??0,'advance_status'=>(float)($d['requested_advance']??0)>0?'REQUESTED':'NONE','travel_method'=>$d['travel_method']??null,
                'hotel_required'=>$request->boolean('hotel_required'),'transport_required'=>$request->boolean('transport_required'),'status'=>'PENDING','requested_by'=>$request->user()->id,
            ]);
        });
        $audit->record('hr.mission.requested',$trip,[],$trip->toArray());
        return redirect()->route('hr.missions.show',$trip)->with('status','Business trip / mission submitted for approval.');
    }

    public function show(Request $request,BusinessTrip $mission): View
    {
        $mission->load(['employee','project','customer','expenses']);
        return view('hr.missions.show',['mission'=>$mission,'approvedExpenses'=>(float)$mission->expenses->where('status','APPROVED')->sum('amount')]);
    }

    public function decide(Request $request,BusinessTrip $mission,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['decision'=>['required',Rule::in(['APPROVED','REJECTED'])]]);
        if($mission->status!=='PENDING') throw ValidationException::withMessages(['mission'=>'Only pending missions can be decided.']);
        if((int)$mission->requested_by===(int)$request->user()->id) throw ValidationException::withMessages(['mission'=>'Segregation of duties: requester cannot approve or reject their own mission.']);
        $before=$mission->toArray();
        $mission->update(['status'=>$d['decision'],'approved_by'=>$request->user()->id,'approved_at'=>now()]);
        $audit->record('hr.mission.'.strtolower($d['decision']),$mission,$before,$mission->fresh()->toArray());
        return back()->with('status','Mission decision recorded.');
    }

    public function advance(Request $request,BusinessTrip $mission,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['action'=>['required',Rule::in(['APPROVE','MARK_PAID','CANCEL'])],'amount'=>['nullable','numeric','min:0']]);
        if(!in_array($mission->status,['APPROVED','IN_PROGRESS','COMPLETED','SETTLEMENT_PENDING'],true)) throw ValidationException::withMessages(['advance'=>'Mission must be approved first.']);
        $before=$mission->toArray();
        if($d['action']==='APPROVE') $mission->update(['approved_advance'=>$d['amount']??$mission->requested_advance,'advance_status'=>'APPROVED']);
        elseif($d['action']==='MARK_PAID'){ if($mission->advance_status!=='APPROVED') throw ValidationException::withMessages(['advance'=>'Advance must be approved before payment.']); $mission->update(['advance_status'=>'PAID']); }
        else $mission->update(['approved_advance'=>0,'advance_status'=>'CANCELLED']);
        $audit->record('hr.mission.advance.'.strtolower($d['action']),$mission,$before,$mission->fresh()->toArray());
        return back()->with('status','Advance status updated.');
    }

    public function storeExpense(Request $request,BusinessTrip $mission,AuditService $audit): RedirectResponse
    {
        if(!in_array($mission->status,['APPROVED','IN_PROGRESS','COMPLETED','SETTLEMENT_PENDING'],true)) throw ValidationException::withMessages(['expense'=>'Expenses can be submitted only for approved missions.']);
        $d=$request->validate(['expense_date'=>['required','date'],'category'=>['required',Rule::in(['HOTEL','TRANSPORT','MEALS','FUEL','TICKET','VISA','OTHER'])],'description'=>['required','string','max:500'],'amount'=>['required','numeric','gt:0'],'currency'=>['required','string','size:3'],'receipt_ref'=>['nullable','string','max:255']]);
        $expense=$mission->expenses()->create([...$d,'tenant_id'=>$request->user()->tenant_id,'status'=>'SUBMITTED','submitted_by'=>$request->user()->id]);
        $audit->record('hr.mission.expense.submitted',$expense,[],$expense->toArray());
        return back()->with('status','Expense submitted for review.');
    }

    public function decideExpense(Request $request,BusinessTrip $mission,BusinessTripExpense $expense,AuditService $audit): RedirectResponse
    {
        abort_unless((int)$expense->business_trip_id===(int)$mission->id,404);
        $d=$request->validate(['decision'=>['required',Rule::in(['APPROVED','REJECTED'])]]);
        if($expense->status!=='SUBMITTED') throw ValidationException::withMessages(['expense'=>'Only submitted expenses can be decided.']);
        if((int)$expense->submitted_by===(int)$request->user()->id) throw ValidationException::withMessages(['expense'=>'Segregation of duties: submitter cannot approve their own expense.']);
        $before=$expense->toArray(); $expense->update(['status'=>$d['decision'],'decided_by'=>$request->user()->id,'decided_at'=>now()]);
        $audit->record('hr.mission.expense.'.strtolower($d['decision']),$expense,$before,$expense->fresh()->toArray());
        return back()->with('status','Expense decision recorded.');
    }

    public function complete(Request $request,BusinessTrip $mission,AuditService $audit): RedirectResponse
    {
        if(!in_array($mission->status,['APPROVED','IN_PROGRESS'],true)) throw ValidationException::withMessages(['mission'=>'Only approved or in-progress missions can be completed.']);
        $d=$request->validate(['completion_notes'=>['nullable','string','max:2000']]); $before=$mission->toArray();
        $mission->update(['status'=>'SETTLEMENT_PENDING','completion_notes'=>$d['completion_notes']??null]);
        $audit->record('hr.mission.completed',$mission,$before,$mission->fresh()->toArray()); return back()->with('status','Mission completed and moved to settlement.');
    }

    public function settle(Request $request,BusinessTrip $mission,AuditService $audit): RedirectResponse
    {
        if($mission->status!=='SETTLEMENT_PENDING') throw ValidationException::withMessages(['settlement'=>'Mission must be pending settlement.']);
        if($mission->expenses()->where('status','SUBMITTED')->exists()) throw ValidationException::withMessages(['settlement'=>'All submitted expenses must be decided before settlement.']);
        $approvedExpenses=(float)$mission->expenses()->where('status','APPROVED')->sum('amount');
        $total=round((float)$mission->per_diem_total+$approvedExpenses,2); $paidAdvance=$mission->advance_status==='PAID'?(float)$mission->approved_advance:0;
        $before=$mission->toArray(); $mission->update(['status'=>'SETTLED','settlement_total'=>$total,'company_payable'=>max(0,round($total-$paidAdvance,2)),'employee_refund_due'=>max(0,round($paidAdvance-$total,2)),'settled_at'=>now(),'settled_by'=>$request->user()->id]);
        $audit->record('hr.mission.settled',$mission,$before,$mission->fresh()->toArray()); return back()->with('status','Mission settlement calculated and closed.');
    }
}
