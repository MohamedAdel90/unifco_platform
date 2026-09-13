@extends('layouts.app')
@section('content')
<h1>Immutable Audit Trail</h1><p class="muted">Append-only security and business history. Audit entries cannot be edited or deleted from this interface.</p>
<form class="form-grid" method="GET"><input name="action" value="{{ request('action') }}" placeholder="Action"><input name="user_id" value="{{ request('user_id') }}" placeholder="User ID"><input name="ip" value="{{ request('ip') }}" placeholder="IP"><input name="entity_type" value="{{ request('entity_type') }}" placeholder="Object Type"><input name="entity_id" value="{{ request('entity_id') }}" placeholder="Object ID"><input type="date" name="from" value="{{ request('from') }}"><input type="date" name="to" value="{{ request('to') }}"><input name="correlation_id" value="{{ request('correlation_id') }}" placeholder="Correlation ID"><button class="btn">Filter</button></form>
<table><thead><tr><th>ID</th><th>Time</th><th>Actor</th><th>Action</th><th>Entity</th><th>IP / Session</th><th>Integrity</th></tr></thead><tbody>
@foreach($logs as $log)<tr><td>{{ $log->id }}</td><td>{{ $log->created_at }}</td><td>{{ $log->user_id ?: 'SYSTEM' }}</td><td>{{ $log->action }}@if($log->reason)<small style="display:block">{{ $log->reason }}</small>@endif</td><td>{{ $log->entity_type }} #{{ $log->entity_id }}</td><td>{{ $log->ip_address ?: '—' }}<small style="display:block">{{ $log->session_id ?: '—' }}</small></td><td>{{ $log->entry_hash?substr($log->entry_hash,0,12).'…':'Legacy entry' }}</td></tr>@endforeach
</tbody></table>{{ $logs->links() }}
@endsection
