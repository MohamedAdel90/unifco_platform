<?php

namespace App\Http\Controllers\EAM;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Customer,CustomerSite,ServiceContract};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB,Storage};
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetMasterController extends Controller
{
    private function authorizeManager(): void
    {
        abort_unless(in_array(Auth::user()?->role,['ADMIN','MANAGER','SUPERVISOR'],true),403);
    }

    public function index(Request $request): View
    {
        $this->authorizeManager();
        $assets = Asset::with(['customer','site','parent'])
            ->when($request->integer('customer_id'),fn($q,$id)=>$q->where('customer_id',$id))
            ->when($request->filled('status'),fn($q)=>$q->where('operational_status',$request->string('status')))
            ->orderBy('asset_code')->paginate(30)->withQueryString();
        return view('eam.assets.index',[
            'assets'=>$assets,
            'customers'=>Customer::orderBy('name')->get(),
            'parents'=>Asset::orderBy('asset_code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager();
        $data=$request->validate([
            'customer_id'=>['required','integer','exists:customers,id'],'customer_site_id'=>['nullable','integer','exists:customer_sites,id'],'parent_asset_id'=>['nullable','integer','exists:assets,id'],
            'asset_code'=>['required','string','max:80','unique:assets,asset_code'],'name'=>['required','string','max:180'],'asset_category'=>['required','string','max:120'],'asset_subcategory'=>['nullable','string','max:120'],
            'manufacturer'=>['nullable','string','max:180'],'model_no'=>['nullable','string','max:180'],'serial_no'=>['nullable','string','max:180'],'criticality'=>['required','in:CRITICAL,HIGH,MEDIUM,LOW'],
            'lifecycle_status'=>['required','in:DRAFT,INSTALLED,COMMISSIONED,ACTIVE,INACTIVE,RETIRED,DISPOSED'],'operational_status'=>['required','in:RUNNING,STANDBY,STOPPED,UNDER_MAINTENANCE,BREAKDOWN,ISOLATED,OUT_OF_SERVICE'],
            'manufacture_date'=>['nullable','date'],'installation_date'=>['nullable','date'],'commission_date'=>['nullable','date'],'warranty_expiry'=>['nullable','date'],'useful_life_months'=>['nullable','integer','min:1'],
            'acquisition_cost'=>['nullable','numeric','min:0'],'replacement_value'=>['nullable','numeric','min:0'],'expected_replacement_date'=>['nullable','date'],'supplier_name'=>['nullable','string','max:180'],'installer_name'=>['nullable','string','max:180'],
        ]);
        $customer=Customer::findOrFail($data['customer_id']);
        if(!empty($data['customer_site_id'])) abort_unless(CustomerSite::whereKey($data['customer_site_id'])->where('customer_id',$customer->id)->exists(),422,'Selected site does not belong to this customer.');
        $asset=Asset::create($data+[
            'tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,'qr_token'=>(string)Str::uuid(),'verification_status'=>'UNDER_REVIEW','status'=>'ACTIVE'
        ]);
        DB::table('asset_status_history')->insert(['asset_id'=>$asset->id,'lifecycle_status'=>$asset->lifecycle_status,'operational_status'=>$asset->operational_status,'note'=>'Asset master created','changed_by'=>Auth::id(),'changed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        return redirect()->route('eam.assets.show',$asset)->with('status','Asset created and ready for verification.');
    }

    public function show(Asset $asset): View
    {
        $this->authorizeManager();
        $asset->load(['customer','site','parent','children']);
        $contracts=ServiceContract::where('customer_id',$asset->customer_id)->orderByDesc('starts_on')->get();
        $assignments=DB::table('asset_contract_assignments')->join('service_contracts','service_contracts.id','=','asset_contract_assignments.service_contract_id')
            ->where('asset_contract_assignments.asset_id',$asset->id)->select('asset_contract_assignments.*','service_contracts.contract_no','service_contracts.title')->orderByDesc('coverage_start')->get();
        $specifications=DB::table('asset_specifications')->where('asset_id',$asset->id)->orderBy('spec_label')->get();
        $documents=DB::table('asset_documents')->where('asset_id',$asset->id)->latest()->get();
        $history=DB::table('asset_status_history')->where('asset_id',$asset->id)->orderByDesc('changed_at')->get();
        return view('eam.assets.show',compact('asset','contracts','assignments','specifications','documents','history'));
    }

    public function status(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorizeManager();
        $data=$request->validate(['lifecycle_status'=>['required','in:DRAFT,INSTALLED,COMMISSIONED,ACTIVE,INACTIVE,RETIRED,DISPOSED'],'operational_status'=>['required','in:RUNNING,STANDBY,STOPPED,UNDER_MAINTENANCE,BREAKDOWN,ISOLATED,OUT_OF_SERVICE'],'verification_status'=>['required','in:DRAFT,UNDER_REVIEW,VERIFIED'],'note'=>['nullable','string','max:2000']]);
        $asset->update($data);
        DB::table('asset_status_history')->insert(['asset_id'=>$asset->id,'lifecycle_status'=>$data['lifecycle_status'],'operational_status'=>$data['operational_status'],'note'=>$data['note']??null,'changed_by'=>Auth::id(),'changed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('status','Asset status updated.');
    }

    public function assignContract(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorizeManager();
        $data=$request->validate(['service_contract_id'=>['required','integer','exists:service_contracts,id'],'coverage_start'=>['required','date'],'coverage_end'=>['nullable','date','after_or_equal:coverage_start'],'scope_type'=>['required','in:FULL_MAINTENANCE,PREVENTIVE_ONLY,CORRECTIVE_ONLY,PARTS_ONLY,LABOUR_ONLY'],'labour_included'=>['nullable','boolean'],'parts_included'=>['nullable','boolean'],'emergency_included'=>['nullable','boolean'],'response_minutes'=>['nullable','integer','min:1'],'resolution_minutes'=>['nullable','integer','min:1'],'exclusions'=>['nullable','string','max:4000']]);
        abort_unless(ServiceContract::whereKey($data['service_contract_id'])->where('customer_id',$asset->customer_id)->exists(),422,'Contract does not belong to the asset customer.');
        DB::table('asset_contract_assignments')->insert($data+['asset_id'=>$asset->id,'labour_included'=>(bool)($data['labour_included']??false),'parts_included'=>(bool)($data['parts_included']??false),'emergency_included'=>(bool)($data['emergency_included']??false),'status'=>'ACTIVE','created_at'=>now(),'updated_at'=>now()]);
        return back()->with('status','Contract coverage assigned to asset.');
    }

    public function addSpecification(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorizeManager();
        $data=$request->validate(['spec_key'=>['required','alpha_dash','max:120'],'spec_label'=>['required','string','max:180'],'spec_value'=>['nullable','string','max:2000'],'unit'=>['nullable','string','max:40']]);
        DB::table('asset_specifications')->updateOrInsert(['asset_id'=>$asset->id,'spec_key'=>$data['spec_key']],$data+['asset_id'=>$asset->id,'updated_at'=>now(),'created_at'=>now()]);
        return back()->with('status','Specification saved.');
    }

    public function uploadDocument(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorizeManager();
        $data=$request->validate(['document_type'=>['required','in:OM_MANUAL,DATASHEET,WARRANTY,INSTALLATION_REPORT,COMMISSIONING_REPORT,DRAWING,CERTIFICATE,PHOTO,OTHER'],'title'=>['required','string','max:255'],'file'=>['required','file','max:15360']]);
        $path=$request->file('file')->store('asset-documents/'.$asset->id,'public');
        DB::table('asset_documents')->insert(['asset_id'=>$asset->id,'document_type'=>$data['document_type'],'title'=>$data['title'],'file_path'=>$path,'original_name'=>$request->file('file')->getClientOriginalName(),'uploaded_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('status','Asset document uploaded.');
    }
}
