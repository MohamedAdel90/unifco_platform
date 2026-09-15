@extends('layouts.app')
@section('title','Operations Manager')
@section('heading','Operations Manager')
@section('content')
<style>
.ops-shell{display:grid;gap:16px}.ops-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#132137 0%,#1e315b 72%,#263f74 100%);color:#fff;border-radius:18px;padding:24px 26px;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 14px 34px rgba(19,33,55,.15)}.ops-hero:after{content:"";position:absolute;width:230px;height:230px;border:40px solid rgba(255,255,255,.045);border-radius:50%;right:-80px;top:-125px}.ops-hero>*{position:relative;z-index:1}.ops-hero small{display:block;color:#b8c6da;text-transform:uppercase;letter-spacing:.14em;font-size:9px;font-weight:800;margin-bottom:7px}.ops-hero h1{margin:0 0 6px;font-size:25px}.ops-hero p{margin:0;color:#d3deed;font-size:12px;max-width:720px;line-height:1.55}.ops-hero-side{display:grid;gap:7px;justify-items:end}.ops-badge{padding:8px 11px;border:1px solid #ffffff2b;border-radius:999px;background:#ffffff10;font-size:10px;font-weight:800;white-space:nowrap}.ops-health{font-size:11px;color:#dbe5f3}.ops-health strong{font-size:20px;color:#fff;margin-inline-start:5px}.ops-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.ops-kpi{position:relative;background:#fff;border:1px solid #e4e9f0;border-radius:13px;padding:15px;box-shadow:0 4px 14px rgba(19,33,55,.035)}.ops-kpi small{display:block;color:#738198;font-size:9px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px}.ops-kpi strong{font-size:25px;color:#1e315b}.ops-kpi.alert{border-inline-start:3px solid #ce122d}.ops-kpi.alert strong{color:#b11d34}.ops-kpi.warn{border-inline-start:3px solid #d59b18}.ops-kpi .hint{font-size:9px;color:#8a96a8;margin-top:5px}.ops-actions{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px}.ops-actions a{min-height:52px;padding:10px;border:1px solid #e1e7ef;border-radius:11px;text-decoration:none;color:#1e315b;background:#fff;font-size:10px;font-weight:800;text-align:center;display:flex;align-items:center;justify-content:center;transition:.16s ease}.ops-actions a:hover{transform:translateY(-1px);background:#f6f8fb;box-shadow:0 5px 14px rgba(19,33,55,.06)}.ops-actions a.primary{background:#1e315b;color:#fff;border-color:#1e315b}.ops-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:17px;box-shadow:0 4px 16px rgba(19,33,55,.03)}.ops-card h3{margin:0 0 4px;color:#132137;font-size:15px}.ops-card .sub{color:#7a8799;font-size:10px;margin-bottom:12px}.ops-section-head{display:flex;justify-content:space-between;align-items:center;gap:12px}.ops-section-head a{font-size:10px;font-weight:800;color:#1e315b;text-decoration:none}.ops-attention{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px}.ops-attention a{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px;border:1px solid #e5eaf0;border-radius:11px;text-decoration:none;background:#fbfcfe;color:#35435a;font-size:10px;font-weight:750}.ops-attention strong{font-size:19px;color:#1e315b}.ops-attention a.danger{background:#fff7f8;border-color:#f3d9de}.ops-attention a.danger strong{color:#b11d34}.ops-attention a.warning{background:#fffbf1;border-color:#f2e4bc}.ops-attention a.warning strong{color:#9a6a00}.ops-grid{display:grid;grid-template-columns:1.45fr .95fr;gap:14px}.ops-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}.ops-table{width:100%;border-collapse:collapse;margin:0;min-width:620px}.ops-table th,.ops-table td{padding:10px 8px;border-bottom:1px solid #edf1f5;font-size:11px;text-align:start}.ops-table th{font-size:9px;color:#7a8799;text-transform:uppercase;letter-spacing:.05em}.ops-table a{color:#1e315b;font-weight:800;text-decoration:none}.ops-pill{display:inline-flex;padding:4px 7px;border-radius:999px;background:#eef3fa;color:#1e315b;font-size:9px;font-weight:800}.ops-pill.hot{background:#fdecef;color:#b11d34}.ops-pill.stage{background:#f1f4f8;color:#55647a}.ops-status{display:grid;gap:7px}.ops-status-row{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #eef1f5;padding:9px 0;font-size:11px}.ops-status-row:last-child{border-bottom:0}.ops-control-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.ops-note{border-inline-start:3px solid #ce122d;padding:11px 13px;background:#fff7f8;border-radius:8px;color:#6f2936;font-size:10px;line-height:1.55}@media(max-width:1180px){.ops-attention{grid-template-columns:repeat(3,1fr)}.ops-actions{grid-template-columns:repeat(3,1fr)}}@media(max-width:850px){.ops-kpis{grid-template-columns:repeat(2,1fr)}.ops-grid,.ops-control-grid{grid-template-columns:1fr}.ops-attention{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.ops-hero{align-items:flex-start;flex-direction:column;padding:20px}.ops-hero-side{justify-items:start}.ops-badge{white-space:normal}.ops-kpis,.ops-attention,.ops-actions{grid-template-columns:1fr 1fr}.ops-kpi strong{font-size:23px}}@media(max-width:390px){.ops-actions,.ops-attention{grid-template-columns:1fr}}
</style>
<div class="ops-shell">
<section class="ops-hero">
  <div><small>Operations · Live Control · Scope Based</small><h1>Operations Command Center</h1><p>One operational view for service demand, SLA risk, work execution, field activity, projects, sites and assets. Priorities below are limited to your assigned scope.</p></div>
  <div class="ops-hero-side"><div class="ops-badge">Scope Enforced · Business Operations</div><div class="ops-health">SLA compliance <strong>{{ $metrics['sla_compliance'] }}%</strong></div></div>
