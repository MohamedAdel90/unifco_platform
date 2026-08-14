@extends('layouts.app')
@section('content')
<h1>Audit Trail</h1>
<form method="GET"><input name="action" value="{{ request('action') }}" placeholder="Action contains"><input name="correlation_id" value="{{ request('correlation_id') }}" placeholder="Correlation ID"><button>Filter</button></form>
<table><thead><tr><th>ID</th><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Correlation</th></tr></thead><tbody>
@foreach($logs as $log)<tr><td>{{ $log->id }}</td><td>{{ $log->created_at }}</td><td>{{ $log->user_id }}</td><td>{{ $log->action }}</td><td>{{ $log->entity_type }} #{{ $log->entity_id }}</td><td>{{ $log->correlation_id }}</td></tr>@endforeach
</tbody></table>{{ $logs->links() }}
@endsection
