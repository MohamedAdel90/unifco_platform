@extends('layouts.app')
@section('title','Operations Reports')
@section('content')
<style>
.ops-shell{display:grid;gap:16px}.ops-hero{background:linear-gradient(135deg,#071a33,#123c73);color:#fff;border-radius:16px;padding:20px 22px}.ops-hero h1{margin:0 0 5px;font-size:22px}.ops-hero p{margin:0;color:#d6e4f6;font-size:12px}.ops-kpis{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:10px}.ops-kpi,.ops-card{background:#fff;border:1px solid #e3eaf2;border-radius:12px}.ops-kpi{padding:13px}.ops-kpi span{display:block;color:#728198;font-size:9px;text-transform:uppercase}.ops-kpi b{display:block;color:#123c73;font-size:22px;margin-top:5px}.ops-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.ops-card{padding:15px}.ops-card h3{margin:0 0 10px;color:#132137;font-size:14px}.ops-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eef2f6;font-size:11px}.ops-row:last-child{border-bottom:0}.ops-note{font-size:10px;color:#65758a;background:#f7f9fc;border-left:3px solid #ce122d;padding:10px 12px;border-radius:0 8px 8px 0}.ops-actions{display:flex;gap:8px;flex-wrap:wrap}.ops-actions a{padding:9px 12px;border:1px solid #dfe6ef;border-radius:9px;text-decoration:none;color:#123c73;background:#fff;font-size:10px;font-weight:800}@media(max-width:1100px){.ops-kpis{grid-template-columns:repeat(4,1fr)}}@media(max-width:760px){.ops-kpis{grid-template-columns:repeat(2,1fr)}.ops-grid{grid-template-columns:1fr}}
</style>
<div class="ops-shell">
  <section class="ops-hero">
    <h1>Operational Reports & Performance</h1>
    <p>Scope-aware operational visibility only. Financial posting, payroll and approval authority remain outside this workspace.</p>
  </section>

  <section class="ops-kpis">
    <div class="ops-kpi"><span>Open Work Orders</span><b>{{ $report['open_work_orders'] }}</b></div>
    <div class="ops-kpi"><span>Completed Work Orders</span><b>{{ $report['completed_work_orders'] }}</b></div>
    <div class="ops-kpi"><span>Emergency / Critical</span><b>{{ $report['emergency_work_orders'] }}</b></div>
    <div class="ops-kpi"><span>Open Service Requests</span><b>{{ $report['open_service_requests'] }}</b></div>
    <div class="ops-kpi"><span>SLA Overdue</span><b>{{ $report['sla_overdue'] }}</b></div>
    <div class="ops-kpi"><span>Active Projects</span><b>{{ $report['active_projects'] }}</b></div>
    <div class="ops-kpi"><span>Active Customers</span><b>{{ $report['active_customers'] }}</b></div>
  </section>

  <section class="ops-actions">
    <a href="{{ route('operations-manager.dashboard') }}">Command Center</a>
    <a href="{{ route('operations-manager.service-requests.index') }}">Service Requests</a>
    <a href="{{ route('field.operations') }}">Field Operations</a>
  </section>

  <section class="ops-grid">
    <div class="ops-card"><h3>Work Orders by Status</h3>@forelse($workOrderStatus as $row)<div class="ops-row"><span>{{ $row->status }}</span><strong>{{ $row->total }}</strong></div>@empty<div class="ops-row"><span>No scoped data</span><strong>0</strong></div>@endforelse</div>
    <div class="ops-card"><h3>Service Requests by Priority</h3>@forelse($requestPriority as $row)<div class="ops-row"><span>{{ $row->priority ?: 'UNSPECIFIED' }}</span><strong>{{ $row->total }}</strong></div>@empty<div class="ops-row"><span>No scoped data</span><strong>0</strong></div>@endforelse</div>
    <div class="ops-card"><h3>Projects by Status</h3>@forelse($projectStatus as $row)<div class="ops-row"><span>{{ $row->status }}</span><strong>{{ $row->total }}</strong></div>@empty<div class="ops-row"><span>No scoped data</span><strong>0</strong></div>@endforelse</div>
  </section>

  <div class="ops-note">All counts are calculated after tenant and assigned-scope enforcement. A structured Operations Manager with no applicable scope receives zero operational rows.</div>
</div>
@endsection