</section>

<section class="ops-kpis">
  <div class="ops-kpi"><small>Open Service Requests</small><strong>{{ $metrics['open_service_requests'] }}</strong><div class="hint">{{ $metrics['in_progress_requests'] }} currently in progress</div></div>
  <div class="ops-kpi {{ $metrics['attention_total'] ? 'alert' : '' }}"><small>Action Required</small><strong>{{ $metrics['attention_total'] }}</strong><div class="hint">Operational exceptions needing attention</div></div>
  <div class="ops-kpi {{ $metrics['sla_breaches'] ? 'alert' : '' }}"><small>SLA Breaches</small><strong>{{ $metrics['sla_breaches'] }}</strong><div class="hint">{{ $metrics['sla_compliance'] }}% current compliance</div></div>
  <div class="ops-kpi {{ $metrics['unassigned_requests'] ? 'warn' : '' }}"><small>Unassigned Requests</small><strong>{{ $metrics['unassigned_requests'] }}</strong><div class="hint">Require triage / assignment</div></div>
  <div class="ops-kpi"><small>Open Work Orders</small><strong>{{ $metrics['open_work_orders'] }}</strong></div>
  <div class="ops-kpi {{ $metrics['emergency_work_orders'] ? 'alert' : '' }}"><small>Emergency / Critical</small><strong>{{ $metrics['emergency_work_orders'] }}</strong></div>
  <div class="ops-kpi {{ $metrics['overdue_work_orders'] ? 'warn' : '' }}"><small>Overdue Work Orders</small><strong>{{ $metrics['overdue_work_orders'] }}</strong></div>
  <div class="ops-kpi"><small>Operational Footprint</small><strong>{{ $metrics['active_projects'] }}</strong><div class="hint">{{ $metrics['customers_in_scope'] }} customers · {{ $metrics['active_assets'] }} assets</div></div>
</section>

<section class="ops-actions">
  <a class="primary" href="{{ route('operations-manager.service-requests.index') }}">Service Requests & SLA</a>
  <a href="{{ route('maintenance.work-orders.index') }}">Work Orders</a>
  <a href="{{ route('field.operations') }}">Field Operations</a>
  <a href="{{ route('operations-manager.project-sites') }}">Projects, Sites & Capacity</a>
  <a href="{{ route('operations-manager.reports') }}">Operational Reports</a>
  <a href="{{ route('crm.customers.index') }}">Customers</a>
</section>

