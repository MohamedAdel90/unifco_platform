@extends('layouts.app')
@section('content')
<h1>Procurement — Purchase Orders</h1>
@if(session('status'))<p>{{ session('status') }}</p>@endif
<table><thead><tr><th>PO</th><th>Supplier</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($orders as $po)<tr><td>{{ $po->po_number }}</td><td>{{ $po->supplier_name }}</td><td>{{ number_format($po->total,2) }}</td><td>{{ $po->status }}</td><td>@if($po->status==='DRAFT')<form method="post" action="{{ route('procurement.purchase-orders.approve',$po) }}">@csrf<button>Approve</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $orders->links() }}
@endsection
