@extends('layouts.app')
@section('content')
<h1>{{ $customer->exists?'Edit':'New' }} Customer</h1>
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<form method="POST" action="{{ $customer->exists?route('crm.customers.update',$customer):route('crm.customers.store') }}">@csrf @if($customer->exists)@method('PUT')@endif
<label>Customer Code <input name="customer_code" value="{{ old('customer_code',$customer->customer_code) }}" required></label>
<label>Name <input name="name" value="{{ old('name',$customer->name) }}" required></label>
<label>Email <input type="email" name="email" value="{{ old('email',$customer->email) }}"></label><button>Save</button></form>
@endsection
