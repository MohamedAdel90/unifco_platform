<?php

namespace App\Http\Controllers\EAM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetReliabilityController extends Controller
{
    public function show(Asset $asset): View
    {
        $asset->load(['customer','site']);
        $failures=DB::table('asset_failures')->where('asset_id',$asset->id)->orderByDesc('failed_at')->get();
        $workOrders=DB::table('work_orders')->where('asset_id',$asset->id)->orderByDesc('id')->get();
        $completed=$workOrders->where('status','COMPLETED');
        $failureCount=$failures->count();
        $totalDowntime=(int)$failures->sum('downtime_minutes');
        $mttr=$failureCount?round($totalDowntime/$failureCount,1):null;

        $chronological=$failures->sortBy('failed_at')->values();
        $intervals=[];
        for($i=1;$i<$chronological->count();$i++){
            $previous=$chronological[$i-1];
            if(!$previous->restored_at) continue;
            $start=\Carbon\Carbon::parse($previous->restored_at);
            $end=\Carbon\Carbon::parse($chronological[$i]->failed_at);
            if($end->greaterThan($start)) $intervals[]=$start->diffInMinutes($end);
        }
        $mtbf=count($intervals)?round(array_sum($intervals)/count($intervals),1):null;

        $pmOrders=$workOrders->where('maintenance_type','PREVENTIVE');
        $pmCompleted=$pmOrders->where('status','COMPLETED')->count();
        $pmCompliance=$pmOrders->count()?round($pmCompleted/$pmOrders->count()*100,1):100;
        $totalMaintenanceCost=(float)$completed->sum('total_cost');
        $correctiveCost=(float)$completed->where('maintenance_type','CORRECTIVE')->sum('total_cost');
        $preventiveCost=(float)$completed->where('maintenance_type','PREVENTIVE')->sum('total_cost');

        $metrics=compact('failureCount','totalDowntime','mttr','mtbf','pmCompliance','totalMaintenanceCost','correctiveCost','preventiveCost');
        return view('eam.assets.reliability',compact('asset','failures','workOrders','metrics'));
    }
}
