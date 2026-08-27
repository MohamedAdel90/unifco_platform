<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\{Asset,AssetCategoryTemplate,AssetDocument,Customer,CustomerSite};
use App\Services\AssetMasterService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetMasterController extends Controller
{
    private const ROLES=['ADMIN','MAINTENANCE_MANAGER','MAINTENANCE_ENGINEER','PROJECT_MANAGER'];
    private const VERIFY_ROLES=['ADMIN','MAINTENANCE_MANAGER'];

    private function user(Request $request): \App\Models\User
    {
        $user=$request->user();
        abort_unless($user && in_array($user->role,self::ROLES,true),403,'This role cannot manage customer assets.');
        return $user;
    }

    public function index(Request $request): View
    {
        $user=$this->user($request);
        $assets=Asset::with(['customer','site','parent','template'])->where('tenant_id',$user->tenant_id)->orderByDesc('id')->limit(120)->get();
        return view('maintenance.asset-master.index',[
            'assets'=>$assets,
            'customers'=>Customer::where('tenant_id',$user->tenant_id)->orderBy('name')->get(),
            'sites'=>CustomerSite::where('tenant_id',$user->tenant_id)->orderBy('name')->get(),
            'templates'=>AssetCategoryTemplate::where('tenant_id',$user->tenant_id)->where('active',true)->orderBy('category')->orderBy('asset_type')->get(),
            'criticalities'=>AssetMasterService::CRITICALITY,'ownershipTypes'=>AssetMasterService::OWNERSHIP,'strategies'=>AssetMasterService::STRATEGIES,
            'canVerify'=>in_array($user->role,self::VERIFY_ROLES,true),
        ]);
    }

    public function show(Request $request,Asset $asset): View
    {
        $user=$this->user($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $asset->load(['customer','site','parent','children','template','documents']);
        $workOrders=\App\Models\WorkOrder::where('asset_id',$asset->id)->latest()->limit(20)->get();
        $parts=\App\Models\AssetPartInstallation::where('asset_id',$asset->id)->latest()->limit(30)->get();
        return view('maintenance.asset-master.show',['asset'=>$asset,'workOrders'=>$workOrders,'parts'=>$parts,'canVerify'=>in_array($user->role,self::VERIFY_ROLES,true)]);
    }

    public function store(Request $request,AssetMasterService $service): RedirectResponse
    {
        $user=$this->user($request);
        $data=$this->validated($request,$user->tenant_id);
        $asset=$service->create((int)$user->tenant_id,(int)$user->organization_id,(int)$user->id,$data);
        return redirect()->route('asset-master.show',$asset)->with('status','Asset '.$asset->asset_code.' created and sent for verification.');
    }

    public function update(Request $request,Asset $asset,AssetMasterService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $asset=$service->update($asset,$this->validated($request,$user->tenant_id,$asset));
        return back()->with('status','Asset master updated. Completeness: '.$asset->data_completeness_score.'%.');
    }

    public function verify(Request $request,Asset $asset,AssetMasterService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless(in_array($user->role,self::VERIFY_ROLES,true),403);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['notes'=>['nullable','string','max:2000']]);
        $service->verify($asset,(int)$user->id,$data['notes']??null);
        return back()->with('status','Asset verified and activated.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless(in_array($user->role,self::VERIFY_ROLES,true),403);
        $data=$request->validate([
            'category'=>['required','string','max:100'],'asset_type'=>['required','string','max:120'],'name'=>['required','string','max:160'],
            'specification_schema'=>['nullable','json'],
        ]);
        AssetCategoryTemplate::updateOrCreate(
            ['tenant_id'=>$user->tenant_id,'category'=>$data['category'],'asset_type'=>$data['asset_type']],
            ['organization_id'=>$user->organization_id,'name'=>$data['name'],'specification_schema'=>isset($data['specification_schema'])?json_decode($data['specification_schema'],true):null,'active'=>true]
        );
        return back()->with('status','Asset specification template saved.');
    }

    public function document(Request $request,Asset $asset): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate([
            'document_type'=>['required',Rule::in(['PRIMARY_PHOTO','NAMEPLATE_PHOTO','PHOTO','DATASHEET','USER_MANUAL','MAINTENANCE_MANUAL','COMMISSIONING_REPORT','WARRANTY_CERTIFICATE','INSPECTION_CERTIFICATE','CALIBRATION_CERTIFICATE','DRAWING','TEST_REPORT','OTHER'])],
            'title'=>['required','string','max:180'],'file'=>['required','file','max:15360'],'version'=>['nullable','string','max:30'],'issued_at'=>['nullable','date'],'expires_at'=>['nullable','date'],
        ]);
        $file=$request->file('file');
        $path=$file->store('asset-master/'.$asset->id,'local');
        AssetDocument::create([
            'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'asset_id'=>$asset->id,'document_type'=>$data['document_type'],'title'=>$data['title'],
            'path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'version'=>$data['version']??null,'issued_at'=>$data['issued_at']??null,
            'expires_at'=>$data['expires_at']??null,'uploaded_by'=>$user->id,
        ]);
        return back()->with('status','Asset document uploaded.');
    }

    public function download(Request $request,AssetDocument $document)
    {
        $user=$this->user($request);
        abort_unless((int)$document->tenant_id===(int)$user->tenant_id,404);
        abort_unless(Storage::disk('local')->exists($document->path),404);
        return Storage::disk('local')->download($document->path,$document->original_name);
    }

    private function validated(Request $request,int $tenantId,?Asset $asset=null): array
    {
        $data=$request->validate([
            'customer_id'=>['required',Rule::exists('customers','id')->where(fn($q)=>$q->where('tenant_id',$tenantId))],
            'customer_site_id'=>['required',Rule::exists('customer_sites','id')->where(fn($q)=>$q->where('tenant_id',$tenantId))],
            'parent_asset_id'=>['nullable',Rule::exists('assets','id')->where(fn($q)=>$q->where('tenant_id',$tenantId))],
            'asset_category_template_id'=>['nullable',Rule::exists('asset_category_templates','id')->where(fn($q)=>$q->where('tenant_id',$tenantId))],
            'customer_asset_code'=>['nullable','string','max:100'],'name'=>['required','string','max:180'],'serial_no'=>['nullable','string','max:120'],
            'asset_category'=>['required','string','max:100'],'asset_subcategory'=>['nullable','string','max:120'],'asset_type'=>['required','string','max:120'],
            'manufacturer'=>['nullable','string','max:160'],'model_no'=>['nullable','string','max:120'],'criticality'=>['required',Rule::in(AssetMasterService::CRITICALITY)],
            'ownership_type'=>['required',Rule::in(AssetMasterService::OWNERSHIP)],'maintenance_strategy'=>['nullable',Rule::in(AssetMasterService::STRATEGIES)],
            'location_code'=>['nullable','string','max:80'],'building'=>['nullable','string','max:120'],'floor'=>['nullable','string','max:80'],'zone'=>['nullable','string','max:120'],
            'room'=>['nullable','string','max:120'],'physical_location'=>['nullable','string','max:255'],'latitude'=>['nullable','numeric','between:-90,90'],'longitude'=>['nullable','numeric','between:-180,180'],
            'manufacture_date'=>['nullable','date'],'installation_date'=>['nullable','date'],'commission_date'=>['nullable','date'],'warranty_start'=>['nullable','date'],
            'warranty_expiry'=>['nullable','date','after_or_equal:warranty_start'],'warranty_provider'=>['nullable','string','max:160'],'supplier_name'=>['nullable','string','max:160'],'installer_name'=>['nullable','string','max:160'],
            'useful_life_months'=>['nullable','integer','min:1','max:1200'],'expected_replacement_date'=>['nullable','date'],'replacement_value'=>['nullable','numeric','min:0'],
            'technical_specifications'=>['nullable','json'],'operational_status'=>['nullable','string','max:30'],
        ]);
        if(isset($data['technical_specifications'])) $data['technical_specifications']=json_decode($data['technical_specifications'],true);
        if($asset && !empty($data['parent_asset_id']) && (int)$data['parent_asset_id']===(int)$asset->id) abort(422,'An asset cannot be its own parent.');
        $site=CustomerSite::where('tenant_id',$tenantId)->findOrFail($data['customer_site_id']);
        abort_unless((int)$site->customer_id===(int)$data['customer_id'],422,'Selected site does not belong to the selected customer.');
        return $data;
    }
}
