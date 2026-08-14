@extends('layouts.app')
@section('content')
<h1>Maintenance Work Orders</h1><p><a href="{{ route('maintenance.work-orders.create') }}">New Work Order</a></p>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<table><thead><tr><th>No</th><th>Asset</th><th>Type</th><th>Priority</th><th>Planned</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@foreach($orders as $o)<tr><td>{{ $o->work_order_no }}</td><td>{{ $o->asset?->asset_code }} — {{ $o->asset?->name }}</td><td>{{ $o->maintenance_type }}</td><td>{{ $o->priority }}</td><td>{{ optional($o->planned_start)->format('Y-m-d H:i') }}</td><td>{{ $o->status }}</td><td>@if($o->status==='OPEN')<a href="{{ route('maintenance.work-orders.edit',$o) }}">Edit</a> <form style="display:inline" method="POST" action="{{ route('maintenance.work-orders.complete',$o) }}">@csrf<button>Complete</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $orders->links() }}
@endsection
