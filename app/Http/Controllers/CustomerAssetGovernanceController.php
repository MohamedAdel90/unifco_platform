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
        $data=$this->validatedAsset($request);
        $service->submit($user,$data); return back()->with('status','Asset submitted for UNIFCO verification.');
    }

    public function import(Request $request,CustomerAssetGovernanceService $service): RedirectResponse
    {
        $user=$request->user(); abort_unless($user instanceof User && $user->customer_id,403);
        $data=$request->validate(['file'=>['required','file','max:10240']]);
        $result=$service->import($user,$data['file']);
        return back()->with('status','Bulk import created '.count($result['created']).' submissions; '.count($result['errors']).' rows rejected.')->with('import_errors',$result['errors']);
    }

    public function review(Request $request,int $submission,CustomerAssetGovernanceService $service): RedirectResponse
    {
        $user=$request->user(); abort_unless($user instanceof User,403);
        $data=$request->validate(['decision'=>['required',Rule::in(['APPROVE','REJECT'])],'notes'=>['nullable','string','max:5000']]);
        $service->review($user,$submission,$data['decision']==='APPROVE',$data['notes']??null); return back()->with('status','Customer asset verification review completed.');
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
            'customer_site_id'=>['required','integer'],'name'=>['required','string','max:180'],'customer_asset_code'=>['nullable','string','max:120'],'serial_no'=>['required','string','max:120'],
            'asset_category'=>['required','string','max:120'],'asset_type'=>['required','string','max:120'],'manufacturer'=>['required','string','max:180'],'model_no'=>['required','string','max:180'],
            'criticality'=>['required',Rule::in(['LOW','MEDIUM','HIGH','CRITICAL'])],'ownership_type'=>['nullable',Rule::in(['CUSTOMER_OWNED'])],
            'maintenance_strategy'=>['nullable',Rule::in(['PREVENTIVE','PREDICTIVE','CONDITION_BASED','CORRECTIVE','RUN_TO_FAILURE'])],'installation_date'=>['required','date'],
            'physical_location'=>['required','string','max:255'],'technical_specifications'=>['nullable','json'],
        ]);
        if(isset($data['technical_specifications'])) $data['technical_specifications']=json_decode($data['technical_specifications'],true);
        return $data;
    }
}
