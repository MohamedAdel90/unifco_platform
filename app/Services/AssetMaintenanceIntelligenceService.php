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

        $entry=AssetMeterReading::create([
            'tenant_id'=>$asset->tenant_id,'organization_id'=>$asset->organization_id,'asset_id'=>$asset->id,
            'reading'=>$reading,'reading_date'=>$data['reading_date'],'notes'=>$data['notes']??null,'recorded_by'=>$userId,
        ]);
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
        $mttr=$restored->count() ? round($restored->avg(fn($f)=>Carbon::parse($f->failed_at)->diffInMinutes(Carbon::parse($f->restored_at))),1) : null;

        $completed=DB::table('work_orders')->where('asset_id',$asset->id)->whereNotNull('completed_at')->orderBy('completed_at')->pluck('completed_at');
        $mtbf=null;
        if($completed->count()>=2){
            $intervals=[];
            for($i=1;$i<$completed->count();$i++) $intervals[]=Carbon::parse($completed[$i-1])->diffInHours(Carbon::parse($completed[$i]));
            $mtbf=round(array_sum($intervals)/count($intervals),1);
        }

        $plans=DB::table('maintenance_plans')->where('asset_id',$asset->id)->where('status','ACTIVE')->get();
        $overdue=$plans->filter(fn($p)=>$p->next_due_date && Carbon::parse($p->next_due_date)->isPast())->count();
        $nextDue=$plans->whereNotNull('next_due_date')->sortBy('next_due_date')->first()?->next_due_date;
        $nextMeter=$plans->whereNotNull('next_due_meter')->sortBy('next_due_meter')->first()?->next_due_meter;

        $ageMonths=$asset->installation_date ? $asset->installation_date->diffInMonths(today()) : 0;
        $life=(int)($asset->useful_life_months ?: 0);
        $remaining=$life>0 ? max(0,$life-$ageMonths) : null;

        $score=100;
        $score-=min(30,$failureCount*6);
        $score-=min(20,(int)floor($downtime/240)*4);
        $score-=min(20,$overdue*10);
        if($life>0) $score-=min(20,(int)round(($ageMonths/$life)*20));
        if($asset->lifecycle_status==='OUT_OF_SERVICE') $score-=20;
        if($asset->lifecycle_status==='UNDER_MAINTENANCE') $score-=8;
        $score=max(0,min(100,$score));
        $band=$score>=90?'EXCELLENT':($score>=75?'GOOD':($score>=60?'ATTENTION':($score>=40?'POOR':'CRITICAL')));
        $replacement=$score<40?'REPLACE':($remaining!==null && $remaining<=12?'PLAN_REPLACEMENT':'MONITOR');

        return compact('failureCount','downtime','mttr','mtbf','overdue','nextDue','nextMeter','remaining','score','band','replacement');
    }

    public function recalculate(Asset $asset): Asset
    {
        $s=$this->snapshot($asset);
        $reason='Failures '.$s['failureCount'].'; downtime '.$s['downtime'].' min; overdue PM '.$s['overdue'].'.';
        $asset->update([
            'health_score'=>$s['score'],'health_band'=>$s['band'],'remaining_life_months'=>$s['remaining'],
            'replacement_recommendation'=>$s['replacement'],'replacement_reason'=>$reason,'last_health_calculated_at'=>now(),
        ]);
        return $asset->fresh();
    }
}
