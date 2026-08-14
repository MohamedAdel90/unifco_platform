@extends('layouts.app')
@section('title','Maintenance & EAM Operations')
@section('heading','Maintenance & EAM Operations')
@section('content')
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error">{{ implode(' ', $errors->all()) }}</div>@endif

<div class="grid">
<div class="card"><div class="muted">Assets</div><div class="metric">{{ $assets->count() }}</div></div>
<div class="card"><div class="muted">Active PM Plans</div><div class="metric">{{ $plans->where('status','ACTIVE')->count() }}</div></div>
<div class="card"><div class="muted">Open / In Progress</div><div class="metric">{{ $orders->whereIn('status',['OPEN','IN_PROGRESS'])->count() }}</div></div>
<div class="card"><div class="muted">Completed Cost</div><div class="metric">{{ number_format((float)$orders->where('status','COMPLETED')->sum('total_cost'),2) }}</div></div>
</div>

<div class="card" style="margin-top:16px"><h3>Preventive Maintenance Plan</h3>
<form method="POST" action="{{ route('maintenance.operations.plans.store') }}">@csrf
<div class="form-grid"><label>Asset<select name="asset_id" required>@foreach($assets as $a)<option value="{{ $a->id }}">{{ $a->asset_code }} — {{ $a->name }}</option>@endforeach</select></label>
<label>Plan No<input name="plan_no" required></label><label>Name<input name="name" required></label>
<label>Frequency<select name="frequency_type"><option>DAYS</option><option>MONTHS</option><option>METER</option></select></label>
<label>Value<input name="frequency_value" type="number" min="1" value="1" required></label><label>Next Due Date<input name="next_due_date" type="date"></label>
<label>Next Meter<input name="next_due_meter" type="number" step="0.0001"></label><label>Priority<select name="priority"><option>NORMAL</option><option>LOW</option><option>HIGH</option><option>CRITICAL</option></select></label></div>
<button class="btn">Create Plan</button></form>
<form method="POST" action="{{ route('maintenance.operations.plans.generate') }}" style="margin-top:10px">@csrf<button class="btn secondary">Generate Due Work Orders</button></form></div>

<div class="card" style="margin-top:16px"><h3>Assets & Lifecycle</h3><table class="table"><thead><tr><th>Asset</th><th>Parent</th><th>Location</th><th>Meter</th><th>NBV</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@foreach($assets as $asset)<tr><td>{{ $asset->asset_code }} — {{ $asset->name }}</td><td>{{ $asset->parent?->asset_code }}</td><td>{{ $asset->location_code }}</td><td>{{ $asset->meter_value }}</td><td>{{ number_format((float)$asset->net_book_value,2) }}</td><td><span class="pill">{{ $asset->status }}</span></td><td>
<form method="POST" action="{{ route('maintenance.operations.assets.meter',$asset) }}" style="display:inline">@csrf<input name="reading" type="number" step="0.0001" placeholder="Meter" style="width:85px"><input type="hidden" name="reading_date" value="{{ now()->toDateString() }}"><button class="btn secondary">Meter</button></form>
<form method="POST" action="{{ route('maintenance.operations.assets.transfer',$asset) }}" style="display:inline">@csrf<input name="to_location" placeholder="Location" style="width:95px"><input type="hidden" name="transfer_date" value="{{ now()->toDateString() }}"><button class="btn secondary">Transfer</button></form>
@if($asset->status==='CAPITALIZED')<form method="POST" action="{{ route('maintenance.operations.assets.depreciate',$asset) }}" style="display:inline">@csrf<input type="hidden" name="posting_date" value="{{ now()->toDateString() }}"><button class="btn secondary">Depreciate</button></form>@endif
@if($asset->status!=='DISPOSED')<form method="POST" action="{{ route('maintenance.operations.assets.dispose',$asset) }}" style="display:inline">@csrf<button class="btn secondary">Dispose</button></form>@endif
</td></tr>@endforeach</tbody></table></div>

<div class="card" style="margin-top:16px"><h3>Maintenance Work Orders</h3><table class="table"><thead><tr><th>No</th><th>Asset</th><th>Type</th><th>Status</th><th>Downtime</th><th>Cost</th><th>Execution</th></tr></thead><tbody>
@foreach($orders as $order)<tr><td>{{ $order->work_order_no }}</td><td>{{ $order->asset?->asset_code }}</td><td>{{ $order->maintenance_type }}</td><td><span class="pill">{{ $order->status }}</span></td><td>{{ $order->downtime_minutes }} min</td><td>{{ number_format((float)$order->total_cost,2) }}</td><td>
@if($order->status==='OPEN')<form method="POST" action="{{ route('maintenance.operations.work-orders.start',$order) }}">@csrf<button class="btn">Start</button></form>@endif
@if($order->status==='IN_PROGRESS')
<form method="POST" action="{{ route('maintenance.operations.work-orders.labor',$order) }}" style="display:inline">@csrf<input name="hours" type="number" step="0.25" placeholder="Hours" style="width:70px"><input name="hourly_rate" type="number" step="0.01" placeholder="Rate" style="width:70px"><button class="btn secondary">Labor</button></form>
<form method="POST" action="{{ route('maintenance.operations.work-orders.material',$order) }}" style="display:inline">@csrf<select name="item_id">@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->item_code }}</option>@endforeach</select><input name="warehouse_code" value="MAIN" style="width:70px"><input name="quantity" type="number" step="0.0001" placeholder="Qty" style="width:60px"><input name="unit_cost" type="number" step="0.01" placeholder="Cost" style="width:65px"><button class="btn secondary">Issue</button></form>
<form method="POST" action="{{ route('maintenance.operations.work-orders.complete',$order) }}" style="display:inline">@csrf<input name="downtime_minutes" type="number" min="0" placeholder="Down min" style="width:80px"><input name="failure_code" placeholder="Failure" style="width:80px"><input name="external_cost" type="number" step="0.01" placeholder="External" style="width:80px"><button class="btn">Complete</button></form>
@endif
</td></tr>@endforeach</tbody></table></div>
@endsection
