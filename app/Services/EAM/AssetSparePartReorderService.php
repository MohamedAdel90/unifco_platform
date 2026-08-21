<?php

namespace App\Services\EAM;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssetSparePartReorderService
{
    public function alerts(?int $customerId=null, ?int $siteId=null): Collection
    {
        $rows=DB::table('asset_spare_parts')
            ->join('assets','assets.id','=','asset_spare_parts.asset_id')
            ->join('items','items.id','=','asset_spare_parts.item_id')
            ->leftJoin('customers','customers.id','=','assets.customer_id')
            ->leftJoin('customer_sites','customer_sites.id','=','assets.customer_site_id')
            ->when($customerId,fn($q)=>$q->where('assets.customer_id',$customerId))
            ->when($siteId,fn($q)=>$q->where('assets.customer_site_id',$siteId))
            ->select(
                'asset_spare_parts.*','assets.tenant_id','assets.asset_code','assets.name as asset_name','assets.customer_id','assets.customer_site_id',
                'items.item_code','items.name as item_name','items.uom','customers.name as customer_name','customer_sites.name as site_name'
            )->get();

        return $rows->map(function($row){
            $stock=DB::table('stock_balances')
                ->where('tenant_id',$row->tenant_id)
                ->where('item_id',$row->item_id)
                ->when($row->preferred_warehouse_code,fn($q)=>$q->where('warehouse_code',$row->preferred_warehouse_code))
                ->selectRaw('COALESCE(SUM(quantity),0) as qty, COALESCE(SUM(reserved_quantity),0) as reserved')
                ->first();
            $quantity=(float)($stock->qty??0);
            $reserved=(float)($stock->reserved??0);
            $available=max(0,$quantity-$reserved);
            $reorder=(float)$row->reorder_level;
            $min=(float)$row->min_stock;
            $status='OK';
            if($available<=0 && ($reorder>0 || $row->critical_spare)) $status='OUT_OF_STOCK';
            elseif($available<=$reorder && $reorder>0) $status='REORDER';
            elseif($available<$min && $min>0) $status='LOW_STOCK';
            $row->stock_quantity=$quantity;
            $row->reserved_quantity=$reserved;
            $row->available_quantity=$available;
            $row->computed_alert_status=$status;
            $row->suggested_order_quantity=max(0,(float)$row->max_stock-$available);
            return $row;
        });
    }

    public function syncStatuses(?int $customerId=null): Collection
    {
        $alerts=$this->alerts($customerId);
        foreach($alerts as $alert){
            if($alert->reorder_alert_status!==$alert->computed_alert_status){
                DB::table('asset_spare_parts')->where('id',$alert->id)->update([
                    'reorder_alert_status'=>$alert->computed_alert_status,'updated_at'=>now(),
                ]);
            }
        }
        return $alerts;
    }
}
