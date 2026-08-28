<?php

namespace App\Services;

use App\Models\{Asset,AssetMeterReading};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AssetMaintenanceIntelligenceService
{
    public function recordMeter(Asset $asset,int $userId,array $data): AssetMeterReading
    {
        $reading=(float)$data['reading'];
        $latest=AssetMeterReading::where('asset_id',$asset->id)->orderByDesc('reading_date')->orderByDesc('id')->first();
        abort_if($latest && $reading < (float)$latest->reading,422,'Meter reading cannot be lower than the latest recorded value.');
        $entry=AssetMeterReading::create(['tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,'reading'=>$reading,'reading_date'=>$data['reading_date'],'notes'=>$data['notes']??null,'recorded_by'=>$userId]);
        $asset->update(['meter_value'=>$reading]);
        $this->recalculate($asset->fresh());
        return $entry;
    }

    public function snapshot(Asset $asset): array
    {
        $failures=DB::table('asset_failures')->where('asset_id',$asset->id)->get();
        $failureCount=$failures->count();
        $downtime=(int)$failures->sum('downtime_minutes');
        $restored=$failures->filter(fn($f)=>$f->restored_at && $f->failed_at);
        $mttr=$restored->count()?round($restored->avg(fn($f)=>Carbon::parse($f->failed_at)->diffInMinutes(Carbon::parse($f->restored_at))),1):null;
        $completed=DB::table('work_orders')->where('asset_id',$asset->id)->whereNotNull('completed_at')->orderBy('completed_at')->pluck('completed_at');
        $mtbf=null;
        if($completed->count()>=2){ $intervals=[]; for($i=1;$i<$completed->count();$i++) $intervals[]=Carbon::parse($completed[$i-1])->diffInHours(Carbon::parse($completed[$i])); $mtbf=round(array_sum($intervals)/count($intervals),1); }
        $plans=DB::table('maintenance_plans')->where('asset_id',$asset->id)->where('status','ACTIVE')->get();
        $overdue=$plans->filter(fn($p)=>$p->next_due_date && Carbon::parse($p->next_due_date)->isPast())->count();
        $nextDue=$plans->whereNotNull('next_due_date')->sortBy('next_due_date')->first()?->next_due_date;
        $nextMeter=$plans->whereNotNull('next_due_meter')->sortBy('next_due_meter')->first()?->next_due_meter;
        $health=app(AssetAcceptanceContractService::class)->healthSnapshot($asset);
        return array_merge(compact('failureCount','downtime','mttr','mtbf','overdue','nextDue','nextMeter'),['remaining'=>$health['remaining'],'score'=>$health['score'],'band'=>$health['band'],'replacement'=>$health['replacement'],'workOrderFrequency'=>$health['workOrderFrequency'],'inspectionPenalty'=>$health['inspectionPenalty'],'maintenanceCompliance'=>$health['maintenanceCompliance'],'partCount'=>$health['partCount'],'conditionPenalty'=>$health['conditionPenalty']]);
    }

    public function recalculate(Asset $asset): Asset
    {
        return app(AssetAcceptanceContractService::class)->recalculateHealth($asset);
    }
}
