@extends('layouts.app')
@section('title','Customers · UNIFCO')
@section('heading','Customers')
@section('content')
<div class="page-head"><div><h2 style="margin:0">Customer Management</h2><p class="muted">Create a customer, complete onboarding, then activate portal access and service operations.</p></div><a class="btn" href="{{ route('crm.customers.create') }}">+ New Customer</a></div>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
<div class="card" style="margin-top:16px"><table><thead><tr><th>Code</th><th>Customer</th><th>Industry / City</th><th>Email</th><th>Onboarding</th><th>Status</th><th></th></tr></thead><tbody>@forelse($customers as $c)<tr><td><strong>{{ $c->customer_code }}</strong></td><td>{{ $c->name }}</td><td>{{ $c->industry ?: '—' }}<br><small>{{ $c->city ?: '—' }}</small></td><td>{{ $c->email ?: '—' }}</td><td><span class="pill">{{ $c->onboarding_status ?: 'DRAFT' }}</span></td><td>{{ $c->status }}</td><td><a class="btn secondary" href="{{ route('crm.customers.portal',$c) }}">Onboarding Center</a> <a href="{{ route('crm.customers.edit',$c) }}">Edit</a> @if($c->status==='ACTIVE')<form style="display:inline" method="POST" action="{{ route('crm.customers.block',$c) }}">@csrf<button>Block</button></form>@endif</td></tr>@empty<tr><td colspan="7">No customers found.</td></tr>@endforelse</tbody></table></div>{{ $customers->links() }}
@endsection