<section class="ops-card">
  <div class="ops-section-head"><div><h3>Action Required</h3><div class="sub">Exceptions are ordered for operational intervention, not passive reporting.</div></div><a href="{{ route('operations-manager.service-requests.index') }}">Open control queue →</a></div>
  <div class="ops-attention">
    @foreach($actionRequired as $item)
      <a class="{{ $item['tone'] }}" href="{{ $item['url'] }}"><span>{{ $item['label'] }}</span><strong>{{ $item['count'] }}</strong></a>
    @endforeach
  </div>
</section>

<section class="ops-grid">
  <div class="ops-card">
    <div class="ops-section-head"><div><h3>Service Request Control Queue</h3><div class="sub">Urgency, workflow stage and SLA deadline in one queue.</div></div><a href="{{ route('operations-manager.service-requests.index') }}">View all →</a></div>
    <div class="ops-table-wrap"><table class="ops-table"><thead><tr><th>Request</th><th>Priority</th><th>Stage</th><th>Assignment</th><th>SLA Due</th></tr></thead><tbody>
      @forelse($recentServiceRequests as $sr)
        <tr><td><a href="{{ route('operations-manager.service-requests.index') }}">{{ $sr->request_no }}</a></td><td><span class="ops-pill {{ in_array($sr->priority,['EMERGENCY','CRITICAL','URGENT']) ? 'hot' : '' }}">{{ $sr->priority }}</span></td><td><span class="ops-pill stage">{{ $sr->workflow_stage }}</span></td><td>{{ $sr->assigned_engineer_id ? 'Assigned' : 'Unassigned' }}</td><td>{{ optional($sr->current_stage_due_at)->format('d M Y H:i') ?: '—' }}</td></tr>
      @empty<tr><td colspan="5" class="muted">No open service requests are currently visible in your scope.</td></tr>@endforelse
    </tbody></table></div>
  </div>
  <div class="ops-card"><h3>Work Order Status</h3><div class="sub">Execution distribution inside the assigned scope.</div><div class="ops-status">@forelse($statusBreakdown as $row)<div class="ops-status-row"><span>{{ $row->status }}</span><strong>{{ $row->total }}</strong></div>@empty<div class="muted">No scoped records available.</div>@endforelse</div></div>
</section>

<section class="ops-control-grid">
  <div class="ops-card"><div class="ops-section-head"><div><h3>Priority Work Queue</h3><div class="sub">Highest operational-attention work orders.</div></div><a href="{{ route('maintenance.work-orders.index') }}">All work orders →</a></div><div class="ops-table-wrap"><table class="ops-table"><thead><tr><th>Work Order</th><th>Type</th><th>Priority</th><th>Status</th><th>Planned Start</th></tr></thead><tbody>@forelse($priorityWorkOrders as $wo)<tr><td><a href="{{ route('maintenance.work-orders.show',$wo) }}">{{ $wo->work_order_no }}</a></td><td>{{ $wo->maintenance_type }}</td><td><span class="ops-pill {{ in_array($wo->priority,['EMERGENCY','CRITICAL','URGENT']) ? 'hot' : '' }}">{{ $wo->priority }}</span></td><td>{{ $wo->status }}</td><td>{{ optional($wo->planned_start)->format('d M Y H:i') ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="muted">No active work orders are currently visible in your scope.</td></tr>@endforelse</tbody></table></div></div>
  <div class="ops-card"><h3>Operational Boundaries</h3><div class="sub">Separation of duties remains enforced.</div><div class="ops-status"><div class="ops-status-row"><span>Operational execution visibility</span><strong>Enabled</strong></div><div class="ops-status-row"><span>Scope enforcement</span><strong>Enabled</strong></div><div class="ops-status-row"><span>System administration authority</span><strong>Restricted</strong></div><div class="ops-status-row"><span>Payroll / accounting posting</span><strong>Restricted</strong></div><div class="ops-status-row"><span>Independent approval authority</span><strong>Role based</strong></div></div></div>
</section>

<div class="ops-note">Operations Manager is the operational control owner, not the system or financial authority. Service Requests → triage → assignment → Work Order → field execution → verification → customer closure will remain traceable through scope enforcement and audit events.</div>
</div>
@endsection
