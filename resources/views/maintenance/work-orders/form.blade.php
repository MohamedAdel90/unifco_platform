@extends('layouts.app')
@section('content')
<h1>{{ $order->exists?'Edit':'New' }} Maintenance Work Order</h1>
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<form method="POST" action="{{ $order->exists?route('maintenance.work-orders.update',$order):route('maintenance.work-orders.store') }}">@csrf @if($order->exists)@method('PUT')@endif
<label>Work Order No <input name="work_order_no" value="{{ old('work_order_no',$order->work_order_no) }}" required></label>
<label>Asset <select name="asset_id" required><option value="">Select</option>@foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id',$order->asset_id)==$a->id)>{{ $a->asset_code }} — {{ $a->name }}</option>@endforeach</select></label>
<label>Type <select name="maintenance_type"><option>PREVENTIVE</option><option @selected(old('maintenance_type',$order->maintenance_type)==='CORRECTIVE')>CORRECTIVE</option></select></label>
<label>Priority <select name="priority">@foreach(['LOW','NORMAL','HIGH','CRITICAL'] as $p)<option @selected(old('priority',$order->priority)===$p)>{{ $p }}</option>@endforeach</select></label>
<label>Planned Start <input type="datetime-local" name="planned_start" value="{{ old('planned_start',$order->planned_start?->format('Y-m-d\\TH:i')) }}"></label>
<button>Save</button></form>
@endsection
