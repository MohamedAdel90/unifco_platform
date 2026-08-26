@extends('layouts.app')
@section('content')
<h1>Approval Inbox</h1>
@if(session('status')) <p class="notice">{{ session('status') }}</p> @endif
<div style="display:flex;gap:10px;flex-wrap:wrap;margin:12px 0 18px">
    <a class="pill" href="{{ route('workflow.approvals.index',['status'=>'PENDING']) }}">Pending: {{ $pendingCount }}</a>
    <a class="pill" href="{{ route('workflow.approvals.index',['breached'=>1]) }}" style="background:#fdebed;color:#b42239">SLA Breached: {{ $breachedCount }}</a>
    <a class="pill" href="{{ route('workflow.approvals.index') }}">All</a>
</div>
<table>
<thead><tr><th>ID</th><th>Request</th><th>Step / Role</th><th>SLA</th><th>Status</th><th>Decision</th></tr></thead>
<tbody>
@forelse($approvals as $approval)
@php
    $isBreached = $approval->status==='PENDING' && $approval->due_at && $approval->due_at->isPast();
    $remaining = $approval->due_at ? now()->diffForHumans($approval->due_at, ['parts'=>2,'short'=>true]) : '—';
@endphp
<tr>
    <td>#{{ $approval->id }}</td>
    <td>{{ class_basename($approval->entity_type) }} #{{ $approval->entity_id }}<br><small>{{ $approval->workflow_key ?: 'General approval' }}</small></td>
    <td><strong>{{ str_replace('_',' ',(string)$approval->action) }}</strong><br><small>{{ str_replace('_',' ',(string)$approval->approval_role) }} · Step {{ $approval->step_order }}</small></td>
    <td style="color:{{ $isBreached ? '#b42239' : 'inherit' }}"><strong>{{ $isBreached ? 'BREACHED' : $remaining }}</strong><br><small>{{ $approval->sla_minutes ? $approval->sla_minutes.' min SLA' : 'No SLA' }}</small></td>
    <td>{{ $approval->status }}</td>
    <td>
    @if($approval->status==='PENDING')
    <form method="POST" action="{{ route('workflow.approvals.decide',$approval) }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">@csrf
        <select name="decision"><option value="APPROVED">Approve</option><option value="RETURNED">Return for correction</option><option value="REJECTED">Reject</option></select>
        <input name="note" placeholder="Decision note / reason">
        <button>Submit</button>
    </form>
    @else
        <small>{{ $approval->decision_note ?: '—' }}</small>
    @endif
    </td>
</tr>
@empty
<tr><td colspan="6">No approval requests found.</td></tr>
@endforelse
</tbody>
</table>
{{ $approvals->links() }}
@endsection
