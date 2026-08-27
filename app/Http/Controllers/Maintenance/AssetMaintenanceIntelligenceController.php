<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\{Asset,AssetMeterReading};
use App\Services\AssetMaintenanceIntelligenceService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class AssetMaintenanceIntelligenceController extends Controller
{
    private const ROLES=['ADMIN','MAINTENANCE_MANAGER','MAINTENANCE_ENGINEER','PROJECT_MANAGER'];

    private function asset(Request $request,Asset $asset): \App\Models\User
    {
        $user=$request->user();
        abort_unless($user && in_array($user->role,self::ROLES,true),403);
        abort_unless((int)$asset->tenant_id===(int)$user->tenant_id,404);
        return $user;
    }

    public function show(Request $request,Asset $asset,AssetMaintenanceIntelligenceService $service): View
    {
        $this->asset($request,$asset);
        $snapshot=$service->snapshot($asset);
        $readings=AssetMeterReading::where('asset_id',$asset->id)->orderByDesc('reading_date')->orderByDesc('id')->limit(30)->get();
        $plans=\Illuminate\Support\Facades\DB::table('maintenance_plans')->where('asset_id',$asset->id)->where('status','ACTIVE')->orderBy('next_due_date')->get();
        $failures=\Illuminate\Support\Facades\DB::table('asset_failures')->where('asset_id',$asset->id)->orderByDesc('failed_at')->limit(20)->get();
        return view('maintenance.asset-master.intelligence',compact('asset','snapshot','readings','plans','failures'));
    }

    public function meter(Request $request,Asset $asset,AssetMaintenanceIntelligenceService $service): RedirectResponse
    {
        $user=$this->asset($request,$asset);
        $data=$request->validate(['reading'=>['required','numeric','min:0'],'reading_date'=>['required','date'],'notes'=>['nullable','string','max:2000']]);
        $service->recordMeter($asset,(int)$user->id,$data);
        return back()->with('status','Meter reading recorded and asset health recalculated.');
    }

    public function recalculate(Request $request,Asset $asset,AssetMaintenanceIntelligenceService $service): RedirectResponse
    {
        $user=$this->asset($request,$asset);
        abort_unless(in_array($user->role,['ADMIN','MAINTENANCE_MANAGER'],true),403);
        $service->recalculate($asset);
        return back()->with('status','Asset maintenance intelligence recalculated.');
    }
}
