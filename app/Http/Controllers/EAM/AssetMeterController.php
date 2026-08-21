<?php

namespace App\Http\Controllers\EAM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetMeterController extends Controller
{
    public function show(Asset $asset): View
    {
        $asset->load(['customer','site']);
        $readings = DB::table('asset_meter_readings')
            ->leftJoin('users','users.id','=','asset_meter_readings.recorded_by')
            ->where('asset_meter_readings.asset_id',$asset->id)
            ->select('asset_meter_readings.*','users.name as recorded_by_name')
            ->orderByDesc('reading_date')->orderByDesc('asset_meter_readings.id')->limit(100)->get();

        $meterPlans = DB::table('maintenance_plans')
            ->where('asset_id',$asset->id)
            ->where('frequency_type','METER')
            ->where('status','ACTIVE')
            ->orderBy('next_due_meter')->get();

        return view('eam.assets.meters',compact('asset','readings','meterPlans'));
    }

    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $data=$request->validate([
            'reading'=>['required','numeric','min:0'],
            'reading_date'=>['required','date'],
            'notes'=>['nullable','string','max:2000'],
        ]);

        if ((float)$data['reading'] < (float)$asset->meter_value) {
            throw ValidationException::withMessages(['reading'=>'Meter reading cannot be lower than the current asset reading.']);
        }

        DB::transaction(function () use ($data,$asset): void {
            DB::table('asset_meter_readings')->insert([
                'tenant_id'=>Auth::user()->tenant_id,
                'organization_id'=>Auth::user()->organization_id,
                'asset_id'=>$asset->id,
                'reading'=>$data['reading'],
                'reading_date'=>$data['reading_date'],
                'notes'=>$data['notes']??null,
                'recorded_by'=>Auth::id(),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
            $asset->update(['meter_value'=>$data['reading']]);
        });

        return back()->with('status','Asset meter reading recorded. Meter-based maintenance plans will be evaluated automatically.');
    }
}
