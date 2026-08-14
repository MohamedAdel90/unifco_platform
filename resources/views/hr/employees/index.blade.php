@extends('layouts.app')
@section('content')
<h1>Employees</h1><p><a href="{{ route('hr.employees.create') }}">New Employee</a></p>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
<table><thead><tr><th>No</th><th>Name</th><th>Email</th><th>Hire date</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($employees as $e)<tr><td>{{ $e->employee_no }}</td><td>{{ $e->name }}</td><td>{{ $e->email }}</td><td>{{ optional($e->hire_date)->format('Y-m-d') }}</td><td>{{ $e->status }}</td><td><a href="{{ route('hr.employees.edit',$e) }}">Edit</a> @if($e->status==='ACTIVE')<form style="display:inline" method="POST" action="{{ route('hr.employees.deactivate',$e) }}">@csrf<button>Deactivate</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $employees->links() }}
@endsection
