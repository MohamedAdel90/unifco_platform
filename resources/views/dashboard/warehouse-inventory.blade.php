@if(app(\App\Services\AuthorizationService::class)->allows(auth()->user(),'inventory.warehouse.read'))
@php($warehouseSummary=app(\App\Services\Dashboard\WarehouseDashboardService::class)->summary())
<style>
.wh-summary{margin-top:16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:15px}.wh-summary-head{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:10px}.wh-summary-head h3{margin:0;color:#071f4d;font-size:13px}.wh-summary-head small{font-size:8px;color:#758399}.wh-summary-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:8px}.wh-summary-card{display:block;background:#f7f9fc;border-radius:9px;padding:9px;color:#071f4d}.wh-summary-card span{display:block;font-size:7px;color:#758399}.wh-summary-card b{font-size:16px}.wh-summary-card.alert b{color:#c51b34}@media(max-width:1100px){.wh-summary-grid{grid-template-columns:repeat(4,1fr)}}@media(max-width:600px){.wh-summary-grid{grid-template-columns:1fr 1fr}}
</style>
<section class="wh-summary"><div class="wh-summary-head"><span><h3>Warehouse & Field Inventory · المستودع وقطع الغيار</h3><small>Central warehouse + technician vehicles + site stock.</small></span><a class="btn secondary" href="{{ route('inventory.warehouse.index') }}">Open Spare Parts Control Center</a></div><div class="wh-summary-grid">
<a class="wh-summary-card" href="{{ route('inventory.warehouse.index') }}"><span>Stock Locations</span><b>{{ $warehouseSummary['locations'] }}</b></a>
<a class="wh-summary-card" href="{{ route('inventory.warehouse.index') }}"><span>Technician Vehicles</span><b>{{ $warehouseSummary['vehicle_locations'] }}</b></a>
<a class="wh-summary-card" href="{{ route('inventory.warehouse.index') }}"><span>On Hand Qty</span><b>{{ number_format($warehouseSummary['on_hand'],1) }}</b></a>
<a class="wh-summary-card" href="{{ route('inventory.warehouse.index') }}"><span>Reserved Qty</span><b>{{ number_format($warehouseSummary['reserved'],1) }}</b></a>
<a class="wh-summary-card" href="{{ route('inventory.warehouse.index') }}"><span>Available Qty</span><b>{{ number_format($warehouseSummary['available'],1) }}</b></a>
<a class="wh-summary-card {{ $warehouseSummary['pending_transfers']?'alert':'' }}" href="{{ route('inventory.warehouse.index') }}"><span>Pending Transfers</span><b>{{ $warehouseSummary['pending_transfers'] }}</b></a>
<a class="wh-summary-card {{ $warehouseSummary['in_transit']?'alert':'' }}" href="{{ route('inventory.warehouse.index') }}"><span>In Transit</span><b>{{ $warehouseSummary['in_transit'] }}</b></a>
<a class="wh-summary-card {{ $warehouseSummary['out_of_stock']?'alert':'' }}" href="{{ route('inventory.warehouse.index') }}"><span>Critical / Out of Stock</span><b>{{ $warehouseSummary['critical_alerts'] }} / {{ $warehouseSummary['out_of_stock'] }}</b></a>
</div></section>
@endif
