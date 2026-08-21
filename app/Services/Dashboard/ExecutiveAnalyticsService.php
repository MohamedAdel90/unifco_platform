<?php

namespace App\Services\Dashboard;

use App\Services\AuthorizationService;
use Illuminate\Support\Collection;

class ExecutiveAnalyticsService
{
    public function __construct(private AuthorizationService $authorization) {}

    public function build(Collection $assets, Collection $openWorkOrders, Collection $serviceRequests, Collection $slaBreaches, Collection $contracts, Collection $stockAlerts, array $capabilities=[]): array
    {
        if(!$capabilities && auth()->check()){
            $user=auth()->user();
            $capabilities=[
                'maintenance'=>$this->authorization->allows($user,'maintenance.work_order.read'),
                'eam'=>$this->authorization->allows($user,'eam.asset.read'),
                'crm_manage'=>$this->authorization->allows($user,'crm.customer.manage'),
                'inventory'=>$this->authorization->allows($user,'inventory.stock.read'),
            ];
        }

        $assetMap=$assets->keyBy('id');
        $backlogAging=['0-1 Days'=>0,'2-7 Days'=>0,'8-30 Days'=>0,'31+ Days'=>0];
        foreach($openWorkOrders as $wo){
            $age=max(0,(int)optional($wo->created_at)->diffInDays(now()));
            if($age<=1)$backlogAging['0-1 Days']++;
            elseif($age<=7)$backlogAging['2-7 Days']++;
            elseif($age<=30)$backlogAging['8-30 Days']++;
            else $backlogAging['31+ Days']++;
        }

        $healthDistribution=['HEALTHY'=>0,'WATCH'=>0,'ATTENTION'=>0,'CRITICAL'=>0,'PENDING'=>0];
        foreach($assets as $asset){
            $band=strtoupper((string)($asset->health_band ?: 'PENDING'));
            if(!array_key_exists($band,$healthDistribution))$band='PENDING';
            $healthDistribution[$band]++;
        }

        $priorities=['EMERGENCY','CRITICAL','HIGH','MEDIUM','LOW'];
        $slaByPriority=[];
        foreach($priorities as $priority){
            $total=$serviceRequests->filter(fn($r)=>strtoupper((string)$r->priority)===$priority)->count();
            $breaches=$slaBreaches->filter(fn($r)=>strtoupper((string)$r->priority)===$priority)->count();
            if($total===0 && $breaches===0) continue;
            $slaByPriority[]=(object)['priority'=>$priority,'total'=>$total,'breaches'=>$breaches,'performance'=>$total?round(max(0,100-($breaches/$total*100)),1):100];
        }

        $customerStats=[];$siteStats=[];
        foreach($openWorkOrders as $wo){
            $asset=$assetMap->get($wo->asset_id);
            if(!$asset) continue;
            $customerId=(int)$asset->customer_id;
            if($customerId){
                $key='c'.$customerId;
                if(!isset($customerStats[$key]))$customerStats[$key]=['name'=>$asset->customer?->name ?: 'Customer','open'=>0,'critical'=>0];
                $customerStats[$key]['open']++;
                if(in_array(strtoupper((string)$wo->priority),['CRITICAL','EMERGENCY'],true))$customerStats[$key]['critical']++;
            }
            $siteId=(int)$asset->customer_site_id;
            if($siteId){
                $key='s'.$siteId;
                if(!isset($siteStats[$key]))$siteStats[$key]=['name'=>$asset->site?->name ?: 'Site','open'=>0,'critical'=>0];
                $siteStats[$key]['open']++;
                if(in_array(strtoupper((string)$wo->priority),['CRITICAL','EMERGENCY'],true))$siteStats[$key]['critical']++;
            }
        }
        $topCustomers=collect($customerStats)->sortByDesc(fn($x)=>($x['critical']*10)+$x['open'])->take(6)->values();
        $topSites=collect($siteStats)->sortByDesc(fn($x)=>($x['critical']*10)+$x['open'])->take(6)->values();

        $contractExpiry=['0-30 Days'=>0,'31-60 Days'=>0,'61-90 Days'=>0,'90+ Days'=>0];
        foreach($contracts as $contract){
            if(!$contract->ends_on || $contract->ends_on->isPast()) continue;
            $days=(int)today()->diffInDays($contract->ends_on,false);
            if($days<=30)$contractExpiry['0-30 Days']++;
            elseif($days<=60)$contractExpiry['31-60 Days']++;
            elseif($days<=90)$contractExpiry['61-90 Days']++;
            else $contractExpiry['90+ Days']++;
        }

        $exceptions=collect();
        if($capabilities['eam']??false){
            foreach($assets->filter(fn($a)=>in_array($a->health_band,['CRITICAL','ATTENTION'],true))->sortBy('health_score')->take(4) as $asset){
                $exceptions->push((object)['severity'=>$asset->health_band==='CRITICAL'?'CRITICAL':'HIGH','type'=>'ASSET','title'=>$asset->asset_code.' · '.$asset->name,'detail'=>'Health '.($asset->health_score ?? '—').'/100 · '.str_replace('_',' ',$asset->replacement_recommendation ?: 'Engineering review'),'url'=>route('eam.assets.show',$asset)]);
            }
        }
        if($capabilities['maintenance']??false){
            foreach($openWorkOrders->filter(fn($w)=>in_array(strtoupper((string)$w->priority),['CRITICAL','EMERGENCY'],true))->take(4) as $wo){
                $exceptions->push((object)['severity'=>'CRITICAL','type'=>'WORK ORDER','title'=>$wo->work_order_no,'detail'=>str_replace('_',' ',$wo->maintenance_type).' · '.$wo->status,'url'=>route('maintenance.work-orders.show',$wo)]);
            }
        }
        if($capabilities['crm_manage']??false){
            foreach($slaBreaches->take(3) as $request){
                $exceptions->push((object)['severity'=>'HIGH','type'=>'SLA','title'=>$request->request_no,'detail'=>'SLA breached · '.$request->priority.' · '.$request->status,'url'=>route('admin.public-requests.index')]);
            }
        }
        if(($capabilities['inventory']??false) && ($capabilities['eam']??false)){
            foreach($stockAlerts->where('computed_alert_status','OUT_OF_STOCK')->take(3) as $stock){
                $exceptions->push((object)['severity'=>'CRITICAL','type'=>'SPARE PART','title'=>$stock->item_code.' · '.$stock->item_name,'detail'=>$stock->asset_code.' · Out of stock','url'=>route('eam.health.index')]);
            }
        }

        return ['backlogAging'=>$backlogAging,'healthDistribution'=>$healthDistribution,'slaByPriority'=>$slaByPriority,'topCustomers'=>$topCustomers,'topSites'=>$topSites,'contractExpiry'=>$contractExpiry,'exceptions'=>$exceptions->take(10)->values()];
    }
}
