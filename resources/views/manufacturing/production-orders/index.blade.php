@extends('layouts.app')
@section('content')
<h1>Production Orders</h1><p><a href="{{ route('manufacturing.production-orders.create') }}">New Production Order</a></p>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<table><thead><tr><th>No</th><th>Product</th><th>Planned</th><th>Produced</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@foreach($orders as $o)<tr><td>{{ $o->order_no }}</td><td>{{ $o->product_name }}</td><td>{{ $o->planned_quantity }}</td><td>{{ $o->produced_quantity }}</td><td>{{ $o->status }}</td><td>
@if($o->status==='CREATED')<a href="{{ route('manufacturing.production-orders.edit',$o) }}">Edit</a> <form style="display:inline" method="POST" action="{{ route('manufacturing.production-orders.release',$o) }}">@csrf<button>Release</button></form>@endif
@if($o->status==='RELEASED')<form style="display:inline" method="POST" action="{{ route('manufacturing.production-orders.complete',$o) }}">@csrf<input name="produced_quantity" type="number" min="0.0001" step="0.0001" max="{{ $o->planned_quantity }}" placeholder="Produced" required><button>Complete</button></form>@endif
</td></tr>@endforeach
</tbody></table>{{ $orders->links() }}
@endsection
