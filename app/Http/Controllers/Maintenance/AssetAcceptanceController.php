<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\{Asset,AssetDocument,AssetLifecycleEvent,User};
use App\Services\AssetAcceptanceContractService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\Rule;

class AssetAcceptanceController extends Controller
{
    private const ROLES=['ADMIN','MAINTENANCE_MANAGER','MAINTENANCE_ENGINEER','PROJECT_MANAGER'];

    private function actor(Request $request,Asset $asset): User
    {
        $user=$request->user();
        abort_unless($user && in_array($user->role,self::ROLES,true),403);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        return $user;
    }

    public function updateProfile(Request $request,Asset $asset,AssetAcceptanceContractService $service): RedirectResponse
    {
        $user=$this->actor($request,$asset);
        $data=$request->validate([
            'manufacturer_asset_number'=>['nullable','string','max:120'],'room_code'=>['nullable','string','max:80'],
            'purchase_date'=>['nullable','date'],'supplier_name'=>['nullable','string','max:160'],'po_number'=>['nullable','string','max:120'],'purchase_value'=>['nullable','numeric','min:0'],
            'warranty_provider'=>['nullable','string','max:160'],'warranty_start'=>['nullable','date'],'warranty_expiry'=>['nullable','date','after_or_equal:warranty_start'],'warranty_terms'=>['nullable','string','max:5000'],
            'expected_replacement_date'=>['nullable','date'],'replacement_target_date'=>['nullable','date'],'replacement_cost_estimate'=>['nullable','numeric','min:0'],
            'operating_hours'=>['nullable','numeric','min:0'],'meter_unit'=>['nullable','string','max:30'],'design_capacity'=>['nullable','numeric','min:0'],'current_load'=>['nullable','numeric','min:0'],'failure_impact'=>['nullable',Rule::in(['LOW','MEDIUM','HIGH','CRITICAL'])],
            'impact_safety'=>['nullable','integer','between:1,5'],'impact_operation'=>['nullable','integer','between:1,5'],'impact_financial'=>['nullable','integer','between:1,5'],'impact_customer'=>['nullable','integer','between:1,5'],'impact_environmental'=>['nullable','integer','between:1,5],
            'probability_failure'=>['nullable','integer','between:1,5'],'probability_condition'=>['nullable','integer','between:1,5'],'probability_age'=>['nullable','integer','between:1,5'],
        ]);
        $asset->update($data);
        $service->recalculateCriticality($asset->fresh());
        $service->recalculateHealth($asset->fresh());
        AssetLifecycleEvent::create(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'event_type'=>'ASSET_PROFILE_UPDATED','from_status'=>$asset->lifecycle_status,'to_status'=>$asset->lifecycle_status,'title'=>'Asset acceptance profile updated','metadata'=>['fields'=>array_keys($data)],'performed_by'=>$user->id,'performed_at'=>now()]);
        return back()->with('status','Asset acceptance profile updated and intelligence recalculated.');
    }

    public function document(Request $request,Asset $asset): RedirectResponse
    {
        $user=$this->actor($request,$asset);
        $types=['PRIMARY_PHOTO','NAMEPLATE_PHOTO','FRONT_PHOTO','REAR_PHOTO','ELECTRICAL_PANEL_PHOTO','INSTALLATION_ENVIRONMENT_PHOTO','DAMAGE_PHOTO','BEFORE_MAINTENANCE_PHOTO','AFTER_MAINTENANCE_PHOTO','ASSET_PHOTO','DATASHEET','USER_MANUAL','MAINTENANCE_MANUAL','COMMISSIONING_REPORT','WARRANTY_CERTIFICATE','PURCHASE_DOCUMENT','INSPECTION_CERTIFICATE','CALIBRATION_CERTIFICATE','DRAWING','TEST_REPORT'];
        $data=$request->validate(['document_type'=>['required',Rule::in($types)],'title'=>['required','string','max:180'],'file'=>['required','file','max:15360'],'version'=>['nullable','string','max:30'],'issued_at'=>['nullable','date'],'expires_at'=>['nullable','date']]);
        $file=$request->file('file'); $path=$file->store('asset-master/'.$asset->id,'local');
        $document=AssetDocument::create(['tenant_id'=>$user->tenant_id,'organization_id'=>$user->organization_id,'asset_id'=>$asset->id,'document_type'=>$data['document_type'],'title'=>$data['title'],'path'=>$path,'file_path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'version'=>$data['version']??null,'issued_at'=>$data['issued_at']??null,'expires_at'=>$data['expires_at']??null,'uploaded_by'=>$user->id]);
        AssetLifecycleEvent::create(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'event_type'=>'DOCUMENT_UPLOADED','from_status'=>$asset->lifecycle_status,'to_status'=>$asset->lifecycle_status,'title'=>'Asset document/photo uploaded','metadata'=>['document_id'=>$document->id,'document_type'=>$document->document_type],'performed_by'=>$user->id,'performed_at'=>now()]);
        return back()->with('status','Document / photo added to Asset 360.');
    }
}
