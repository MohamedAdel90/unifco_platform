@extends('layouts.app')
@section('content')
<h1>Inventory — Stock Movement</h1>
@if(session('status'))<p>{{ session('status') }}</p>@endif
@if($errors->any())<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="post" action="{{ route('inventory.stock.move') }}">@csrf
<select name="item_id" required>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->name }}</option>@endforeach</select>
<input name="warehouse_code" value="MAIN" required>
<select name="movement_type"><option>RECEIPT</option><option>ISSUE</option></select>
<input type="number" step="0.0001" min="0.0001" name="quantity" required>
<input name="idempotency_key" value="{{ Illuminate\Support\Str::uuid() }}" required>
<button>Post Movement</button></form>
@endsection
