@extends('layouts.app')
@section('content')
<h1>{{ $order->exists?'Edit':'New' }} Production Order</h1>
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<form method="POST" action="{{ $order->exists?route('manufacturing.production-orders.update',$order):route('manufacturing.production-orders.store') }}">@csrf @if($order->exists)@method('PUT')@endif
<label>Order No <input name="order_no" value="{{ old('order_no',$order->order_no) }}" required></label>
<label>Product <input name="product_name" value="{{ old('product_name',$order->product_name) }}" required></label>
<label>Planned Quantity <input type="number" step="0.0001" min="0.0001" name="planned_quantity" value="{{ old('planned_quantity',$order->planned_quantity) }}" required></label>
<button>Save</button></form>
@endsection
