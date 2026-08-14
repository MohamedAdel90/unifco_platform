@extends('layouts.app')
@section('content')
<h1>Receive Purchase Order {{ $order->po_number }}</h1>
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<form method="POST" action="{{ route('procurement.goods-receipts.store',$order) }}">@csrf
<label>Receipt No <input name="receipt_no" value="{{ old('receipt_no','GR-'.now()->format('YmdHis')) }}" required></label>
<label>Warehouse <input name="warehouse_code" value="{{ old('warehouse_code','MAIN') }}" required></label>
<table><thead><tr><th>Line</th><th>Item</th><th>Ordered</th><th>Unit Cost</th><th>Receive Qty</th></tr></thead><tbody>
@foreach($order->lines as $line)
<tr><td>{{ $line->line_no }}</td><td>{{ $line->item?->item_code }} — {{ $line->item?->name }}</td><td>{{ $line->quantity }}</td><td>{{ $line->unit_price }}</td><td><input type="number" step="0.0001" min="0" max="{{ $line->quantity }}" name="quantities[{{ $line->id }}]" value="{{ old('quantities.'.$line->id,$line->quantity) }}"></td></tr>
@endforeach
</tbody></table><button type="submit">Post Goods Receipt</button>
</form>
@endsection
