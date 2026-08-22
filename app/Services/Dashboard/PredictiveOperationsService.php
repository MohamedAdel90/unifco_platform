<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Collection;

class PredictiveOperationsService
{
    public function build(Collection $assets, Collection $workOrders, Collection $openWorkOrders, Collection $maintenancePlans, Collection $serviceRequests, Collection $stockAlerts): array
    {
        $completedStatuses=['COMPLETED','CLOSED'];
        $completed=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$completedStatuses,true));
        $pmOrders=$workOrders->filter(fn($w)=>strtoupper((string)$w->maintenance_type)==='PREVENTIVE');
        $pmCompleted=$pmOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$completedStatuses,true))->count();
        $pmCompliance=$pmOrders->count()?round($pmCompleted/$pmOrders->count()*100,1):100.0;

        $healthKnown=$assets->whereNotNull('health_score');
        $assetHealth=$healthKnown->count()?round((float)$healthKnown->avg('health_score'),1):100.0;
        $overdue=$openWorkOrders->filter(fn($w)=>$w->planned_start && $w->planned_start->isPast())->count();
        $backlogRate=$openWorkOrders->count()?round($overdue/$openWorkOrders->count()*100,1):0.0;
        $slaBreaches=$serviceRequests->filter(fn($r)=>$this->slaState($r)==='BREACHED')->count();
        $slaTotal=$serviceRequests->filter(fn($r)=>$r->response_sla_minutes || $r->resolution_sla_minutes)->count();
        $slaPerformance=$slaTotal?round(max(0,100-($slaBreaches/$slaTotal*100)),1):100.0;
        $stockCritical=$stockAlerts->filter(fn($a)=>in_array($a->computed_alert_status,['OUT_OF_STOCK','REORDER'],true))->count();

        $targets=[
            $this->target('PM Compliance',$pmCompliance,95,'HIGHER','%'),
            $this->target('SLA Performance',$slaPerformance,95,'HIGHER','%'),
            $this->target('Asset Health',$assetHealth,85,'HIGHER','/100'),
            $this->target('Overdue Backlog',$backlogRate,10,'LOWER','%'),
            $this->target('Critical Stock Alerts',$stockCritical,0,'LOWER','alerts'),
        ];

        $maintenanceForecast=['7 Days'=>0,'30 Days'=>0,'60 Days'=>0,'90 Days'=>0];
        foreach($maintenancePlans->filter(fn($p)=>$p->status==='ACTIVE' && $p->next_due_date) as $plan){
            if($plan->next_due_date->isPast()) continue;
            $days=(int)today()->diffInDays($plan->next_due_date,false);
            if($days<=7)$maintenanceForecast['7 Days']++;
            if($days<=30)$maintenanceForecast['30 Days']++;
            if($days<=60)$maintenanceForecast['60 Days']++;
            if($days<=90)$maintenanceForecast['90 Days']++;
        }

        $slaRisks=collect();
        foreach($serviceRequests as $request){
            $state=$this->slaState($request);
            if(!in_array($state,['AT_RISK','BREACHED'],true)) continue;
            $slaRisks->push((object)[
                'request_no'=>$request->request_no,
                'priority'=>$request->priority,
                'status'=>$request->status,
                'risk'=>$state,
                'consumption'=>$this->slaConsumption($request),
            ]);
        }
        $slaRisks=$slaRisks->sortByDesc(fn($r)=>($r->risk==='BREACHED'?200:100)+$r->consumption)->take(8)->values();

        $replacementPipeline=['Immediate'=>['count'=>0,'value'=>0.0],'0-12 Months'=>['count'=>0,'value'=>0.0],'13-24 Months'=>['count'=>0,'value'=>0.0],'25-36 Months'=>['count'=>0,'value'=>0.0]];
        foreach($assets as $asset){
            $months=$asset->remaining_life_months;
            if($asset->replacement_recommendation==='PLAN_REPLACEMENT' && ($months===null || $months<=0)) $bucket='Immediate';
            elseif($months!==null && $months<=12) $bucket='0-12 Months';
            elseif($months!==null && $months<=24) $bucket='13-24 Months';
            elseif($months!==null && $months<=36) $bucket='25-36 Months';
            else continue;
            $replacementPipeline[$bucket]['count']++;
            $replacementPipeline[$bucket]['value']+=(float)($asset->replacement_value ?: $asset->acquisition_cost ?: 0);
        }

        $monthStart=now()->startOfMonth();
        $lastMonthStart=now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd=now()->subMonthNoOverflow()->endOfMonth();
        $maintenanceCostThisMonth=(float)$workOrders->filter(fn($w)=>$w->completed_at && $w->completed_at->gte($monthStart))->sum('total_cost');
        $maintenanceCostLastMonth=(float)$workOrders->filter(fn($w)=>$w->completed_at && $w->completed_at->between($lastMonthStart,$lastMonthEnd))->sum('total_cost');
        $costChange=$maintenanceCostLastMonth==0?($maintenanceCostThisMonth>0?100:0):round((($maintenanceCostThisMonth-$maintenanceCostLastMonth)/abs($maintenanceCostLastMonth))*100,1);

        $earlyWarnings=collect();
        if($backlogRate>10)$earlyWarnings->push($this->warning('BACKLOG','HIGH','Overdue backlog is above the 10% management threshold.',round($backlogRate,1).'% overdue'));
        if($pmCompliance<95)$earlyWarnings->push($this->warning('PM','HIGH','Preventive maintenance compliance is below target.',round($pmCompliance,1).'% vs 95% target'));
        if($slaRisks->where('risk','AT_RISK')->count()>0)$earlyWarnings->push($this->warning('SLA','HIGH','Service requests are approaching SLA breach.', $slaRisks->where('risk','AT_RISK')->count().' requests at risk'));
        if($stockCritical>0)$earlyWarnings->push($this->warning('STOCK','CRITICAL','Critical spare availability can interrupt planned maintenance.',$stockCritical.' critical alerts'));
        if($replacementPipeline['Immediate']['count']>0)$earlyWarnings->push($this->warning('REPLACEMENT','CRITICAL','Assets require immediate replacement planning.',$replacementPipeline['Immediate']['count'].' assets'));
        if($costChange>20)$earlyWarnings->push($this->warning('COST','MEDIUM','Maintenance cost increased materially versus last month.','+'.round($costChange,1).'% month-over-month'));

        $departmentScores=[
            'Maintenance'=>$this->score([$pmCompliance,100-min(100,$backlogRate*3)]),
            'Asset Management'=>$this->score([$assetHealth,100-min(100,$replacementPipeline['Immediate']['count']*15)]),
            'Customer Service'=>$this->score([$slaPerformance,100-min(100,$slaRisks->where('risk','AT_RISK')->count()*10)]),
            'Supply Chain'=>$this->score([100-min(100,$stockCritical*12)]),
        ];

        return compact('targets','maintenanceForecast','slaRisks','replacementPipeline','maintenanceCostThisMonth','maintenanceCostLastMonth','costChange','earlyWarnings','departmentScores');
    }

    private function target(string $name,float|int $actual,float|int $target,string $direction,string $unit): object
    {
        $met=$direction==='HIGHER' ? $actual>=$target : $actual<=$target;
        $gap=$direction==='HIGHER' ? round($actual-$target,1) : round($target-$actual,1);
        return (object)compact('name','actual','target','direction','unit','met','gap');
    }

    private function warning(string $type,string $severity,string $message,string $metric): object
    {
        return (object)compact('type','severity','message','metric');
    }

    private function score(array $components): int
    {
        if(!$components) return 100;
        return (int)round(max(0,min(100,array_sum($components)/count($components))));
    }

    private function slaState($request): string
    {
        if(!$request->created_at) return 'OK';
        $consumption=$this->slaConsumption($request);
        if($consumption>=100) return 'BREACHED';
        if($consumption>=75) return 'AT_RISK';
        return 'OK';
    }

    private function slaConsumption($request): float
    {
        if(!$request->created_at) return 0.0;
        $created=$request->created_at;
        $ratios=[];
        if($request->response_sla_minutes && !$request->responded_at){
            $ratios[]=now()->diffInMinutes($created)/(float)$request->response_sla_minutes*100;
        }
        if($request->resolution_sla_minutes && !$request->resolved_at){
            $ratios[]=now()->diffInMinutes($created)/(float)$request->resolution_sla_minutes*100;
        }
        return round(max($ratios ?: [0]),1);
    }
}
