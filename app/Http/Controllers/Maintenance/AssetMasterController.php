<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\{Asset,AssetCategoryTemplate,AssetCommissioningRecord,AssetDocument,AssetLocation,Customer,CustomerSite};
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

    private function checker(Request $request): \App\Models\User
    {
        $user=$this->user($request);
        abort_unless(in_array($user->role,self::VERIFY_ROLES,true),403,'This role cannot approve asset governance actions.');
        return $user;
    }

    public function index(Request $request): View
    {
        $user=$this->user($request);
        $assets=Asset::with(['customer','site','location','parent','template'])->where('tenant_id',$user->tenant_id)->orderByDesc('id')->limit(120)->get();
        return view('maintenance.asset-master.index',[
            'assets'=>$assets,
            'customers'=>Customer::where('tenant_id',$user->tenant_id)->orderBy('name')->get(),
            'sites'=>CustomerSite::whereHas('customer',fn($q)=>$q->where('tenant_id',$user->tenant_id))->orderBy('name')->get(),
            'locations'=>AssetLocation::where('tenant_id',$user->tenant_id)->where('active',true)->with(['site','parent'])->orderBy('customer_site_id')->orderBy('location_type')->orderBy('name')->get(),
            'templates'=>AssetCategoryTemplate::where('tenant_id',$user->tenant_id)->where('active',true)->orderBy('category')->orderBy('asset_type')->get(),
            'criticalities'=>AssetMasterService::CRITICALITY,'ownershipTypes'=>AssetMasterService::OWNERSHIP,'strategies'=>AssetMasterService::STRATEGIES,
            'canVerify'=>in_array($user->role,self::VERIFY_ROLES,true),
        ]);
    }

    public function show(Request $request,Asset $asset): View
    {
        $user=$this->user($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $asset->load(['customer','site','location.parent','parent','children','template','documents','lifecycleEvents','commissioningRecords']);
        $workOrders=\App\Models\WorkOrder::where('asset_id',$asset->id)->latest()->limit(20)->get();
        $parts=\App\Models\AssetPartInstallation::where('asset_id',$asset->id)->latest()->limit(30)->get();
        $locations=AssetLocation::where('tenant_id',$user->tenant_id)->where('customer_id',$asset->customer_id)->where('customer_site_id',$asset->customer_site_id)->where('active',true)->orderBy('location_type')->orderBy('name')->get();
        return view('maintenance.asset-master.show',[
            'asset'=>$asset,'workOrders'=>$workOrders,'parts'=>$parts,'locations'=>$locations,
            'canVerify'=>in_array($user->role,self::VERIFY_ROLES,true),'lifecycleStatuses'=>AssetMasterService::LIFECYCLE,
        ]);
    }

    public function store(Request $request,AssetMasterService $service): RedirectResponse
    {
        $user=$this->user($request);
        $data=$this->validated($request,(int)$user->tenant_id);
        $asset=$service->create((int)$user->tenant_id,(int)$user->organization_id,(int)$user->id,$data);
        return redirect()->route('asset-master.show',$asset)->with('status','Asset '.$asset->asset_code.' created and sent for verification.');
    }

    public function update(Request $request,Asset $asset,AssetMasterService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $asset=$service->update($asset,$this->validated($request,(int)$user->tenant_id,$asset));
        return back()->with('status','Asset master updated. Completeness: '.$asset->data_completeness_score.'%.');
    }

    public function verify(Request $request,Asset $asset,AssetMasterService $service): RedirectResponse
    {
        $user=$this->checker($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['notes'=>['nullable','string','max:2000']]);
        $service->verify($asset,(int)$user->id,$data['notes']??null);
        return back()->with('status','Asset verified and activated.');
    }

    public function transition(Request $request,Asset $asset,AssetMasterService $service): RedirectResponse
    {
        $user=$this->checker($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['to_status'=>['required',Rule::in(AssetMasterService::LIFECYCLE)],'notes'=>['nullable','string','max:2000']]);
        $service->transition($asset,$data['to_status'],(int)$user->id,$data['notes']??null);
        return back()->with('status','Asset lifecycle moved to '.str_replace('_',' ',$data['to_status']).'.');
    }

    public function requestCommissioning(Request $request,Asset $asset,AssetMasterService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate([
            'inspection_date'=>['required','date'],'inspection_result'=>['required',Rule::in(['PASS','PASS_WITH_NOTES','FAIL'])],
            'checklist'=>['nullable','json'],'notes'=>['nullable','string','max:3000'],
        ]);
        if(isset($data['checklist'])) $data['checklist']=json_decode($data['checklist'],true);
        $service->requestCommissioning($asset,(int)$user->id,$data);
        return back()->with('status','Commissioning submitted for independent approval.');
    }

    public function reviewCommissioning(Request $request,Asset $asset,AssetCommissioningRecord $record,AssetMasterService $service): RedirectResponse
    {
        $user=$this->checker($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id && (int)$record->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['decision'=>['required',Rule::in(['APPROVE','REJECT'])],'notes'=>['nullable','string','max:3000']]);
        $service->reviewCommissioning($asset,$record,(int)$user->id,$data['decision']==='APPROVE',$data['notes']??null);
        return back()->with('status','Commissioning review '.($data['decision']==='APPROVE'?'approved':'rejected').'.');
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $user=$this->checker($request);
        $data=$request->validate([
            'customer_id'=>['required',Rule::exists('customers','id')->where(fn($q)=>$q->where('tenant_id',$user->tenant_id))],
            'customer_site_id'=>['required',Rule::exists('customer_sites','id')],'parent_id'=>['nullable','integer'],
            'location_type'=>['required',Rule::in(AssetMasterService::LOCATION_TYPES)],'code'=>['required','string','max:80'],'name'=>['required','string','max:160'],
            'description'=>['nullable','string','max:255'],'latitude'=>['nullable','numeric','between:-90,90'],'longitude'=>['nullable','numeric','between:-180,180'],
        ]);
        $site=CustomerSite::with('customer')->findOrFail($data['customer_site_id']);
        abort_unless($site->customer && (int)$site->customer->tenant_id===(int)$user->tenant_id && (int)$site->customer_id===(int)$data['customer_id'],422,'Selected site does not belong to the selected customer.');
        if(!empty($data['parent_id'])){
            $parent=AssetLocation::where('tenant_id',$user->tenant_id)->findOrFail($data['parent_id']);
            abort_unless((int)$parent->customer_site_id===(int)$data['customer_site_id'],422,'Parent location must belong to the same site.');
        }
        AssetLocation::create([...$data,'tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'active'=>true]);
        return back()->with('status','Asset location node created.');
    }

    public function assignLocation(Request $request,Asset $asset,AssetMasterService $service): RedirectResponse
    {
        $user=$this->user($request);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        $data=$request->validate(['asset_location_id'=>['required','integer']]);
        $location=AssetLocation::where('tenant_id',$user->tenant_id)->findOrFail($data['asset_location_id']);
        abort_unless((int)$location->customer_id===(int)$asset->customer_id && (int)$location->customer_site_id===(int)$asset->customer_site_id,422,'Location must belong to the same customer and site.');
        $service->update($asset,['asset_location_id'=>$location->id,'customer_id'=>$asset->customer_id,'customer_site_id'=>$asset->customer_site_id]);
        return back()->with('status','Asset assigned to '.$location->name.'.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $user=$this->checker($request);
        $data=$request->validate(['category'=>['required','string','max:100'],'asset_type'=>['required','string','max:120'],'name'=>['required','string','max:160'],'specification_schema'=>['nullable','json']]);
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
        $file=$request->file('file'); $path=$file->store('asset-master/'.$asset->id,'local');
        AssetDocument::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'asset_id'=>$asset->id,'document_type'=>$data['document_type'],'title'=>$data['title'],'path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'version'=>$data['version']??null,'issued_at'=>$data['issued_at']??null,'expires_at'=>$data['expires_at']??null,'uploaded_by'=>$user->id]);
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
            'customer_id'=>['required',Rule::exists('customers','id')->where(fn($q)=>$q->where('tenant_id',$tenantId))],'customer_site_id'=>['required',Rule::exists('customer_sites','id')],
            'parent_asset_id'=>['nullable',Rule::exists('assets','id')->where(fn($q)=>$q->where('tenant_id',$tenantId))],'asset_category_template_id'=>['nullable',Rule::exists('asset_category_templates','id')->where(fn($q)=>$q->where('tenant_id',$tenantId))],
            'asset_location_id'=>['nullable','integer'],'customer_asset_code'=>['nullable','string','max:100'],'name'=>['required','string','max:180'],'serial_no'=>['nullable','string','max:120'],
            'asset_category'=>['required','string','max:100'],'asset_subcategory'=>['nullable','string','max:120'],'asset_type'=>['required','string','max:120'],'manufacturer'=>['nullable','string','max:160'],'model_no'=>['nullable','string','max:120'],
            'criticality'=>['required',Rule::in(AssetMasterService::CRITICALITY)],'ownership_type'=>['required',Rule::in(AssetMasterService::OWNERSHIP)],'maintenance_strategy'=>['nullable',Rule::in(AssetMasterService::STRATEGIES)],
            'location_code'=>['nullable','string','max:80'],'building'=>['nullable','string','max:120'],'floor'=>['nullable','string','max:80'],'zone'=>['nullable','string','max:120'],'room'=>['nullable','string','max:120'],'physical_location'=>['nullable','string','max:255'],
            'latitude'=>['nullable','numeric','between:-90,90'],'longitude'=>['nullable','numeric','between:-180,180'],'manufacture_date'=>['nullable','date'],'installation_date'=>['nullable','date'],'commission_date'=>['nullable','date'],
            'warranty_start'=>['nullable','date'],'warranty_expiry'=>['nullable','date','after_or_equal:warranty_start'],'warranty_provider'=>['nullable','string','max:160'],'supplier_name'=>['nullable','string','max:160'],'installer_name'=>['nullable','string','max:160'],
            'useful_life_months'=>['nullable','integer','min:1','max:1200'],'expected_replacement_date'=>['nullable','date'],'replacement_value'=>['nullable','numeric','min:0'],'technical_specifications'=>['nullable','json'],'operational_status'=>['nullable','string','max:30'],
        ]);
        if(isset($data['technical_specifications'])) $data['technical_specifications']=json_decode($data['technical_specifications'],true);
        if($asset && !empty($data['parent_asset_id']) && (int)$data['parent_asset_id']===(int)$asset->id) abort(422,'An asset cannot be its own parent.');
        $site=CustomerSite::with('customer')->findOrFail($data['customer_site_id']);
        abort_unless($site->customer && (int)$site->customer->tenant_id===$tenantId && (int)$site->customer_id===(int)$data['customer_id'],422,'Selected site does not belong to the selected customer.');
        if(!empty($data['asset_location_id'])){
            $location=AssetLocation::where('tenant_id',$tenantId)->findOrFail($data['asset_location_id']);
            abort_unless((int)$location->customer_id===(int)$data['customer_id'] && (int)$location->customer_site_id===(int)$data['customer_site_id'],422,'Selected location does not belong to the selected customer/site.');
        }
        return $data;
    }
}
