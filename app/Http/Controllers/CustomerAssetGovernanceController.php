<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CustomerAssetGovernanceService;
use Illuminate\Http\{JsonResponse,RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerAssetGovernanceController extends Controller
{
    public function index(Request $request): View
    {
        $user=$request->user(); abort_unless($user,403);
        $q=DB::table('customer_asset_submissions')->where('tenant_id',$user->tenant_id);
        if($user->customer_id) $q->where('customer_id',$user->customer_id); else abort_unless(in_array($user->role,['ADMIN','MAINTENANCE_MANAGER'],true),403);
        return view('customer-assets.governance',['submissions'=>$q->orderByDesc('id')->limit(200)->get(),'isCustomer'=>(bool)$user->customer_id]);
    }

    public function store(Request $request,CustomerAssetGovernanceService $service): RedirectResponse
    {
        $user=$request->user(); abort_unless($user instanceof User && $user->customer_id,403);
        $service->submit($user,$this->validatedAsset($request));
        return back()->with('status','New Asset → Pending Verification. It cannot become ACTIVE until independent Verify & Activate.');
    }

    public function import(Request $request,CustomerAssetGovernanceService $service): RedirectResponse
    {
        $user=$request->user(); abort_unless($user instanceof User && $user->customer_id,403);
        $data=$request->validate(['file'=>['required','file','max:51200']]);
        $result=$service->import($user,$data['file']);
        return back()->with('status','Bulk Import: Imported → Validation Queue → Duplicate Check → Approval. Created '.count($result['created']).'; rejected '.count($result['errors']).'.')->with('import_errors',$result['errors']);
    }

    public function review(Request $request,int $submission,CustomerAssetGovernanceService $service): RedirectResponse
    {
        $user=$request->user(); abort_unless($user instanceof User,403);
        $data=$request->validate(['decision'=>['required',Rule::in(['APPROVE','REJECT'])],'notes'=>['nullable','string','max:5000']]);
        $asset=$service->review($user,$submission,$data['decision']==='APPROVE',$data['notes']??null);
        return back()->with('status',$asset?'Submission approved into Asset Master as PENDING_VERIFICATION. Maintenance Manager must Verify & Activate separately.':'Customer asset submission rejected.');
    }

    public function audit(Request $request,int $submission): JsonResponse
    {
        $user=$request->user(); abort_unless($user,403);
        $record=DB::table('customer_asset_submissions')->where('tenant_id',$user->tenant_id)->where('id',$submission)->first(); abort_unless($record,404);
        if($user->customer_id) abort_unless((int)$record->customer_id===(int)$user->customer_id,404); else abort_unless(in_array($user->role,['ADMIN','MAINTENANCE_MANAGER'],true),403);
        return response()->json(DB::table('customer_asset_submission_events')->where('customer_asset_submission_id',$submission)->orderBy('performed_at')->get());
    }

    private function validatedAsset(Request $request): array
    {
        $data=$request->validate([
            'customer_site_id'=>['required','integer'],'name'=>['required','string','max:180'],'asset_category'=>['required','string','max:120'],
            'customer_asset_code'=>['nullable','string','max:120'],'serial_no'=>['nullable','string','max:120'],'asset_type'=>['nullable','string','max:120'],'manufacturer'=>['nullable','string','max:180'],'model_no'=>['nullable','string','max:180'],
            'criticality'=>['nullable',Rule::in(['LOW','MEDIUM','HIGH','CRITICAL'])],'ownership_type'=>['nullable',Rule::in(['CUSTOMER_OWNED'])],
            'maintenance_strategy'=>['nullable',Rule::in(['PREVENTIVE','PREDICTIVE','CONDITION_BASED','CORRECTIVE','RUN_TO_FAILURE'])],'installation_date'=>['nullable','date'],
            'physical_location'=>['nullable','string','max:255'],'technical_specifications'=>['nullable','json'],
        ]);
        if(isset($data['technical_specifications'])) $data['technical_specifications']=json_decode($data['technical_specifications'],true);
        return $data;
    }
}
