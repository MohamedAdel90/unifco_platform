<?php

namespace App\Http\Controllers\EAM;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Customer,CustomerSite,MaintenancePlan,ServiceContract};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB,Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        return view('eam.assets.index',[
            'assets'=>Asset::with(['customer','site','parent'])
                ->when($request->integer('customer_id'),fn($q,$id)=>$q->where('customer_id',$id))
                ->when($request->filled('operational_status'),fn($q)=>$q->where('operational_status',(string)$request->string('operational_status')))
                ->orderBy('asset_code')->paginate(25)->withQueryString(),
            'customers'=>Customer::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('eam.assets.form',[
            'asset'=>new Asset(),
            'customers'=>Customer::orderBy('name')->get(),
            'sites'=>CustomerSite::orderBy('site_code')->get(),
            'parents'=>Asset::orderBy('asset_code')->get(),
            'templates'=>$this->templatesWithFields(),
        ]);
    }

    public function edit(Asset $asset): View
    {
        return view('eam.assets.form',[
            'asset'=>$asset,
            'customers'=>Customer::orderBy('name')->get(),
            'sites'=>CustomerSite::orderBy('site_code')->get(),
            'parents'=>Asset::whereKeyNot($asset->id)->orderBy('asset_code')->get(),
            'templates'=>$this->templatesWithFields(),
        ]);
    }

    public function show(Asset $asset): View
    {
        $asset->load(['customer','site','parent','children']);
        $contracts=ServiceContract::where('customer_id',$asset->customer_id)->orderByDesc('starts_on')->get();
        $assignments=DB::table('asset_contract_assignments')
            ->join('service_contracts','service_contracts.id','=','asset_contract_assignments.service_contract_id')
            ->where('asset_contract_assignments.asset_id',$asset->id)
            ->select('asset_contract_assignments.*','service_contracts.contract_no','service_contracts.title')
            ->orderByDesc('coverage_start')->get();
        $specifications=DB::table('asset_specifications')->where('asset_id',$asset->id)->orderBy('spec_label')->get();
        $documents=DB::table('asset_documents')->where('asset_id',$asset->id)->latest()->get();
        $history=DB::table('asset_status_history')->where('asset_id',$asset->id)->orderByDesc('changed_at')->get();
        $template = $asset->asset_category_template_id
            ? DB::table('asset_category_templates')->where('id',$asset->asset_category_template_id)->first()
            : null;
        $templateFields = $template
            ? DB::table('asset_category_template_fields')->where('asset_category_template_id',$template->id)->orderBy('sort_order')->get()
            : collect();
        $specByKey = $specifications->keyBy('spec_key');
        $requiredTemplateFields = $templateFields->where('is_required',1);
        $completedRequiredFields = $requiredTemplateFields->filter(fn($f)=>filled($specByKey->get($f->field_key)?->spec_value));
        $specCompletion = $requiredTemplateFields->count() ? (int) round(($completedRequiredFields->count() / $requiredTemplateFields->count()) * 100) : 100;
        $plans = MaintenancePlan::where('asset_id',$asset->id)->orderBy('plan_no')->get();
        $planTasks = DB::table('maintenance_plan_tasks')->whereIn('maintenance_plan_id',$plans->pluck('id'))->orderBy('sort_order')->get()->groupBy('maintenance_plan_id');

        return view('eam.assets.show',compact(
            'asset','contracts','assignments','specifications','documents','history','template','templateFields','specByKey','specCompletion','plans','planTasks'
        ));
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$this->validated($request);
        $this->applyTemplateDefaults($data);
        $this->validateOwnership($data);
        $asset=Asset::create($data+[
            'organization_id'=>Auth::user()->organization_id,
            'qr_token'=>(string)Str::uuid(),
            'verification_status'=>'UNDER_REVIEW',
            'status'=>'REGISTERED',
        ]);
        DB::table('asset_status_history')->insert([
            'asset_id'=>$asset->id,'lifecycle_status'=>$asset->lifecycle_status,'operational_status'=>$asset->operational_status,
            'note'=>'Asset registered','changed_by'=>Auth::id(),'changed_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $audit->record('eam.asset.registered',$asset,[],$asset->toArray());
        return redirect()->route('eam.assets.show',$asset)->with('status','Asset registered and moved to verification. Complete the required template specifications next.');
    }

    public function update(Request $request, Asset $asset, AuditService $audit): RedirectResponse
    {
        $data=$this->validated($request,$asset);
        $this->applyTemplateDefaults($data,false);
        $this->validateOwnership($data);
        $before=$asset->toArray();
        if (! $asset->qr_token) $data['qr_token']=(string)Str::uuid();
        $asset->update($data);
        $audit->record('eam.asset.updated',$asset,$before,$asset->fresh()->toArray());
        return redirect()->route('eam.assets.show',$asset)->with('status','Asset master data updated.');
    }

    public function capitalize(Asset $asset, AuditService $audit): RedirectResponse
    {
        if ($asset->status !== 'REGISTERED') throw ValidationException::withMessages(['asset'=>'Only REGISTERED assets can be capitalized.']);
        if (! $asset->commission_date) throw ValidationException::withMessages(['asset'=>'Commission date is required before capitalization.']);
        if ((float)$asset->acquisition_cost <= 0) throw ValidationException::withMessages(['asset'=>'Acquisition cost must be positive before capitalization.']);
        $before=$asset->toArray(); $asset->update(['status'=>'CAPITALIZED']);
        $audit->record('eam.asset.capitalized',$asset,$before,$asset->fresh()->toArray());
        return back()->with('status','Asset capitalized.');
    }

    public function updateStatus(Request $request, Asset $asset, AuditService $audit): RedirectResponse
    {
        $data=$request->validate([
            'lifecycle_status'=>['required','in:DRAFT,INSTALLED,COMMISSIONED,ACTIVE,INACTIVE,RETIRED,DISPOSED'],
            'operational_status'=>['required','in:RUNNING,STANDBY,STOPPED,UNDER_MAINTENANCE,BREAKDOWN,ISOLATED,OUT_OF_SERVICE'],
            'verification_status'=>['required','in:DRAFT,UNDER_REVIEW,VERIFIED'],
            'note'=>['nullable','string','max:2000'],
        ]);
        $before=$asset->toArray();
        $asset->update(collect($data)->only(['lifecycle_status','operational_status','verification_status'])->all());
        DB::table('asset_status_history')->insert([
            'asset_id'=>$asset->id,'lifecycle_status'=>$data['lifecycle_status'],'operational_status'=>$data['operational_status'],
            'note'=>$data['note']??null,'changed_by'=>Auth::id(),'changed_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $audit->record('eam.asset.status_updated',$asset,$before,$asset->fresh()->toArray());
        return back()->with('status','Asset lifecycle and operating status updated.');
    }

    public function assignContract(Request $request, Asset $asset): RedirectResponse
    {
        $data=$request->validate([
            'service_contract_id'=>['required','integer','exists:service_contracts,id'],'coverage_start'=>['required','date'],'coverage_end'=>['nullable','date','after_or_equal:coverage_start'],
            'scope_type'=>['required','in:FULL_MAINTENANCE,PREVENTIVE_ONLY,CORRECTIVE_ONLY,PARTS_ONLY,LABOUR_ONLY'],
            'labour_included'=>['nullable','boolean'],'parts_included'=>['nullable','boolean'],'emergency_included'=>['nullable','boolean'],
            'response_minutes'=>['nullable','integer','min:1'],'resolution_minutes'=>['nullable','integer','min:1'],'exclusions'=>['nullable','string','max:4000'],
        ]);
        abort_unless(ServiceContract::whereKey($data['service_contract_id'])->where('customer_id',$asset->customer_id)->exists(),422,'Contract does not belong to this customer.');
        DB::table('asset_contract_assignments')->insert([
            'asset_id'=>$asset->id,'service_contract_id'=>$data['service_contract_id'],'coverage_start'=>$data['coverage_start'],'coverage_end'=>$data['coverage_end']??null,
            'scope_type'=>$data['scope_type'],'labour_included'=>$request->boolean('labour_included'),'parts_included'=>$request->boolean('parts_included'),
            'emergency_included'=>$request->boolean('emergency_included'),'response_minutes'=>$data['response_minutes']??null,'resolution_minutes'=>$data['resolution_minutes']??null,
            'exclusions'=>$data['exclusions']??null,'status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('status','Contract coverage assigned to asset.');
    }

    public function storeSpecification(Request $request, Asset $asset): RedirectResponse
    {
        $data=$request->validate(['spec_key'=>['required','alpha_dash','max:120'],'spec_label'=>['required','string','max:180'],'spec_value'=>['nullable','string','max:2000'],'unit'=>['nullable','string','max:40']]);
        DB::table('asset_specifications')->updateOrInsert(
            ['asset_id'=>$asset->id,'spec_key'=>$data['spec_key']],
            ['asset_id'=>$asset->id,'spec_key'=>$data['spec_key'],'spec_label'=>$data['spec_label'],'spec_value'=>$data['spec_value']??null,'unit'=>$data['unit']??null,'updated_at'=>now(),'created_at'=>now()]
        );
        return back()->with('status','Asset specification saved.');
    }

    public function storeTemplateSpecifications(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless($asset->asset_category_template_id,422,'Assign an asset category template first.');
        $fields=DB::table('asset_category_template_fields')->where('asset_category_template_id',$asset->asset_category_template_id)->get();
        $input=(array)$request->input('spec',[]);
        foreach($fields as $field){
            $value=$input[$field->field_key]??null;
            if($field->is_required && blank($value)) throw ValidationException::withMessages(['spec.'.$field->field_key=>$field->label.' is required.']);
            DB::table('asset_specifications')->updateOrInsert(
                ['asset_id'=>$asset->id,'spec_key'=>$field->field_key],
                ['asset_id'=>$asset->id,'spec_key'=>$field->field_key,'spec_label'=>$field->label,'spec_value'=>$value,'unit'=>$field->unit,'updated_at'=>now(),'created_at'=>now()]
            );
        }
        return back()->with('status','Template specifications saved.');
    }

    public function storeDocument(Request $request, Asset $asset): RedirectResponse
    {
        $data=$request->validate([
            'document_type'=>['required','in:OM_MANUAL,DATASHEET,WARRANTY,INSTALLATION_REPORT,COMMISSIONING_REPORT,DRAWING,CERTIFICATE,PHOTO,OTHER'],
            'title'=>['required','string','max:255'],'file'=>['required','file','max:15360'],
        ]);
        $path=$request->file('file')->store('asset-documents/'.$asset->id,'public');
        DB::table('asset_documents')->insert([
            'asset_id'=>$asset->id,'document_type'=>$data['document_type'],'title'=>$data['title'],'file_path'=>$path,
            'original_name'=>$request->file('file')->getClientOriginalName(),'uploaded_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('status','Asset document uploaded.');
    }

    public function downloadDocument(Asset $asset, int $document): StreamedResponse
    {
        $doc=DB::table('asset_documents')->where('id',$document)->where('asset_id',$asset->id)->first();
        abort_unless($doc,404);
        return Storage::disk('public')->download($doc->file_path,$doc->original_name);
    }

    public function storeMaintenancePlan(Request $request, Asset $asset): RedirectResponse
    {
        $data=$request->validate([
            'plan_no'=>['required','string','max:50',Rule::unique('maintenance_plans','plan_no')->where(fn($q)=>$q->where('tenant_id',Auth::user()->tenant_id))],
            'name'=>['required','string','max:160'],'service_contract_id'=>['nullable','integer','exists:service_contracts,id'],
            'maintenance_strategy'=>['required','in:PREVENTIVE,PREDICTIVE,CONDITION_BASED,INSPECTION,STATUTORY'],
            'frequency_type'=>['required','in:DAYS,WEEKS,MONTHS,YEARS,METER'],'frequency_value'=>['required','integer','min:1'],
            'next_due_date'=>['nullable','date'],'next_due_meter'=>['nullable','numeric','min:0'],'priority'=>['required','in:LOW,NORMAL,HIGH,CRITICAL'],
            'estimated_duration_minutes'=>['nullable','integer','min:1'],'meter_type'=>['nullable','string','max:60'],'required_skill'=>['nullable','string','max:120'],
            'safety_instructions'=>['nullable','string','max:4000'],'lead_days'=>['nullable','integer','min:0','max:365'],'auto_generate_work_orders'=>['nullable','boolean'],
        ]);
        if(!empty($data['service_contract_id'])) abort_unless(ServiceContract::whereKey($data['service_contract_id'])->where('customer_id',$asset->customer_id)->exists(),422,'Maintenance contract does not belong to this customer.');
        MaintenancePlan::create($data+[
            'tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,'asset_id'=>$asset->id,
            'auto_generate_work_orders'=>$request->boolean('auto_generate_work_orders',true),'status'=>'ACTIVE'
        ]);
        return back()->with('status','Preventive maintenance plan created. Add checklist tasks next.');
    }

    public function storeMaintenanceTask(Request $request, Asset $asset, MaintenancePlan $plan): RedirectResponse
    {
        abort_unless($plan->asset_id===$asset->id,404);
        $data=$request->validate([
            'sort_order'=>['nullable','integer','min:1'],'task_code'=>['nullable','string','max:60'],'task_title'=>['required','string','max:255'],
            'instructions'=>['nullable','string','max:4000'],'response_type'=>['required','in:PASS_FAIL,NUMBER,TEXT,PHOTO'],'unit'=>['nullable','string','max:30'],
            'min_value'=>['nullable','numeric'],'max_value'=>['nullable','numeric'],'photo_required'=>['nullable','boolean'],'required'=>['nullable','boolean'],
        ]);
        DB::table('maintenance_plan_tasks')->insert([
            'maintenance_plan_id'=>$plan->id,'sort_order'=>$data['sort_order']??100,'task_code'=>$data['task_code']??null,'task_title'=>$data['task_title'],
            'instructions'=>$data['instructions']??null,'response_type'=>$data['response_type'],'unit'=>$data['unit']??null,'min_value'=>$data['min_value']??null,'max_value'=>$data['max_value']??null,
            'photo_required'=>$request->boolean('photo_required'),'required'=>$request->boolean('required',true),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('status','Maintenance checklist task added.');
    }

    private function validateOwnership(array $data): void
    {
        if (!empty($data['customer_site_id'])) {
            abort_unless(CustomerSite::whereKey($data['customer_site_id'])->where('customer_id',$data['customer_id'])->exists(),422,'Selected site does not belong to selected customer.');
        }
        if (!empty($data['parent_asset_id'])) {
            abort_unless(Asset::whereKey($data['parent_asset_id'])->where('customer_id',$data['customer_id'])->exists(),422,'Parent asset must belong to the same customer.');
        }
    }

    private function validated(Request $request, ?Asset $asset=null): array
    {
        return $request->validate([
            'customer_id'=>['required','integer','exists:customers,id'],'customer_site_id'=>['nullable','integer','exists:customer_sites,id'],'parent_asset_id'=>['nullable','integer','exists:assets,id'],
            'asset_category_template_id'=>['nullable','integer','exists:asset_category_templates,id'],
            'asset_code'=>['required','string','max:80',Rule::unique('assets')->where(fn($q)=>$q->where('tenant_id',Auth::user()->tenant_id))->ignore($asset?->id)],
            'name'=>['required','string','max:180'],'asset_category'=>['nullable','string','max:120'],'asset_subcategory'=>['nullable','string','max:120'],
            'manufacturer'=>['nullable','string','max:180'],'model_no'=>['nullable','string','max:180'],'serial_no'=>['nullable','string','max:180'],
            'criticality'=>['required','in:CRITICAL,HIGH,MEDIUM,LOW'],'lifecycle_status'=>['required','in:DRAFT,INSTALLED,COMMISSIONED,ACTIVE,INACTIVE,RETIRED,DISPOSED'],
            'operational_status'=>['required','in:RUNNING,STANDBY,STOPPED,UNDER_MAINTENANCE,BREAKDOWN,ISOLATED,OUT_OF_SERVICE'],
            'manufacture_date'=>['nullable','date'],'installation_date'=>['nullable','date'],'commission_date'=>['nullable','date'],'warranty_expiry'=>['nullable','date'],
            'useful_life_months'=>['nullable','integer','min:1'],'acquisition_cost'=>['nullable','numeric','min:0'],'replacement_value'=>['nullable','numeric','min:0'],
            'expected_replacement_date'=>['nullable','date'],'supplier_name'=>['nullable','string','max:180'],'installer_name'=>['nullable','string','max:180'],
        ]);
    }

    private function applyTemplateDefaults(array &$data, bool $fillDefaults=true): void
    {
        if(empty($data['asset_category_template_id'])) return;
        $template=DB::table('asset_category_templates')->where('id',$data['asset_category_template_id'])->where('status','ACTIVE')->first();
        if(!$template) return;
        $data['asset_category']=$template->name;
        if($fillDefaults || empty($data['criticality'])) $data['criticality']=$template->default_criticality;
        if(($fillDefaults || empty($data['useful_life_months'])) && $template->default_useful_life_months) $data['useful_life_months']=$template->default_useful_life_months;
    }

    private function templatesWithFields(): array
    {
        $templates=DB::table('asset_category_templates')->where('status','ACTIVE')->orderBy('system_group')->orderBy('name')->get();
        $fields=DB::table('asset_category_template_fields')->orderBy('sort_order')->get()->groupBy('asset_category_template_id');
        return $templates->map(fn($t)=>[
            'id'=>$t->id,'code'=>$t->code,'name'=>$t->name,'system_group'=>$t->system_group,'criticality'=>$t->default_criticality,
            'useful_life_months'=>$t->default_useful_life_months,'meter_based_supported'=>(bool)$t->meter_based_supported,'meter_unit'=>$t->default_meter_unit,
            'fields'=>($fields[$t->id]??collect())->values()->map(fn($f)=>['key'=>$f->field_key,'label'=>$f->label,'type'=>$f->data_type,'unit'=>$f->unit,'required'=>(bool)$f->is_required])->all(),
        ])->all();
    }
}
