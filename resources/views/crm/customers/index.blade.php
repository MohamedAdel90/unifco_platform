@extends('layouts.app')
@section('content')
<h1>Customers</h1><p><a href="{{ route('crm.customers.create') }}">New Customer</a></p>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
<table><thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($customers as $c)<tr><td>{{ $c->customer_code }}</td><td>{{ $c->name }}</td><td>{{ $c->email }}</td><td>{{ $c->status }}</td><td><a href="{{ route('crm.customers.edit',$c) }}">Edit</a> @if($c->status==='ACTIVE')<form style="display:inline" method="POST" action="{{ route('crm.customers.block',$c) }}">@csrf<button>Block</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $customers->links() }}
@endsection
