<?php

namespace App\Http\Controllers\EAM;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Customer,CustomerSite,ServiceContract};
use App\Services\EAM\AssetSparePartReorderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetHealthDashboardController extends Controller
{
    public function index(Request $request, AssetSparePartReorderService $reorder): View
    {
        $customerId=$request->integer('customer_id')?:null;
        $siteId=$request->integer('site_id')?:null;
        $contractId=$request->integer('contract_id')?:null;

        $assets=Asset::with(['customer','site'])
            ->when($customerId,fn($q)=>$q->where('customer_id',$customerId))
            ->when($siteId,fn($q)=>$q->where('customer_site_id',$siteId))
            ->when($contractId,function($q) use($contractId){
                $ids=DB::table('asset_contract_assignments')->where('service_contract_id',$contractId)->where('status','ACTIVE')->pluck('asset_id');
                $q->whereIn('id',$ids);
            })->orderBy('health_score')->get();

        $metrics=$this->portfolioMetrics($assets);
        $recommendations=$this->recommendations($assets);
        $reorderAlerts=$reorder->alerts($customerId,$siteId)->filter(fn($a)=>$a->computed_alert_status!=='OK')->values();

        return view('eam.health.index',[
            'assets'=>$assets,'metrics'=>$metrics,'recommendations'=>$recommendations,'reorderAlerts'=>$reorderAlerts,
            'customers'=>Customer::orderBy('name')->get(),
            'sites'=>CustomerSite::when($customerId,fn($q)=>$q->where('customer_id',$customerId))->orderBy('name')->get(),
            'contracts'=>ServiceContract::when($customerId,fn($q)=>$q->where('customer_id',$customerId))->orderByDesc('starts_on')->get(),
            'customerId'=>$customerId,'siteId'=>$siteId,'contractId'=>$contractId,
        ]);
    }

    public function customer(Request $request, AssetSparePartReorderService $reorder): View
    {
        $user=auth()->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403);
        $customerId=(int)$user->customer_id;
        $siteId=$request->integer('site_id')?:null;
        $contractId=$request->integer('contract_id')?:null;

        $assets=Asset::with('site')->where('customer_id',$customerId)
            ->when($siteId,fn($q)=>$q->where('customer_site_id',$siteId))
            ->when($contractId,function($q) use($contractId,$customerId){
                abort_unless(ServiceContract::whereKey($contractId)->where('customer_id',$customerId)->exists(),404);
                $ids=DB::table('asset_contract_assignments')->where('service_contract_id',$contractId)->where('status','ACTIVE')->pluck('asset_id');
                $q->whereIn('id',$ids);
            })->orderBy('health_score')->get();

        $metrics=$this->portfolioMetrics($assets,false);
        $recommendations=$this->recommendations($assets,false);
        $reorderAlerts=$reorder->alerts($customerId,$siteId)->filter(fn($a)=>$a->computed_alert_status!=='OK')->map(function($a){
            unset($a->preferred_supplier,$a->suggested_order_quantity,$a->stock_quantity,$a->reserved_quantity);
            return $a;
        })->values();

        return view('customer.asset-health',[
            'assets'=>$assets,'metrics'=>$metrics,'recommendations'=>$recommendations,'reorderAlerts'=>$reorderAlerts,
            'sites'=>CustomerSite::where('customer_id',$customerId)->orderBy('name')->get(),
            'contracts'=>ServiceContract::where('customer_id',$customerId)->orderByDesc('starts_on')->get(),
            'siteId'=>$siteId,'contractId'=>$contractId,
        ]);
    }

    private function portfolioMetrics($assets,bool $internal=true): array
    {
        $total=$assets->count();
        $healthKnown=$assets->whereNotNull('health_score');
        $average=$healthKnown->count()?round((float)$healthKnown->avg('health_score'),1):null;
        $critical=$assets->whereIn('health_band',['ATTENTION','CRITICAL'])->count();
        $replacement=$assets->where('replacement_recommendation','PLAN_REPLACEMENT')->count();
        $review=$assets->where('replacement_recommendation','ENGINEERING_REVIEW')->count();
        $healthy=$assets->where('health_band','HEALTHY')->count();
        $running=$assets->where('operational_status','RUNNING')->count();
        $availability=$total?round($running/$total*100,1):100;
        $assetIds=$assets->pluck('id');
        $overduePm=$assetIds->isEmpty()?0:DB::table('maintenance_plans')->whereIn('asset_id',$assetIds)->where('status','ACTIVE')->whereNotNull('next_due_date')->where('next_due_date','<',today()->toDateString())->count();
        $failures30=$assetIds->isEmpty()?0:DB::table('asset_failures')->whereIn('asset_id',$assetIds)->where('failed_at','>=',now()->subDays(30))->count();
        $result=compact('total','average','critical','replacement','review','healthy','availability','overduePm','failures30');
        if($internal){
            $result['maintenanceCost12m']=$assetIds->isEmpty()?0:(float)DB::table('work_orders')->whereIn('asset_id',$assetIds)->where('status','COMPLETED')->where('completed_at','>=',now()->subMonths(12))->sum('total_cost');
        }
        return $result;
    }

    private function recommendations($assets,bool $internal=true)
    {
        return $assets->filter(fn($a)=>in_array($a->replacement_recommendation,['PLAN_REPLACEMENT','ENGINEERING_REVIEW'],true) || in_array($a->health_band,['ATTENTION','CRITICAL'],true))
            ->sortBy('health_score')->take(30)->values()->map(function($asset) use($internal){
                return (object)[
                    'id'=>$asset->id,'asset_code'=>$asset->asset_code,'name'=>$asset->name,'customer'=>$asset->customer?->name,'site'=>$asset->site?->name,
                    'health_score'=>$asset->health_score,'health_band'=>$asset->health_band,'remaining_life_months'=>$asset->remaining_life_months,
                    'recommendation'=>$asset->replacement_recommendation,
                    'reason'=>$internal?$asset->replacement_reason:$this->customerSafeReason($asset),
                ];
            });
    }

    private function customerSafeReason(Asset $asset): string
    {
        $reasons=[];
        if($asset->remaining_life_months!==null && $asset->remaining_life_months<=12)$reasons[]='Remaining useful life is approaching the recommended review window.';
        if(in_array($asset->operational_status,['BREAKDOWN','OUT_OF_SERVICE','STOPPED','ISOLATED'],true))$reasons[]='Current operating condition requires engineering attention.';
        if(in_array($asset->health_band,['ATTENTION','CRITICAL'],true))$reasons[]='Reliability and maintenance indicators require review.';
        return $reasons?implode(' ',$reasons):'Lifecycle review is recommended based on current asset condition.';
    }
}
