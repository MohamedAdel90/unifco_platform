@extends('layouts.app')
@section('title','Service Request Operations')
@section('content')
<style>
.ops-shell{display:grid;gap:18px}.ops-hero{background:linear-gradient(135deg,#071a33,#123c73);color:#fff;border-radius:18px;padding:22px;box-shadow:0 14px 34px rgba(7,26,51,.16)}.ops-hero h1{margin:0 0 6px;font-size:24px}.ops-hero p{margin:0;color:#d9e8ff}.ops-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.ops-kpi,.ops-panel{background:#fff;border:1px solid #e6edf5;border-radius:14px;box-shadow:0 7px 22px rgba(15,37,67,.05)}.ops-kpi{padding:15px}.ops-kpi b{display:block;font-size:25px;color:#102f57;margin-top:4px}.ops-panel{padding:16px}.ops-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center}.ops-toolbar select,.ops-toolbar button,.ops-table select,.ops-table input{height:38px;border:1px solid #d6e0eb;border-radius:9px;padding:0 10px;background:#fff}.ops-btn{border:0!important;background:#123c73!important;color:#fff!important;font-weight:700;cursor:pointer}.ops-btn-danger{background:#b4232d!important}.ops-table-wrap{overflow:auto}.ops-table{width:100%;border-collapse:collapse;min-width:1040px}.ops-table th{font-size:12px;color:#5d7088;text-align:left;padding:11px;border-bottom:1px solid #e7edf4;background:#f8fafc;position:sticky;top:0}.ops-table td{padding:12px 11px;border-bottom:1px solid #edf1f5;vertical-align:top}.ops-table tr:hover td{background:#fbfdff}.pill{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:11px;font-weight:800;background:#eef4fb;color:#26496e}.pill.red{background:#fff0f1;color:#aa2430}.pill.amber{background:#fff7e7;color:#8b6100}.muted{color:#718198;font-size:12px}.ops-actions{display:grid;gap:7px;min-width:250px}.ops-actions form{display:flex;gap:6px}.ops-actions select,.ops-actions input{min-width:0;flex:1}@media(max-width:900px){.ops-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ops-hero{border-radius:14px}}@media(max-width:520px){.ops-grid{grid-template-columns:1fr 1fr}.ops-hero h1{font-size:20px}}
</style>
<div class="ops-shell">
    <section class="ops-hero">
        <div class="muted" style="color:#a9c5e7;font-weight:800">OPERATIONS MANAGER · SERVICE DELIVERY</div>
        <h1>Service Request Control Center</h1>
        <p>Scoped triage, assignment, SLA watch and operational escalation. Financial and contractual approvals remain outside this workspace.</p>
    </section>

    @if(session('status'))<div class="ops-panel" style="border-left:4px solid #1f8a5b">{{ session('status') }}</div>@endif

    <section class="ops-grid">
        <div class="ops-kpi"><span class="muted">Open Requests</span><b>{{ $metrics['open'] }}</b></div>
        <div class="ops-kpi"><span class="muted">Unassigned</span><b>{{ $metrics['unassigned'] }}</b></div>
        <div class="ops-kpi"><span class="muted">Emergency / Critical</span><b>{{ $metrics['emergency'] }}</b></div>
        <div class="ops-kpi"><span class="muted">SLA Overdue</span><b>{{ $metrics['sla_overdue'] }}</b></div>
        <div class="ops-kpi"><span class="muted">Escalated</span><b>{{ $metrics['escalated'] }}</b></div>
    </section>

    <section class="ops-panel">
        <form method="get" class="ops-toolbar">
            <select name="status"><option value="">All statuses</option>@foreach(['OPEN','IN_PROGRESS','RESOLVED','CLOSED','CANCELLED'] as $v)<option @selected(request('status')===$v)>{{ $v }}</option>@endforeach</select>
            <select name="priority"><option value="">All priorities</option>@foreach(['EMERGENCY','CRITICAL','URGENT','HIGH','NORMAL','LOW'] as $v)<option @selected(request('priority')===$v)>{{ $v }}</option>@endforeach</select>
            <select name="stage"><option value="">All workflow stages</option>@foreach(['NEW','TRIAGE','ASSIGNED','IN_PROGRESS','WAITING_PARTS','ON_HOLD','ESCALATED'] as $v)<option @selected(request('stage')===$v)>{{ $v }}</option>@endforeach</select>
            <button class="ops-btn" type="submit">Apply filters</button>
            <a href="{{ route('operations-manager.dashboard') }}" style="margin-left:auto;color:#123c73;font-weight:700;text-decoration:none">← Command Center</a>
        </form>
    </section>

    <section class="ops-panel ops-table-wrap">
        <table class="ops-table">
            <thead><tr><th>Request</th><th>Customer / Site</th><th>Priority</th><th>Workflow</th><th>SLA</th><th>Assigned</th><th>Operational Action</th></tr></thead>
            <tbody>
            @forelse($requests as $item)
                @php($overdue=$item->current_stage_due_at && $item->current_stage_due_at->isPast())
                <tr>
                    <td><strong>{{ $item->request_no }}</strong><div class="muted">{{ $item->subject }}</div><div class="muted">{{ $item->request_type ?: $item->service_category }}</div></td>
                    <td>{{ $item->company_name }}<div class="muted">{{ $item->site_city ?: '—' }}</div></td>
                    <td><span class="pill {{ in_array($item->priority,['EMERGENCY','CRITICAL'])?'red':(in_array($item->priority,['URGENT','HIGH'])?'amber':'') }}">{{ $item->priority }}</span></td>
                    <td><span class="pill {{ $item->workflow_stage==='ESCALATED'?'red':'' }}">{{ $item->workflow_stage }}</span><div class="muted">{{ $item->status }}</div></td>
                    <td><span class="pill {{ $overdue?'red':'' }}">{{ $overdue?'OVERDUE':'ON TRACK' }}</span><div class="muted">{{ $item->current_stage_due_at?->format('Y-m-d H:i') ?: 'No active stage due date' }}</div></td>
                    <td>{{ $assignees->firstWhere('id',$item->assigned_engineer_id)?->name ?: 'Unassigned' }}</td>
                    <td class="ops-actions">
                        <form method="post" action="{{ route('operations-manager.service-requests.assign',$item) }}">@csrf
                            <select name="assigned_engineer_id" required><option value="">Assign...</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected($item->assigned_engineer_id==$assignee->id)>{{ $assignee->name }} · {{ $assignee->role }}</option>@endforeach</select>
                            <button class="ops-btn">Assign</button>
                        </form>
                        <form method="post" action="{{ route('operations-manager.service-requests.escalate',$item) }}">@csrf
                            <input name="reason" required maxlength="1000" placeholder="Escalation reason">
                            <button class="ops-btn ops-btn-danger">Escalate</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:35px;color:#718198">No service requests are visible in your current scope.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
