<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\AgreedAssetIntelligenceService;
use Illuminate\Http\{JsonResponse,RedirectResponse,Request};
use Illuminate\Validation\Rule;

class AgreedAssetIntelligenceController extends Controller
{
    private const ROLES=['ADMIN','MAINTENANCE_MANAGER','MAINTENANCE_ENGINEER','PROJECT_MANAGER'];

    private function actor(Request $request,Asset $asset): \App\Models\User
    {
        $user=$request->user(); abort_unless($user && in_array($user->role,self::ROLES,true),403); abort_unless((int)$user->tenant_id===(int)$asset->tenant_id,404); return $user;
    }

    public function pm(Request $request,Asset $asset,AgreedAssetIntelligenceService $service): RedirectResponse
    {
        $user=$this->actor($request,$asset);
        $data=$request->validate(['plan_no'=>['nullable','string','max:50'],'name'=>['required','string','max:160'],'frequency_type'=>['required',Rule::in(['DAY','WEEK','MONTH','YEAR','METER'])],'frequency_value'=>['required','integer','min:1'],'next_due_date'=>['nullable','date'],'next_due_meter'=>['nullable','numeric','min:0'],'priority'=>['nullable','string','max:20']]);
        $service->createPmPlan($asset,(int)$user->id,$data); return back()->with('status','PM plan created.');
    }

    public function inspection(Request $request,Asset $asset,AgreedAssetIntelligenceService $service): RedirectResponse
    {
        $user=$this->actor($request,$asset);
        $data=$request->validate(['work_order_id'=>['nullable','integer'],'inspection_date'=>['required','date'],'inspection_type'=>['nullable','string','max:80'],'condition_status'=>['required',Rule::in(['GOOD','FAIR','POOR','CRITICAL'])],'findings'=>['nullable','string','max:5000'],'recommendations'=>['nullable','string','max:5000']]);
        $service->recordInspection($asset,(int)$user->id,$data); return back()->with('status','Inspection recorded.');
    }

    public function failure(Request $request,Asset $asset,AgreedAssetIntelligenceService $service): RedirectResponse
    {
        $user=$this->actor($request,$asset);
        $data=$request->validate(['work_order_id'=>['nullable','integer'],'failure_code'=>['nullable','string','max:120'],'failure_mode'=>['required','string','max:180'],'failure_effect'=>['nullable','string','max:180'],'failure_cause'=>['nullable','string','max:180'],'root_cause'=>['nullable','string','max:5000'],'corrective_action'=>['nullable','string','max:5000'],'failed_at'=>['required','date'],'restored_at'=>['nullable','date'],'downtime_minutes'=>['nullable','integer','min:0'],'meter_at_failure'=>['nullable','numeric','min:0'],'severity'=>['nullable',Rule::in(['LOW','MEDIUM','HIGH','CRITICAL'])]]);
        $service->recordFailure($asset,(int)$user->id,$data); return back()->with('status','Failure recorded and reliability recalculated.');
    }

    public function agreedSnapshot(Request $request,Asset $asset,AgreedAssetIntelligenceService $service): JsonResponse
    {
        $this->actor($request,$asset); return response()->json(['phase_b'=>$service->phaseBSnapshot($asset),'phase_c'=>$service->phaseCSnapshot($asset)]);
    }
}
