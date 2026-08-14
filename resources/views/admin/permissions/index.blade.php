@extends('layouts.app')
@section('content')
<h1>Role Permissions</h1>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
<form method="POST" action="{{ route('admin.permissions.store') }}">@csrf <input name="role_code" placeholder="ROLE" required><input name="permission_code" placeholder="module.resource.action" required><button>Grant</button></form>
<table><thead><tr><th>Role</th><th>Permission</th><th></th></tr></thead><tbody>
@foreach($rows as $row)<tr><td>{{ $row->role_code }}</td><td>{{ $row->permission_code }}</td><td><form method="POST" action="{{ route('admin.permissions.destroy',$row->id) }}">@csrf @method('DELETE')<button>Revoke</button></form></td></tr>@endforeach
</tbody></table>
@endsection
