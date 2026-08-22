<?php

namespace App\Services\Dashboard;

use App\Models\InventoryTransferOrder;
use App\Models\Warehouse;
use App\Services\EAM\AssetSparePartReorderService;
use Illuminate\Support\Facades\DB;

class WarehouseDashboardService
{
    public function __construct(private AssetSparePartReorderService $reorder) {}

    public function summary(): array
    {
        $tenantId=auth()->user()->tenant_id;
        $warehouses=Warehouse::where('status','ACTIVE')->get();
        $codes=$warehouses->pluck('code');
        $balances=DB::table('stock_balances')->where('tenant_id',$tenantId)->whereIn('warehouse_code',$codes)->get();
        $alerts=$this->reorder->alerts()->filter(fn($row)=>$row->computed_alert_status!=='OK');

        return [
            'locations'=>$warehouses->count(),
            'vehicle_locations'=>$warehouses->where('location_type','VEHICLE')->count(),
            'on_hand'=>(float)$balances->sum('quantity'),
            'reserved'=>(float)$balances->sum('reserved_quantity'),
            'available'=>(float)$balances->sum(fn($row)=>max(0,(float)$row->quantity-(float)$row->reserved_quantity)),
            'pending_transfers'=>InventoryTransferOrder::where('status','REQUESTED')->count(),
            'in_transit'=>InventoryTransferOrder::where('status','IN_TRANSIT')->count(),
            'critical_alerts'=>$alerts->count(),
            'out_of_stock'=>$alerts->where('computed_alert_status','OUT_OF_STOCK')->count(),
        ];
    }
}
