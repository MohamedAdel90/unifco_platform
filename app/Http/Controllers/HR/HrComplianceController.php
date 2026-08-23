<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{Employee,EmploymentContract,HrComplianceCase,HrComplianceProfile,HrComplianceScanRun};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HrComplianceController extends Controller
{
    private const BANDS=['RED','LOW_GREEN','MID_GREEN','HIGH_GREEN','PLATINUM','NOT_REVIEWED'];

    public function index(Request $request): View
    {
        $profile=HrComplianceProfile::firstOrCreate(['organization_id'=>$request->user()->organization_id],[
            'economic_activity'=>null,'qiwa_contract_target_pct'=>90,'nitaqat_reported_band'=>'NOT_REVIEWED','wps_status'=>'NOT_REVIEWED','updated_by'=>$request->user()->id,
        ]);
        $active=Employee::where('status','ACTIVE')->get();
        $saudis=$active->filter(fn($e)=>$this->isSaudi($e));
        $contracts=EmploymentContract::whereIn('employee_id',$active->pluck('id'))->where('status','ACTIVE')->get();
        $documented=$contracts->where('qiwa_status','DOCUMENTED')->count();
        $qiwaPct=$contracts->count()?round($documented/$contracts->count()*100,1):0;
        $open=HrComplianceCase::with('employee')->whereIn('status',['OPEN','IN_PROGRESS'])->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'MEDIUM' THEN 3 ELSE 4 END")->orderBy('due_on')->limit(100)->get();
        $latest=HrComplianceScanRun::latest('completed_at')->first();
        return view('hr.compliance.index',compact('profile','active','saudis','contracts','documented','qiwaPct','open','latest'));
    }

    public function updateProfile(Request $request,AuditService $audit): RedirectResponse
    {
        $d=$request->validate([
            'economic_activity'=>['nullable','string','max:180'],'qiwa_contract_target_pct'=>['required','numeric','min:0','max:100'],
            'nitaqat_reported_band'=>['required',Rule::in(self::BANDS)],'wps_status'=>['required',Rule::in(['NOT_REVIEWED','COMPLIANT','ACTION_REQUIRED'])],
            'last_wps_period'=>['nullable','date'],'mudad_reference'=>['nullable','string','max:120'],
            'last_gosi_reconciliation_on'=>['nullable','date'],'last_qiwa_reconciliation_on'=>['nullable','date'],'last_nitaqat_review_on'=>['nullable','date'],
        ]);
        $profile=HrComplianceProfile::firstOrCreate(['organization_id'=>$request->user()->organization_id]); $before=$profile->toArray();
        $profile->update([...$d,'updated_by'=>$request->user()->id]);
        $audit->record('hr.compliance.profile.updated',$profile,$before,$profile->fresh()->toArray());
        return back()->with('status','Saudi compliance profile updated.');
    }

    public function updateEmployeeRegistration(Request $request,Employee $employee,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['gosi_status'=>['required',Rule::in(['REGISTERED','PENDING','NOT_REGISTERED','NOT_APPLICABLE'])],'gosi_no'=>['nullable','string','max:80'],'gosi_registered_on'=>['nullable','date']]);
        $before=$employee->toArray(); $employee->forceFill($d)->save(); $audit->record('hr.compliance.gosi.updated',$employee,$before,$employee->fresh()->toArray());
        return back()->with('status','GOSI registration evidence updated.');
    }

    public function updateQiwa(Request $request,EmploymentContract $contract,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['qiwa_status'=>['required',Rule::in(['DOCUMENTED','PENDING_EMPLOYEE','PENDING_EMPLOYER','NOT_DOCUMENTED','NOT_APPLICABLE'])],'qiwa_contract_ref'=>['nullable','string','max:120'],'qiwa_documented_on'=>['nullable','date']]);
        $before=$contract->toArray(); $contract->forceFill($d)->save(); $audit->record('hr.compliance.qiwa.updated',$contract,$before,$contract->fresh()->toArray());
        return back()->with('status','Qiwa contract evidence updated.');
    }

    public function scan(Request $request,AuditService $audit): RedirectResponse
    {
        $tenantId=(int)$request->user()->tenant_id; $orgId=$request->user()->organization_id; $today=today(); $created=0; $seen=[];
        $profile=HrComplianceProfile::firstOrCreate(['organization_id'=>$orgId],['qiwa_contract_target_pct'=>90,'nitaqat_reported_band'=>'NOT_REVIEWED','wps_status'=>'NOT_REVIEWED','updated_by'=>$request->user()->id]);
        $employees=Employee::where('status','ACTIVE')->get();
        foreach($employees as $e){
            if(!$e->gosi_no || $e->gosi_status!=='REGISTERED') $created+=$this->upsertCase($request,'GOSI','HIGH','GOSI registration evidence incomplete','Active employee '.$e->employee_no.' requires confirmed GOSI registration/evidence.',$e,'gosi:'.$e->id,$today->copy()->addDays(7),$seen);
            if($this->isSaudi($e) && !$e->national_id) $created+=$this->upsertCase($request,'IDENTITY','HIGH','Saudi national ID missing','Saudi employee '.$e->employee_no.' has no national ID recorded.',$e,'national-id:'.$e->id,$today->copy()->addDays(3),$seen);
            if(!$this->isSaudi($e)){
                if(!$e->iqama_no || !$e->iqama_expiry) $created+=$this->upsertCase($request,'IQAMA','CRITICAL','Iqama data incomplete','Non-Saudi employee '.$e->employee_no.' is missing Iqama number or expiry date.',$e,'iqama-missing:'.$e->id,$today->copy()->addDays(2),$seen);
                elseif($e->iqama_expiry->lte($today->copy()->addDays(90))) $created+=$this->upsertCase($request,'IQAMA',$e->iqama_expiry->lte($today->copy()->addDays(30))?'CRITICAL':'HIGH','Iqama expiry approaching','Iqama for '.$e->employee_no.' expires '.$e->iqama_expiry->format('d M Y').'.',$e,'iqama-expiry:'.$e->id,$e->iqama_expiry,$seen);
            }
            if(!$e->passport_no || !$e->passport_expiry) $created+=$this->upsertCase($request,'PASSPORT','MEDIUM','Passport data incomplete','Employee '.$e->employee_no.' is missing passport number or expiry date.',$e,'passport-missing:'.$e->id,$today->copy()->addDays(30),$seen);
            elseif($e->passport_expiry->lte($today->copy()->addDays(90))) $created+=$this->upsertCase($request,'PASSPORT','HIGH','Passport expiry approaching','Passport for '.$e->employee_no.' expires '.$e->passport_expiry->format('d M Y').'.',$e,'passport-expiry:'.$e->id,$e->passport_expiry,$seen);
            if(!$e->iban) $created+=$this->upsertCase($request,'WPS','HIGH','IBAN missing for payroll/WPS readiness','Active employee '.$e->employee_no.' has no IBAN recorded for wage payment readiness.',$e,'iban:'.$e->id,$today->copy()->addDays(7),$seen);
        }
        $contracts=EmploymentContract::whereIn('employee_id',$employees->pluck('id'))->where('status','ACTIVE')->get();
        foreach($contracts as $c) if($c->qiwa_status!=='DOCUMENTED') $created+=$this->upsertCase($request,'QIWA','HIGH','Employment contract not confirmed documented in Qiwa','Active contract '.$c->contract_no.' is not marked DOCUMENTED in the local Qiwa evidence register.',Employee::find($c->employee_id),'qiwa:'.$c->id,$today->copy()->addDays(7),$seen);
        $documented=$contracts->where('qiwa_status','DOCUMENTED')->count(); $qiwaPct=$contracts->count()?round($documented/$contracts->count()*100,1):0;
        if($contracts->count() && $qiwaPct<(float)$profile->qiwa_contract_target_pct) $created+=$this->upsertCase($request,'QIWA','CRITICAL','Qiwa contract documentation target below threshold','Local evidence shows '.$qiwaPct.'% documented active contracts versus configured target '.$profile->qiwa_contract_target_pct.'%.',null,'qiwa-target',$today->copy()->addDays(7),$seen);
        if(!$profile->economic_activity || $profile->nitaqat_reported_band==='NOT_REVIEWED') $created+=$this->upsertCase($request,'NITAQAT','HIGH','Nitaqat official readiness data requires review','Record the establishment economic activity and latest band from the official HRSD/Qiwa source. Internal headcount ratio is not treated as the official Nitaqat band.',null,'nitaqat-review',$today->copy()->addDays(7),$seen);
        if($profile->wps_status!=='COMPLIANT' || !$profile->last_wps_period) $created+=$this->upsertCase($request,'WPS','HIGH','Mudad/WPS reconciliation requires review','Record the latest wage protection reconciliation period and evidence/reference from Mudad.',null,'wps-review',$today->copy()->addDays(7),$seen);
        HrComplianceCase::whereIn('status',['OPEN','IN_PROGRESS'])->whereNotNull('source_key')->whereNotIn('source_key',$seen)->update(['status'=>'RESOLVED','resolved_by'=>$request->user()->id,'resolved_at'=>now(),'remediation_notes'=>'Automatically cleared by subsequent compliance scan.']);
        $summary=['active_employees'=>$employees->count(),'saudi_employees'=>$employees->filter(fn($e)=>$this->isSaudi($e))->count(),'active_contracts'=>$contracts->count(),'qiwa_documented_pct'=>$qiwaPct,'new_or_existing_findings'=>count($seen),'new_cases'=>$created,'open_cases'=>HrComplianceCase::whereIn('status',['OPEN','IN_PROGRESS'])->count()];
        $scan=HrComplianceScanRun::create(['organization_id'=>$orgId,'scan_no'=>'HRC-'.now()->format('Ymd-His'),'summary'=>$summary,'run_by'=>$request->user()->id,'completed_at'=>now()]);
        $audit->record('hr.compliance.scan.completed',$scan,[],$summary);
        return back()->with('status','Saudi HR compliance scan completed: '.$summary['open_cases'].' open findings.');
    }

    public function updateCase(Request $request,HrComplianceCase $case,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['status'=>['required',Rule::in(['OPEN','IN_PROGRESS','RESOLVED','ACCEPTED_RISK'])],'remediation_notes'=>['nullable','string','max:4000']]);
        $before=$case->toArray(); $case->update([...$d,'resolved_by'=>in_array($d['status'],['RESOLVED','ACCEPTED_RISK'])?$request->user()->id:null,'resolved_at'=>in_array($d['status'],['RESOLVED','ACCEPTED_RISK'])?now():null]);
        $audit->record('hr.compliance.case.updated',$case,$before,$case->fresh()->toArray()); return back()->with('status','Compliance case updated.');
    }

    private function upsertCase(Request $request,string $cat,string $severity,string $title,string $description,?Employee $employee,string $sourceKey,Carbon $due,array &$seen): int
    {
        $seen[]=$sourceKey; $case=HrComplianceCase::where('source_key',$sourceKey)->whereIn('status',['OPEN','IN_PROGRESS'])->first();
        if($case){$case->update(['severity'=>$severity,'title'=>$title,'description'=>$description,'due_on'=>$due]); return 0;}
        $next=(int)HrComplianceCase::withoutGlobalScopes()->where('tenant_id',$request->user()->tenant_id)->max('id')+1;
        HrComplianceCase::create(['organization_id'=>$request->user()->organization_id,'case_no'=>'HRC-'.now()->format('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),'category'=>$cat,'severity'=>$severity,'title'=>$title,'description'=>$description,'employee_id'=>$employee?->id,'source_key'=>$sourceKey,'due_on'=>$due,'status'=>'OPEN']); return 1;
    }

    private function isSaudi(Employee $e): bool { return in_array(strtolower(trim((string)$e->nationality)),['saudi','saudi arabian','ksa','سعودي','سعودية'],true); }
}
