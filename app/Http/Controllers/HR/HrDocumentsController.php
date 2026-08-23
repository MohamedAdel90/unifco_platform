<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\{Employee,HrDocumentTemplate,HrServiceRequest};
use App\Services\{AuditService,AuthorizationService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HrDocumentsController extends Controller
{
    private const TYPES=['SALARY_CERTIFICATE','SERVICE_CERTIFICATE','BANK_LETTER','NOC','PROFILE_UPDATE','GENERAL'];
    private const PROFILE_FIELDS=['email','mobile','address_line','city','emergency_contact_name','emergency_contact_mobile','bank_name','iban'];

    private function currentEmployee(Request $request): Employee
    {
        abort_unless($request->user()->employee_id,403,'Your user account is not linked to an employee profile.');
        return Employee::where('tenant_id',$request->user()->tenant_id)->findOrFail($request->user()->employee_id);
    }

    private function mayReadHr(Request $request): bool
    {
        return app(AuthorizationService::class)->allows($request->user(),'hr.employee.read');
    }

    public function employeeIndex(Request $request): View
    {
        $employee=$this->currentEmployee($request);
        $requests=HrServiceRequest::where('employee_id',$employee->id)->latest()->limit(40)->get();
        return view('hr.documents.employee',compact('employee','requests'));
    }

    public function storeEmployeeRequest(Request $request,AuditService $audit): RedirectResponse
    {
        $employee=$this->currentEmployee($request);
        $d=$request->validate([
            'request_type'=>['required',Rule::in(self::TYPES)],'language'=>['required',Rule::in(['EN','AR'])],
            'recipient_name'=>['nullable','string','max:180'],'purpose'=>['nullable','string','max:500'],'notes'=>['nullable','string','max:3000'],
            'email'=>['nullable','email','max:255'],'mobile'=>['nullable','string','max:40'],'address_line'=>['nullable','string','max:255'],'city'=>['nullable','string','max:120'],
            'emergency_contact_name'=>['nullable','string','max:180'],'emergency_contact_mobile'=>['nullable','string','max:40'],'bank_name'=>['nullable','string','max:160'],'iban'=>['nullable','string','max:40'],
        ]);
        $changes=[];
        if($d['request_type']==='PROFILE_UPDATE') foreach(self::PROFILE_FIELDS as $field) if($request->filled($field) && (string)$employee->{$field}!==trim((string)$request->input($field))) $changes[$field]=trim((string)$request->input($field));
        if($d['request_type']==='PROFILE_UPDATE' && !$changes) throw ValidationException::withMessages(['request_type'=>'Enter at least one profile field that is different from the current employee record.']);
        $requestNo=$this->nextRequestNo($request->user()->tenant_id);
        $service=HrServiceRequest::create([
            'organization_id'=>$employee->organization_id,'request_no'=>$requestNo,'employee_id'=>$employee->id,'request_type'=>$d['request_type'],'language'=>$d['language'],
            'recipient_name'=>$d['recipient_name']??null,'purpose'=>$d['purpose']??null,'requested_changes'=>$changes?:null,'notes'=>$d['notes']??null,'status'=>'PENDING','requested_by'=>$request->user()->id,
        ]);
        $audit->record('hr.documents.requested',$service,[],$service->toArray());
        return back()->with('status','HR request '.$requestNo.' submitted.');
    }

    public function index(Request $request): View
    {
        $this->ensureDefaults($request);
        $status=trim((string)$request->query('status')); $type=trim((string)$request->query('type'));
        $requests=HrServiceRequest::with('employee')->when($status,fn($q)=>$q->where('status',$status))->when($type,fn($q)=>$q->where('request_type',$type))->latest()->paginate(30)->withQueryString();
        return view('hr.documents.index',[
            'requests'=>$requests,'templates'=>HrDocumentTemplate::orderBy('document_type')->orderBy('language')->get(),
            'stats'=>['pending'=>HrServiceRequest::where('status','PENDING')->count(),'approved'=>HrServiceRequest::where('status','APPROVED')->count(),'issued'=>HrServiceRequest::where('status','ISSUED')->count(),'profile_updates'=>HrServiceRequest::where('request_type','PROFILE_UPDATE')->where('status','PENDING')->count()],
            'types'=>self::TYPES,
        ]);
    }

    public function storeTemplate(Request $request,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['code'=>['required','string','max:80'],'name'=>['required','string','max:160'],'document_type'=>['required',Rule::in(['SALARY_CERTIFICATE','SERVICE_CERTIFICATE','BANK_LETTER','NOC'])],'language'=>['required',Rule::in(['EN','AR'])],'subject'=>['required','string','max:220'],'body_template'=>['required','string','max:12000'],'include_salary'=>['nullable','boolean']]);
        $template=HrDocumentTemplate::create([...$d,'organization_id'=>$request->user()->organization_id,'include_salary'=>$request->boolean('include_salary'),'status'=>'ACTIVE','created_by'=>$request->user()->id]);
        $audit->record('hr.documents.template.created',$template,[],$template->toArray());
        return back()->with('status','Document template created.');
    }

    public function decide(Request $request,HrServiceRequest $service,AuditService $audit): RedirectResponse
    {
        $d=$request->validate(['decision'=>['required',Rule::in(['APPROVED','REJECTED'])],'decision_notes'=>['nullable','string','max:2000']]);
        if($service->status!=='PENDING') throw ValidationException::withMessages(['request'=>'Only pending requests can be decided.']);
        if((int)$service->requested_by===(int)$request->user()->id) throw ValidationException::withMessages(['request'=>'Segregation of duties: requester cannot approve their own HR request.']);
        $before=$service->toArray();
        DB::transaction(function() use($service,$d,$request){
            if($d['decision']==='APPROVED' && $service->request_type==='PROFILE_UPDATE'){
                $employee=Employee::findOrFail($service->employee_id); $changes=array_intersect_key($service->requested_changes??[],array_flip(self::PROFILE_FIELDS));
                if($changes) $employee->update($changes);
            }
            $service->update(['status'=>$d['decision'],'decided_by'=>$request->user()->id,'decided_at'=>now(),'decision_notes'=>$d['decision_notes']??null]);
        });
        $audit->record('hr.documents.request.'.strtolower($d['decision']),$service,$before,$service->fresh()->toArray());
        return back()->with('status','HR request decision recorded.');
    }

    public function issue(Request $request,HrServiceRequest $service,AuditService $audit): RedirectResponse
    {
        if($service->status!=='APPROVED') throw ValidationException::withMessages(['request'=>'Only approved requests can be issued.']);
        if(in_array($service->request_type,['PROFILE_UPDATE','GENERAL'],true)) throw ValidationException::withMessages(['request'=>'This request type does not generate a formal letter.']);
        $this->ensureDefaults($request);
        $template=HrDocumentTemplate::where('document_type',$service->request_type)->where('language',$service->language)->where('status','ACTIVE')->latest()->firstOrFail();
        $employee=Employee::with('position')->findOrFail($service->employee_id);
        $documentNo='HRL-'.now()->format('Y').'-'.str_pad((string)$service->id,6,'0',STR_PAD_LEFT);
        $token=Str::random(64); $data=$this->letterData($employee,$service,$documentNo);
        $snapshot=['document_no'=>$documentNo,'request_no'=>$service->request_no,'template_code'=>$template->code,'language'=>$service->language,'subject'=>$this->render($template->subject,$data),'body'=>$this->render($template->body_template,$data),'employee'=>$data,'issued_at'=>now()->toIso8601String(),'issued_by'=>$request->user()->id];
        $before=$service->toArray();
        $service->update(['status'=>'ISSUED','template_id'=>$template->id,'document_no'=>$documentNo,'verification_token'=>$token,'snapshot'=>$snapshot,'issued_by'=>$request->user()->id,'issued_at'=>now()]);
        $audit->record('hr.documents.letter.issued',$service,$before,$service->fresh()->toArray());
        return redirect()->route('hr.documents.show',$service)->with('status','Official HR letter issued.');
    }

    public function show(Request $request,HrServiceRequest $service): View
    {
        $isHr=$this->mayReadHr($request);
        if(!$isHr){ $employee=$this->currentEmployee($request); abort_unless((int)$service->employee_id===(int)$employee->id,403); }
        $service->load('employee');
        return view('hr.documents.show',compact('service','isHr'));
    }

    public function printable(Request $request,HrServiceRequest $service): View
    {
        abort_unless($service->status==='ISSUED' && $service->snapshot,404);
        $isHr=$this->mayReadHr($request);
        if(!$isHr){ $employee=$this->currentEmployee($request); abort_unless((int)$service->employee_id===(int)$employee->id,403); }
        return view('hr.documents.print',['service'=>$service,'snapshot'=>$service->snapshot]);
    }

    private function nextRequestNo(int $tenantId): string
    {
        $next=(int)HrServiceRequest::withoutGlobalScopes()->where('tenant_id',$tenantId)->max('id')+1;
        return 'HRR-'.now()->format('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT);
    }

    private function letterData(Employee $e,HrServiceRequest $r,string $documentNo): array
    {
        $salary=(float)$e->basic_salary+(float)$e->housing_allowance+(float)$e->transport_allowance+(float)$e->other_allowances;
        return ['document_no'=>$documentNo,'date'=>now()->format('d M Y'),'employee_name'=>$e->name,'employee_no'=>$e->employee_no,'position'=>$e->position?->title??'—','department'=>$e->position?->department??'—','hire_date'=>$e->hire_date?->format('d M Y')??'—','nationality'=>$e->nationality??'—','national_id'=>$e->national_id??$e->iqama_no??'—','basic_salary'=>number_format((float)$e->basic_salary,2),'gross_salary'=>number_format($salary,2),'recipient'=>$r->recipient_name?:'To Whom It May Concern','purpose'=>$r->purpose?:'Official employment verification'];
    }

    private function render(string $text,array $data): string
    {
        $replace=[]; foreach($data as $k=>$v) $replace['{{'.$k.'}}']=(string)$v; return strtr($text,$replace);
    }

    private function ensureDefaults(Request $request): void
    {
        $defs=[
            ['SALARY_CERTIFICATE','EN','Salary Certificate','Salary Certificate','This is to certify that {{employee_name}} ({{employee_no}}) is employed as {{position}} in {{department}} since {{hire_date}}. Current basic salary is SAR {{basic_salary}} and total monthly compensation is SAR {{gross_salary}}. This certificate is issued to {{recipient}} for {{purpose}}.',true],
            ['SALARY_CERTIFICATE','AR','تعريف بالراتب','تعريف بالراتب','تشهد الشركة بأن الموظف/ة {{employee_name}} رقم {{employee_no}} يعمل بوظيفة {{position}} في {{department}} منذ {{hire_date}}. الراتب الأساسي الحالي {{basic_salary}} ريال سعودي وإجمالي التعويض الشهري {{gross_salary}} ريال سعودي. صدر هذا الخطاب إلى {{recipient}} لغرض {{purpose}}.',true],
            ['SERVICE_CERTIFICATE','EN','Service Certificate','Service Certificate','This is to certify that {{employee_name}} ({{employee_no}}) has been employed with the company since {{hire_date}} as {{position}} in {{department}}. This certificate is issued upon the employee request for {{purpose}}.',false],
            ['SERVICE_CERTIFICATE','AR','شهادة خدمة','شهادة خدمة','تشهد الشركة بأن الموظف/ة {{employee_name}} رقم {{employee_no}} يعمل لدى الشركة منذ {{hire_date}} بوظيفة {{position}} في {{department}}. أصدرت هذه الشهادة بناءً على طلب الموظف لغرض {{purpose}}.',false],
            ['BANK_LETTER','EN','Bank Letter','Employment / Salary Letter','Dear {{recipient}}, this letter confirms that {{employee_name}} ({{employee_no}}) is currently employed as {{position}}. Monthly compensation is SAR {{gross_salary}}. Issued on {{date}} for {{purpose}}.',true],
            ['BANK_LETTER','AR','خطاب بنك','خطاب تعريف للجهة المالية','السادة/ {{recipient}}، نفيدكم بأن الموظف/ة {{employee_name}} رقم {{employee_no}} على رأس العمل بوظيفة {{position}}، وإجمالي التعويض الشهري {{gross_salary}} ريال سعودي. صدر بتاريخ {{date}} لغرض {{purpose}}.',true],
            ['NOC','EN','No Objection Certificate','No Objection Certificate','To {{recipient}}: the company has no objection to {{employee_name}} ({{employee_no}}), {{position}}, proceeding with the stated purpose: {{purpose}}, subject to applicable company policies and regulations.',false],
            ['NOC','AR','شهادة عدم ممانعة','شهادة عدم ممانعة','إلى {{recipient}}: لا تمانع الشركة من قيام الموظف/ة {{employee_name}} رقم {{employee_no}}، {{position}}، بالإجراء الموضح للغرض التالي: {{purpose}}، وذلك مع الالتزام بسياسات الشركة والأنظمة المعمول بها.',false],
        ];
        foreach($defs as [$type,$lang,$name,$subject,$body,$salary]){
            $code='SYS-'.$type.'-'.$lang;
            HrDocumentTemplate::firstOrCreate(['code'=>$code],['organization_id'=>$request->user()->organization_id,'name'=>$name,'document_type'=>$type,'language'=>$lang,'subject'=>$subject,'body_template'=>$body,'include_salary'=>$salary,'status'=>'ACTIVE','created_by'=>$request->user()->id]);
        }
    }
}
