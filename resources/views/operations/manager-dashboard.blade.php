@extends('layouts.app')

@section('title','Operations Manager')
@section('heading','Operations Manager')

@section('content')
<style>
.ops-shell{display:grid;gap:16px}.ops-hero{background:linear-gradient(135deg,#132137,#1e315b);color:#fff;border-radius:16px;padding:20px 22px;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 10px 28px rgba(19,33,55,.14)}.ops-hero h1{margin:0 0 5px;font-size:22px}.ops-hero p{margin:0;color:#cbd6e6;font-size:12px}.ops-badge{padding:7px 10px;border:1px solid #ffffff24;border-radius:999px;background:#ffffff0d;font-size:10px;font-weight:800;white-space:nowrap}.ops-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.ops-kpi{background:#fff;border:1px solid #e4e9f0;border-radius:12px;padding:14px}.ops-kpi small{display:block;color:#738198;font-size:9px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px}.ops-kpi strong{font-size:23px;color:#1e315b}.ops-kpi.alert{border-left:3px solid #ce122d}.ops-kpi.alert strong{color:#b11d34}.ops-grid{display:grid;grid-template-columns:1.55fr .85fr;gap:14px}.ops-card{background:#fff;border:1px solid #e3e8ef;border-radius:13px;padding:16px}.ops-card h3{margin:0 0 4px;color:#132137;font-size:14px}.ops-card .sub{color:#7a8799;font-size:10px;margin-bottom:12px}.ops-table{width:100%;border-collapse:collapse;display:table;margin:0}.ops-table th,.ops-table td{padding:9px 7px;border-bottom:1px solid #edf1f5;font-size:11px}.ops-table th{font-size:9px;color:#7a8799;text-transform:uppercase;letter-spacing:.05em}.ops-pill{display:inline-flex;padding:4px 7px;border-radius:999px;background:#eef3fa;color:#1e315b;font-size:9px;font-weight:800}.ops-pill.hot{background:#fdecef;color:#b11d34}.ops-status{display:grid;gap:8px}.ops-status-row{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #eef1f5;padding:8px 0;font-size:11px}.ops-status-row:last-child{border-bottom:0}.ops-actions{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px}.ops-actions a{padding:11px;border:1px solid #e1e7ef;border-radius:10px;text-decoration:none;color:#1e315b;background:#fff;font-size:10px;font-weight:800;text-align:center}.ops-actions a:hover{background:#f6f8fb}.ops-actions a.primary{background:#1e315b;color:#fff;border-color:#1e315b}.ops-note{border-left:3px solid #ce122d;padding:10px 12px;background:#fff7f8;border-radius:0 8px 8px 0;color:#6f2936;font-size:10px}@media(max-width:1100px){.ops-kpis{grid-template-columns:repeat(4,1fr)}.ops-actions{grid-template-columns:repeat(3,1fr)}}@media(max-width:760px){.ops-hero{align-items:flex-start;flex-direction:column}.ops-kpis{grid-template-columns:repeat(2,1fr)}.ops-grid{grid-template-columns:1fr}.ops-actions{grid-template-columns:repeat(2,1fr)}.ops-table{display:block;overflow-x:auto}}
</style>

<div class="ops-shell">
  <section class="ops-hero">
    <div>
      <h1>Operations Command Center</h1>
      <p>Live operational visibility across service requests, projects, sites and work orders inside your assigned scope.</p>
    </div>
    <div class="ops-badge">Scope Enforced · Business Operations</div>
  </section>

  <section class="ops-kpis">
    <div class="ops-kpi"><small>Open Service Requests</small><strong>{{ $metrics['open_service_requests'] }}</strong></div>
    <div class="ops-kpi {{ $metrics['sla_breaches'] ? 'alert' : '' }}"><small>SLA Breaches</small><strong>{{ $metrics['sla_breaches'] }}</strong></div>
    <div class="ops-kpi"><small>Open Work Orders</small><strong>{{ $metrics['open_work_orders'] }}</strong></div>
    <div class="ops-kpi {{ $metrics['emergency_work_orders'] ? 'alert' : '' }}"><small>Emergency / Critical</small><strong>{{ $metrics['emergency_work_orders'] }}</strong></div>
    <div class="ops-kpi"><small>Overdue Work Orders</small><strong>{{ $metrics['overdue_work_orders'] }}</strong></div>
    <div class="ops-kpi"><small>Active Projects</small><strong>{{ $metrics['active_projects'] }}</strong></div>
    <div class="ops-kpi"><small>Active Assets</small><strong>{{ $metrics['active_assets'] }}</strong></div>
    <div class="ops-kpi"><small>Customers in Scope</small><strong>{{ $metrics['customers_in_scope'] }}</strong></div>
  </section>

  <section class="ops-actions">
    <a class="primary" href="{{ route('operations-manager.service-requests.index') }}">Service Requests & SLA</a>
    <a href="{{ route('maintenance.work-orders.index') }}">Work Orders</a>
    <a href="{{ route('field.operations') }}">Field Operations</a>
    <a href="{{ route('operations-manager.project-sites') }}">Projects, Sites & Capacity</a>
    <a href="{{ route('crm.customers.index') }}">Customers</a>
  </section>

  <section class="ops-grid">
    <div class="ops-card">
      <h3>Priority Work Queue</h3>
      <div class="sub">Highest operational attention items visible in your scope.</div>
      <table class="ops-table">
        <thead><tr><th>Work Order</th><th>Type</th><th>Priority</th><th>Status</th><th>Planned Start</th></tr></thead>
        <tbody>
        @forelse($priorityWorkOrders as $wo)
          <tr>
            <td><a href="{{ route('maintenance.work-orders.show',$wo) }}">{{ $wo->work_order_no }}</a></td>
            <td>{{ $wo->maintenance_type }}</td>
            <td><span class="ops-pill {{ in_array($wo->priority,['EMERGENCY','CRITICAL','URGENT']) ? 'hot' : '' }}">{{ $wo->priority }}</span></td>
            <td>{{ $wo->status }}</td>
            <td>{{ optional($wo->planned_start)->format('d M Y H:i') ?: '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="muted">No active work orders are currently visible in your scope.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="ops-card">
      <h3>Work Order Status</h3>
      <div class="sub">Operational distribution only. Financial and system KPIs are intentionally excluded.</div>
      <div class="ops-status">
        @forelse($statusBreakdown as $row)
          <div class="ops-status-row"><span>{{ $row->status }}</span><strong>{{ $row->total }}</strong></div>
        @empty
          <div class="muted">No scoped records available.</div>
        @endforelse
      </div>
    </div>
  </section>

  <div class="ops-note">Operations Manager controls operational delivery within assigned scopes. System administration, payroll, accounting posting, procurement approval and contractual approval remain separated unless an additional authorized role or approval authority is explicitly assigned.</div>
</div>
@endsection
