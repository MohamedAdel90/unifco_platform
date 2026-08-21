<?php

namespace App\Http\Controllers\EAM;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Customer,CustomerSite,ServiceContract};
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request,Response};
use Illuminate\Support\Facades\{Auth,DB,Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        return view('eam.assets.index',[
            'assets'=>Asset::with(['customer','site','parent'])
                ->when($request->integer('customer_id'),fn($q,$id)=>$q->where('customer_id',$id))
                ->when($request->filled('operational_status'),fn($q)=>$q->where('operational_status',$request->string('operational_status')))
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
        ]);
    }

    public function edit(Asset $asset): View
    {
        return view('eam.assets.form',[
            'asset'=>$asset,
            'customers'=>Customer::orderBy('name')->get(),
            'sites'=>CustomerSite::orderBy('site_code')->get(),
            'parents'=>Asset::whereKeyNot($asset->id)->orderBy('asset_code')->get(),
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
        return view('eam.assets.show',compact('asset','contracts','assignments','specifications','documents','history'));
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$this->validated($request);
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
        return redirect()->route('eam.assets.show',$asset)->with('status','Asset registered and moved to verification.');
    }

    public function update(Request $request, Asset $asset, AuditService $audit): RedirectResponse
    {
        $data=$this->validated($request,$asset);
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

    public function downloadDocument(Asset $asset, int $document): Response
    {
        $doc=DB::table('asset_documents')->where('id',$document)->where('asset_id',$asset->id)->first();
        abort_unless($doc,404);
        return Storage::disk('public')->download($doc->file_path,$doc->original_name);
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
            'asset_code'=>['required','string','max:80',Rule::unique('assets')->where(fn($q)=>$q->where('tenant_id',Auth::user()->tenant_id))->ignore($asset?->id)],
            'name'=>['required','string','max:180'],'asset_category'=>['required','string','max:120'],'asset_subcategory'=>['nullable','string','max:120'],
            'manufacturer'=>['nullable','string','max:180'],'model_no'=>['nullable','string','max:180'],'serial_no'=>['nullable','string','max:180'],
            'criticality'=>['required','in:CRITICAL,HIGH,MEDIUM,LOW'],'lifecycle_status'=>['required','in:DRAFT,INSTALLED,COMMISSIONED,ACTIVE,INACTIVE,RETIRED,DISPOSED'],
            'operational_status'=>['required','in:RUNNING,STANDBY,STOPPED,UNDER_MAINTENANCE,BREAKDOWN,ISOLATED,OUT_OF_SERVICE'],
            'manufacture_date'=>['nullable','date'],'installation_date'=>['nullable','date'],'commission_date'=>['nullable','date'],'warranty_expiry'=>['nullable','date'],
            'useful_life_months'=>['nullable','integer','min:1'],'acquisition_cost'=>['nullable','numeric','min:0'],'replacement_value'=>['nullable','numeric','min:0'],
            'expected_replacement_date'=>['nullable','date'],'supplier_name'=>['nullable','string','max:180'],'installer_name'=>['nullable','string','max:180'],
        ]);
    }
}
