<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceEntry,Employee,EmployeeDocument,EmploymentContract,FinalSettlement,JobPosition,LeaveRequest,PayrollLine};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB,Storage};
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $q=trim((string)$request->query('q')); $status=trim((string)$request->query('status')); $department=trim((string)$request->query('department'));
        $query=Employee::with('position')->when($q,fn($x)=>$x->where(fn($y)=>$y->where('employee_no','like',"%{$q}%")->orWhere('name','like',"%{$q}%")->orWhere('email','like',"%{$q}%")->orWhere('official_email','like',"%{$q}%")->orWhere('mobile','like',"%{$q}%")))
            ->when($status,fn($x)=>$x->where('status',$status))
            ->when($department,fn($x)=>$x->whereHas('position',fn($p)=>$p->where('department',$department)));
        $stats=['total'=>(clone $query)->count(),'active'=>(clone $query)->where('status','ACTIVE')->count(),'inactive'=>(clone $query)->where('status','!=','ACTIVE')->count()];
        $employees=$query->orderBy('employee_no')->paginate(25)->withQueryString();
        $departments=JobPosition::whereNotNull('department')->select('department')->distinct()->orderBy('department')->pluck('department');
        return view('hr.employees.index',compact('employees','stats','departments'));
    }

    public function show(Employee $employee): View
    {
        $employee->load(['position','manager','contracts'=>fn($q)=>$q->latest('starts_on'),'documents'=>fn($q)=>$q->orderByRaw('expires_on IS NULL, expires_on')]);
        $attendance=AttendanceEntry::where('employee_id',$employee->id)->latest('work_date')->limit(20)->get();
        $leaves=LeaveRequest::where('employee_id',$employee->id)->latest()->limit(20)->get();
        $payroll=PayrollLine::where('employee_id',$employee->id)->latest()->limit(12)->get();
        $finalSettlements=FinalSettlement::where('employee_id',$employee->id)->latest()->limit(8)->get();
        $serviceYears=$employee->hire_date ? $employee->hire_date->floatDiffInYears(now()) : 0;
        $expiring=$employee->documents->filter(fn($d)=>$d->expires_on && $d->expires_on->isBetween(today(),today()->addDays(90)));
        return view('hr.employees.show',compact('employee','attendance','leaves','payroll','finalSettlements','serviceYears','expiring'));
    }

    public function create(): View { return view('hr.employees.form',['employee'=>new Employee(),...$this->lookups()]); }
    public function edit(Employee $employee): View { return view('hr.employees.form',compact('employee')+$this->lookups($employee)); }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$this->validated($request);
        $uploads=$this->validatedUploads($request);
        $employee=DB::transaction(function() use($data,$uploads,$audit){
            $tenantId=Auth::user()->tenant_id;
            $employee=Employee::create([...$data,'employee_no'=>$this->nextEmployeeNumber($tenantId),'organization_id'=>Auth::user()->organization_id,'status'=>$data['status']??'ACTIVE']);
            $this->storeRegistrationDocuments($employee,$uploads);
            $audit->record('hr.employee.created',$employee,[],$employee->toArray());
            return $employee;
        });
        return redirect()->route('hr.employees.show',$employee)->with('status','Employee created as '.$employee->employee_no.'.');
    }

    public function update(Request $request, Employee $employee, AuditService $audit): RedirectResponse
    {
        $data=$this->validated($request,$employee);
        $uploads=$this->validatedUploads($request);
        DB::transaction(function() use($employee,$data,$uploads,$audit){
            $before=$employee->toArray();
            $employee->update($data);
            $this->storeRegistrationDocuments($employee,$uploads);
            $audit->record('hr.employee.updated',$employee,$before,$employee->fresh()->toArray());
        });
        return redirect()->route('hr.employees.show',$employee)->with('status','Employee updated.');
    }

    public function deactivate(Employee $employee, AuditService $audit): RedirectResponse { return $this->setStatus($employee,'INACTIVE',$audit,'Employee deactivated.'); }
    public function activate(Employee $employee, AuditService $audit): RedirectResponse { return $this->setStatus($employee,'ACTIVE',$audit,'Employee activated.'); }

    public function storeContract(Request $request, Employee $employee, AuditService $audit): RedirectResponse
    {
        $d=$request->validate([
            'contract_no'=>['required','string','max:80',Rule::unique('employment_contracts')->where(fn($q)=>$q->where('tenant_id',Auth::user()->tenant_id))],
            'contract_type'=>['required','string','max:40'],'starts_on'=>['required','date'],'ends_on'=>['nullable','date','after_or_equal:starts_on'],
            'probation_ends_on'=>['nullable','date','after_or_equal:starts_on'],'basic_salary'=>['required','numeric','min:0'],'housing_allowance'=>['nullable','numeric','min:0'],
            'transport_allowance'=>['nullable','numeric','min:0'],'other_allowances'=>['nullable','numeric','min:0'],'signed_on'=>['nullable','date'],'status'=>['required','in:ACTIVE,EXPIRED,TERMINATED'],
        ]);
        $contract=$employee->contracts()->create([...$d,'tenant_id'=>Auth::user()->tenant_id,'currency'=>'SAR','created_by'=>Auth::id()]);
        $audit->record('hr.contract.created',$contract,[],$contract->toArray());
        return back()->with('status','Employment contract added.');
    }

    public function storeDocument(Request $request, Employee $employee, AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['document_type'=>['required','string','max:60'],'document_no'=>['nullable','string','max:100'],'issued_on'=>['nullable','date'],'expires_on'=>['nullable','date','after_or_equal:issued_on'],'status'=>['required','in:VALID,EXPIRED,PENDING_RENEWAL'],'file'=>['nullable','file','mimes:pdf,jpg,jpeg,png','max:15360']]);
        $file=$d['file']??null; unset($d['file']);
        if($file) $d['file_path']=$file->store('hr/employees/'.$employee->id.'/documents','local');
        $document=$employee->documents()->create([...$d,'tenant_id'=>Auth::user()->tenant_id]);
        $audit->record('hr.employee.document_created',$document,[],$document->toArray());
        return back()->with('status','Employee document added.');
    }

    private function setStatus(Employee $employee,string $status,AuditService $audit,string $message): RedirectResponse
    {
        $before=$employee->toArray(); $employee->update(['status'=>$status]);
        $audit->record('hr.employee.status_changed',$employee,$before,$employee->fresh()->toArray());
        return back()->with('status',$message);
    }

    private function lookups(?Employee $employee=null): array
    {
        return [
            'positions'=>JobPosition::where('status','ACTIVE')->orderBy('department')->orderBy('title')->get(),
            'managers'=>Employee::where('status','ACTIVE')->when($employee,fn($q)=>$q->whereKeyNot($employee->id))->orderBy('name')->get(),
            'nextEmployeeNo'=>$employee?->employee_no ?: $this->previewNextEmployeeNumber(Auth::user()->tenant_id),
            'nationalities'=>$this->nationalities(),
            'phoneCountries'=>$this->phoneCountries(),
        ];
    }

    private function validated(Request $request, ?Employee $employee=null): array
    {
        $tenantId=Auth::user()->tenant_id;
        $data=$request->validate([
            'first_name'=>['required','string','max:80'],'middle_name'=>['nullable','string','max:80'],'last_name'=>['required','string','max:80'],
            'email'=>['nullable','email','max:255'],'official_email'=>['nullable','email','max:255',Rule::unique('employees','official_email')->where(fn($q)=>$q->where('tenant_id',$tenantId))->ignore($employee?->id)],
            'mobile'=>['nullable','string','max:30'],'mobile_country_code'=>['required','string','max:8'],'hire_date'=>['nullable','date'],'status'=>['required','in:ACTIVE,INACTIVE,ON_LEAVE,TERMINATED'],
            'job_position_id'=>['nullable','integer'],'manager_employee_id'=>['nullable','integer'],'nationality'=>['required','string','max:80'],'gender'=>['nullable','in:MALE,FEMALE'],'date_of_birth'=>['nullable','date','before:today'],
            'marital_status'=>['nullable','string','max:30'],'national_id'=>['nullable','string','max:50'],'iqama_no'=>['nullable','string','max:50'],'iqama_expiry'=>['nullable','date'],'passport_no'=>['nullable','string','max:50'],'passport_expiry'=>['nullable','date'],
            'gosi_no'=>['nullable','string','max:60'],'bank_name'=>['nullable','string','max:120'],'iban'=>['nullable','string','max:50'],'emergency_contact_name'=>['nullable','string','max:160'],'emergency_contact_mobile'=>['nullable','string','max:30'],'emergency_mobile_country_code'=>['required','string','max:8'],
            'address_line'=>['nullable','string','max:255'],'city'=>['nullable','string','max:100'],'employment_type'=>['nullable','string','max:40'],'contract_type'=>['nullable','string','max:40'],
            'probation_end_date'=>['nullable','date'],'contract_end_date'=>['nullable','date'],'basic_salary'=>['nullable','numeric','min:0'],'housing_allowance'=>['nullable','numeric','min:0'],'transport_allowance'=>['nullable','numeric','min:0'],'other_allowances'=>['nullable','numeric','min:0'],
            'work_location'=>['nullable','string','max:160'],'notes'=>['nullable','string','max:3000'],
        ]);
        if(!empty($data['job_position_id'])) abort_unless(JobPosition::where('tenant_id',$tenantId)->whereKey($data['job_position_id'])->exists(),422);
        if(!empty($data['manager_employee_id'])) abort_unless(Employee::where('tenant_id',$tenantId)->whereKey($data['manager_employee_id'])->exists(),422);
        $data['name']=trim(implode(' ',array_filter([$data['first_name'],$data['middle_name']??null,$data['last_name']])));
        $data['country']='SA';
        foreach(['basic_salary','housing_allowance','transport_allowance','other_allowances'] as $field) $data[$field]=$data[$field]??0;
        return $data;
    }

    private function validatedUploads(Request $request): array
    {
        return $request->validate([
            'national_id_document'=>['nullable','file','mimes:pdf,jpg,jpeg,png','max:15360'],
            'iqama_document'=>['nullable','file','mimes:pdf,jpg,jpeg,png','max:15360'],
            'passport_document'=>['nullable','file','mimes:pdf,jpg,jpeg,png','max:15360'],
            'contract_document'=>['nullable','file','mimes:pdf,jpg,jpeg,png','max:15360'],
            'iban_document'=>['nullable','file','mimes:pdf,jpg,jpeg,png','max:15360'],
        ]);
    }

    private function storeRegistrationDocuments(Employee $employee,array $uploads): void
    {
        $map=[
            'national_id_document'=>['NATIONAL_ID',$employee->national_id,null],
            'iqama_document'=>['IQAMA',$employee->iqama_no,$employee->iqama_expiry],
            'passport_document'=>['PASSPORT',$employee->passport_no,$employee->passport_expiry],
            'contract_document'=>['EMPLOYMENT_CONTRACT',null,$employee->contract_end_date],
            'iban_document'=>['IBAN_CERTIFICATE',$employee->iban,null],
        ];
        foreach($map as $field=>[$type,$number,$expires]){
            $file=$uploads[$field]??null; if(!$file) continue;
            $path=$file->store('hr/employees/'.$employee->id.'/documents','local');
            $employee->documents()->create(['tenant_id'=>$employee->tenant_id,'document_type'=>$type,'document_no'=>$number,'expires_on'=>$expires,'file_path'=>$path,'status'=>'VALID']);
        }
    }

    private function nextEmployeeNumber(int $tenantId): string
    {
        $numbers=Employee::where('tenant_id',$tenantId)->where('employee_no','like','UN-%')->lockForUpdate()->pluck('employee_no');
        $max=$numbers->reduce(function($carry,$number){return preg_match('/^UN-(\d+)$/',$number,$m)?max($carry,(int)$m[1]):$carry;},0);
        return 'UN-'.str_pad((string)($max+1),5,'0',STR_PAD_LEFT);
    }

    private function previewNextEmployeeNumber(int $tenantId): string
    {
        $numbers=Employee::where('tenant_id',$tenantId)->where('employee_no','like','UN-%')->pluck('employee_no');
        $max=$numbers->reduce(function($carry,$number){return preg_match('/^UN-(\d+)$/',$number,$m)?max($carry,(int)$m[1]):$carry;},0);
        return 'UN-'.str_pad((string)($max+1),5,'0',STR_PAD_LEFT);
    }

    private function nationalities(): array
    {
        return ['Saudi Arabian','Emirati','Bahraini','Kuwaiti','Omani','Qatari','Egyptian','Jordanian','Lebanese','Syrian','Yemeni','Sudanese','Indian','Pakistani','Bangladeshi','Filipino','Nepalese','Sri Lankan','Indonesian','Ethiopian','Turkish','British','American','Other'];
    }

    private function phoneCountries(): array
    {
        return [
            ['code'=>'+966','flag'=>'🇸🇦','name'=>'Saudi Arabia'],['code'=>'+971','flag'=>'🇦🇪','name'=>'UAE'],['code'=>'+973','flag'=>'🇧🇭','name'=>'Bahrain'],['code'=>'+965','flag'=>'🇰🇼','name'=>'Kuwait'],['code'=>'+968','flag'=>'🇴🇲','name'=>'Oman'],['code'=>'+974','flag'=>'🇶🇦','name'=>'Qatar'],
            ['code'=>'+20','flag'=>'🇪🇬','name'=>'Egypt'],['code'=>'+962','flag'=>'🇯🇴','name'=>'Jordan'],['code'=>'+961','flag'=>'🇱🇧','name'=>'Lebanon'],['code'=>'+963','flag'=>'🇸🇾','name'=>'Syria'],['code'=>'+967','flag'=>'🇾🇪','name'=>'Yemen'],['code'=>'+249','flag'=>'🇸🇩','name'=>'Sudan'],
            ['code'=>'+91','flag'=>'🇮🇳','name'=>'India'],['code'=>'+92','flag'=>'🇵🇰','name'=>'Pakistan'],['code'=>'+880','flag'=>'🇧🇩','name'=>'Bangladesh'],['code'=>'+63','flag'=>'🇵🇭','name'=>'Philippines'],['code'=>'+977','flag'=>'🇳🇵','name'=>'Nepal'],['code'=>'+94','flag'=>'🇱🇰','name'=>'Sri Lanka'],
            ['code'=>'+62','flag'=>'🇮🇩','name'=>'Indonesia'],['code'=>'+251','flag'=>'🇪🇹','name'=>'Ethiopia'],['code'=>'+90','flag'=>'🇹🇷','name'=>'Turkey'],['code'=>'+44','flag'=>'🇬🇧','name'=>'United Kingdom'],['code'=>'+1','flag'=>'🇺🇸','name'=>'United States'],
        ];
    }
}
