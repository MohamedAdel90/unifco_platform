<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AssetAcceptanceContractService
{
    public function recalculateCriticality(Asset $asset): Asset
    {
        $impacts=array_filter([$asset->impact_safety,$asset->impact_operation,$asset->impact_financial,$asset->impact_customer,$asset->impact_environmental],fn($v)=>$v!==null);
        $probabilities=array_filter([$asset->probability_failure,$asset->probability_condition,$asset->probability_age],fn($v)=>$v!==null);
        if(count($impacts)<5 || count($probabilities)<3) return $asset;
        $impact=array_sum($impacts)/count($impacts); $probability=array_sum($probabilities)/count($probabilities); $score=round($impact*$probability,2);
        $class=$score>=16?'A':($score>=10?'B':($score>=5?'C':'D')); $criticality=['A'=>'CRITICAL','B'=>'HIGH','C'=>'MEDIUM','D'=>'LOW'][$class];
        $asset->update(['criticality_matrix_score'=>$score,'criticality_class'=>$class,'criticality'=>$criticality]); return $asset->fresh();
    }

    public function healthSnapshot(Asset $asset): array
    {
        $ageMonths=$asset->installation_date?$asset->installation_date->diffInMonths(today()):0; $life=(int)($asset->useful_life_months?:0); $ageRatio=$life>0?min(1,$ageMonths/$life):0;
        $failures=DB::table('asset_failures')->where('asset_id',$asset->id)->where('failed_at','>=',now()->subYear())->get(); $failureCount=$failures->count(); $downtime=(int)$failures->sum('downtime_minutes');
        $workOrderFrequency=DB::table('work_orders')->where('asset_id',$asset->id)->where('created_at','>=',now()->subYear())->count();
        $latestInspection=DB::table('asset_inspection_records')->where('asset_id',$asset->id)->orderByDesc('inspection_date')->orderByDesc('id')->first(); $inspectionPenalty=match($latestInspection?->condition_status){'CRITICAL'=>18,'POOR'=>12,'FAIR'=>6,default=>0};
        $plans=DB::table('maintenance_plans')->where('asset_id',$asset->id)->where('status','ACTIVE')->get(); $overdue=$plans->filter(fn($p)=>$p->next_due_date && Carbon::parse($p->next_due_date)->isPast())->count(); $maintenanceCompliance=$plans->count()?round(max(0,100-(($overdue/$plans->count())*100)),1):null;
        $partRows=DB::table('asset_part_installations')->where('asset_id',$asset->id)->where('installed_at','>=',now()->subYear())->get(); $partCount=$partRows->count(); $partCost=(float)$partRows->sum('total_cost');
        $conditionPenalty=0; if($asset->design_capacity && $asset->current_load){$util=((float)$asset->current_load/(float)$asset->design_capacity)*100;$conditionPenalty=$util>=100?15:($util>=90?10:($util>=80?5:0));}
        $score=100-min(15,(int)round($ageRatio*15))-min(20,$failureCount*5)-min(10,$workOrderFrequency*2)-min(15,(int)floor($downtime/240)*3)-$inspectionPenalty-min(15,$overdue*8)-min(10,$partCount*2)-$conditionPenalty; $score=max(0,min(100,$score));
        $evidence=['installation_and_life'=>(bool)($asset->installation_date&&$life>0),'inspection'=>(bool)$latestInspection,'maintenance_plan'=>$plans->count()>0,'operating_profile'=>(bool)($asset->design_capacity&&$asset->current_load),'work_history'=>($workOrderFrequency>0||$failureCount>0)];
        $evidenceCount=count(array_filter($evidence)); $evidenceCoverage=(int)round(($evidenceCount/count($evidence))*100); $sufficientEvidence=$evidenceCount>=3;
        $band=$sufficientEvidence?($score>=90?'EXCELLENT':($score>=75?'GOOD':($score>=60?'ATTENTION':($score>=40?'POOR':'CRITICAL')))):'INSUFFICIENT_DATA';
        $remaining=$life>0?max(0,$life-$ageMonths):null; $replacement=$sufficientEvidence?($score<40?'REPLACE':($remaining!==null&&$remaining<=12?'PLAN_REPLACEMENT':'MONITOR')):'ASSESS';
        return compact('score','band','ageMonths','failureCount','workOrderFrequency','downtime','inspectionPenalty','maintenanceCompliance','partCount','partCost','conditionPenalty','remaining','replacement','evidence','evidenceCount','evidenceCoverage','sufficientEvidence');
    }

    public function recalculateHealth(Asset $asset): Asset
    {
        $s=$this->healthSnapshot($asset); $reason='Evidence '.$s['evidenceCoverage'].'%; age '.$s['ageMonths'].'m; failures '.$s['failureCount'].'; WO frequency '.$s['workOrderFrequency'].'; downtime '.$s['downtime'].'m; inspection penalty '.$s['inspectionPenalty'].'; maintenance compliance '.($s['maintenanceCompliance']??'N/A').'%; parts '.$s['partCount'].'; condition penalty '.$s['conditionPenalty'].'.';
        $asset->update(['health_score'=>$s['score'],'health_band'=>$s['band'],'remaining_life_months'=>$s['remaining'],'replacement_recommendation'=>$s['replacement'],'replacement_reason'=>$reason,'last_health_calculated_at'=>now()]); return $asset->fresh();
    }

    public function alerts(Asset $asset): array
    {
        $warrantyExpiresSoon=$asset->warranty_expiry&&!$asset->warranty_expiry->isPast()&&$asset->warranty_expiry->lte(today()->addDays(30)); $replacementDate=$asset->replacement_target_date?:$asset->expected_replacement_date; $approachingEndOfLife=$replacementDate&&!$replacementDate->isPast()&&$replacementDate->lte(today()->addYear()); return ['warranty_expires_in_30_days'=>$warrantyExpiresSoon,'approaching_end_of_useful_life'=>$approachingEndOfLife];
    }

    public function overview(Asset $asset): array
    {
        $openWorkOrders=DB::table('work_orders')->where('asset_id',$asset->id)->whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])->count(); $workOrders=DB::table('work_orders')->where('asset_id',$asset->id); $lifetimeCost=(float)(clone $workOrders)->sum('total_cost'); $downtimeYtd=(int)DB::table('asset_failures')->where('asset_id',$asset->id)->whereYear('failed_at',today()->year)->sum('downtime_minutes'); $installedParts=DB::table('asset_part_installations')->where('asset_id',$asset->id)->where('component_status','INSTALLED')->count(); return ['next_pm'=>$asset->next_pm?->toDateString(),'open_work_orders'=>$openWorkOrders,'lifetime_cost'=>$lifetimeCost,'downtime_ytd'=>$downtimeYtd,'installed_parts'=>$installedParts,'last_inspection'=>$asset->last_inspection?->toDateString()];
    }
}
