<?php

namespace App\Services\EAM;

use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssetHealthService
{
    public function calculate(Asset $asset): array
    {
        $score=100;
        $reasons=[];
        $now=now();
        $commission=$asset->commission_date ? Carbon::parse($asset->commission_date) : null;
        $remainingLife=null;

        if($commission && $asset->useful_life_months){
            $ageMonths=$commission->diffInMonths($now);
            $remainingLife=max(0,(int)$asset->useful_life_months-$ageMonths);
            $lifeRatio=min(1,$ageMonths/max(1,(int)$asset->useful_life_months));
            $agePenalty=(int)round($lifeRatio*20);
            $score-=$agePenalty;
            if($remainingLife<=12){$reasons[]='Remaining useful life is 12 months or less.';}
        }

        $failures=DB::table('asset_failures')->where('asset_id',$asset->id)->where('failed_at','>=',$now->copy()->subMonths(12))->get();
        $failurePenalty=min(25,$failures->count()*5);
        $score-=$failurePenalty;
        if($failures->count()>=3)$reasons[]='Repeated failures recorded during the last 12 months.';

        $downtime=(int)$failures->sum('downtime_minutes');
        $downtimePenalty=min(20,(int)floor($downtime/240)*2);
        $score-=$downtimePenalty;
        if($downtime>=1440)$reasons[]='High accumulated downtime during the last 12 months.';

        $overduePlans=DB::table('maintenance_plans')->where('asset_id',$asset->id)->where('status','ACTIVE')->whereNotNull('next_due_date')->where('next_due_date','<',$now->toDateString())->count();
        if($overduePlans>0){$score-=min(20,$overduePlans*5);$reasons[]='Preventive maintenance is overdue.';}

        $operationalPenalty=match((string)$asset->operational_status){
            'BREAKDOWN'=>35,
            'OUT_OF_SERVICE'=>35,
            'STOPPED'=>20,
            'ISOLATED'=>20,
            'UNDER_MAINTENANCE'=>10,
            default=>0,
        };
        $score-=$operationalPenalty;
        if($operationalPenalty>=20)$reasons[]='Current operational condition requires attention.';

        $yearCost=(float)DB::table('work_orders')->where('asset_id',$asset->id)->where('status','COMPLETED')->where('completed_at','>=',$now->copy()->subMonths(12))->sum('total_cost');
        $replacementValue=(float)$asset->replacement_value;
        $costRatio=$replacementValue>0?$yearCost/$replacementValue:null;
        if($costRatio!==null){
            $score-=min(20,(int)round(min(1,$costRatio/0.4)*20));
            if($costRatio>=0.30)$reasons[]='Recent maintenance cost is high relative to replacement value.';
        }

        $score=max(0,min(100,$score));
        $band=match(true){
            $score>=80=>'HEALTHY',
            $score>=60=>'WATCH',
            $score>=40=>'ATTENTION',
            default=>'CRITICAL',
        };

        $recommendation='KEEP_IN_SERVICE';
        if($score<40 || ($remainingLife!==null && $remainingLife<=6) || ($costRatio!==null && $costRatio>=0.40)) $recommendation='PLAN_REPLACEMENT';
        elseif($score<60 || ($remainingLife!==null && $remainingLife<=12)) $recommendation='ENGINEERING_REVIEW';

        if(!$reasons)$reasons[]='No major lifecycle risk indicators detected.';

        return [
            'health_score'=>$score,
            'health_band'=>$band,
            'remaining_life_months'=>$remainingLife,
            'replacement_recommendation'=>$recommendation,
            'replacement_reason'=>implode(' ',$reasons),
            'last_health_calculated_at'=>$now,
        ];
    }

    public function recalculate(Asset $asset): array
    {
        $result=$this->calculate($asset);
        $asset->forceFill($result)->save();
        return $result;
    }
}
