@extends('layouts.app')
@section('content')
<h1>{{ $employee->exists?'Edit':'New' }} Employee</h1>
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<form method="POST" action="{{ $employee->exists?route('hr.employees.update',$employee):route('hr.employees.store') }}">@csrf @if($employee->exists)@method('PUT')@endif
<label>Employee No <input name="employee_no" value="{{ old('employee_no',$employee->employee_no) }}" required></label>
<label>Name <input name="name" value="{{ old('name',$employee->name) }}" required></label>
<label>Email <input type="email" name="email" value="{{ old('email',$employee->email) }}"></label>
<label>Hire Date <input type="date" name="hire_date" value="{{ old('hire_date',optional($employee->hire_date)->format('Y-m-d')) }}"></label>
<button>Save</button></form>
@endsection
