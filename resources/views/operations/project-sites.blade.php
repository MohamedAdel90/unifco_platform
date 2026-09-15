@extends('layouts.app')
@section('title','Project & Site Operations')
@section('content')
<style>
.ops-shell{display:grid;gap:16px}.hero{background:linear-gradient(135deg,#0b203d,#174b83);color:#fff;border-radius:18px;padding:22px}.hero h1{margin:2px 0 6px;font-size:23px}.hero p{margin:0;color:#d6e7f7;font-size:12px}.kpis{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}.kpi,.card{background:#fff;border:1px solid #e4ebf3;border-radius:13px;padding:14px}.kpi small{display:block;color:#718198;font-size:9px;text-transform:uppercase}.kpi strong{display:block;font-size:22px;color:#14375e;margin-top:5px}.grid{display:grid;grid-template-columns:1.25fr .75fr;gap:14px}.card h3{margin:0 0 4px;color:#102f57;font-size:14px}.sub{color:#7d8998;font-size:10px;margin-bottom:12px}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse;min-width:700px}.table th,.table td{padding:9px 8px;border-bottom:1px solid #edf1f5;text-align:left;font-size:11px}.table th{font-size:9px;color:#77869a;text-transform:uppercase}.pill{display:inline-flex;padding:4px 8px;border-radius:999px;background:#eef4fb;color:#204d7c;font-size:9px;font-weight:800}.pill.warn{background:#fff7e7;color:#8b6100}.site-list{display:grid;gap:8px}.site{border:1px solid #e8edf3;border-radius:10px;padding:10px}.site b{color:#173d66;font-size:11px}.muted{color:#7b899a;font-size:10px}.back{color:#fff;text-decoration:none;font-weight:700;font-size:11px;float:right}.attention{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}.attention a,.attention div{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px;border:1px solid #e5eaf0;border-radius:11px;background:#fbfcfe;color:#35435a;text-decoration:none;font-size:10px;font-weight:800}.attention strong{font-size:20px;color:#173d66}.attention .danger{background:#fff7f8;border-color:#f3d9de}.attention .danger strong{color:#b11d34}.attention .warning{background:#fffbf1;border-color:#f2e4bc}.attention .warning strong{color:#9a6a00}@media(max-width:1000px){.kpis{grid-template-columns:repeat(3,1fr)}.grid{grid-template-columns:1fr}.attention{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.kpis{grid-template-columns:repeat(2,1fr)}.attention{grid-template-columns:1fr 1fr}}
</style>
<div class="ops-shell">
 <section class="hero">
   <a class="back" href="{{ route('operations-manager.dashboard') }}">← Command Center</a>
   <div style="font-size:10px;font-weight:800;color:#a9c8e6">OPERATIONS MANAGER · DELIVERY HIERARCHY</div>
   <h1>Projects, Sites & Field Capacity</h1>
   <p>Scope-aware operating picture across customers, projects, active sites, open work and technician workload.</p>
 </section>
 <section class="kpis">
   <div class="kpi"><small>Projects</small><strong>{{ $metrics['projects'] }}</strong></div>
   <div class="kpi"><small>Customers</small><strong>{{ $metrics['customers'] }}</strong></div>
   <div class="kpi"><small>Active Sites</small><strong>{{ $metrics['sites'] }}</strong></div>
   <div class="kpi"><small>Open Work Orders</small><strong>{{ $metrics['open_work_orders'] }}</strong></div>
   <div class="kpi"><small>Field Assignments</small><strong>{{ $metrics['field_assignments'] }}</strong></div>
   <div class="kpi"><small>Active Technicians</small><strong>{{ $metrics['active_technicians'] }}</strong></div>
 </section>
 <section class="card">
   <h3>Field Capacity Attention</h3><div class="sub">Immediate execution risks across the current operational scope.</div>
   <div class="attention">
     <a class="danger" href="{{ route('maintenance.work-orders.index') }}"><span>Emergency / Critical Work</span><strong>{{ $metrics['emergency_work_orders'] }}</strong></a>
     <a class="warning" href="{{ route('maintenance.work-orders.index') }}"><span>Overdue Work Orders</span><strong>{{ $metrics['overdue_work_orders'] }}</strong></a>
     <a class="warning" href="#technician-capacity"><span>Overloaded Technicians</span><strong>{{ $metrics['overloaded_technicians'] }}</strong></a>
     <div><span>Total Capacity Attention</span><strong>{{ $metrics['attention_total'] }}</strong></div>
   </div>
 </section>
 <section class="grid">
   <div class="card table-wrap">
     <h3>Project Portfolio</h3><div class="sub">Only projects visible in the current operational scope.</div>
     <table class="table"><thead><tr><th>Project</th><th>Customer</th><th>Status</th><th>Start</th><th>Finish</th></tr></thead><tbody>
       @forelse($projects as $project)
       <tr><td><strong>{{ $project->project_no }}</strong><div class="muted">{{ $project->name }}</div></td><td>{{ $customers->firstWhere('id',$project->customer_id)?->name ?: '—' }}</td><td><span class="pill">{{ $project->status }}</span></td><td>{{ optional($project->planned_start)->format('Y-m-d') ?: '—' }}</td><td>{{ optional($project->planned_finish)->format('Y-m-d') ?: '—' }}</td></tr>
       @empty<tr><td colspan="5" class="muted">No projects visible in the current scope.</td></tr>@endforelse
     </tbody></table>
   </div>
   <div class="card">
     <h3>Active Sites</h3><div class="sub">Sites inherited from scoped customers.</div>
     <div class="site-list">@forelse($sites as $site)<div class="site"><b>{{ $site->name }}</b><div class="muted">{{ $site->site_code }} · {{ $site->city ?: '—' }}</div><div class="muted">{{ $customers->firstWhere('id',$site->customer_id)?->name ?: '—' }}</div></div>@empty<div class="muted">No active sites visible.</div>@endforelse</div>
   </div>
 </section>
 <section class="card table-wrap" id="technician-capacity">
   <h3>Technician Utilization Snapshot</h3><div class="sub">Current active field assignments inside the manager's Work Order scope. Four or more active assignments are flagged for balancing.</div>
   <table class="table"><thead><tr><th>Technician</th><th>Capacity</th><th>Active Assignments</th><th>In Progress</th><th>Arrived</th></tr></thead><tbody>
   @forelse($utilization as $row)<tr><td><strong>{{ $row['employee'] }}</strong></td><td><span class="pill {{ $row['active_assignments']>=4?'warn':'' }}">{{ $row['active_assignments']>=4?'REBALANCE':'AVAILABLE' }}</span></td><td>{{ $row['active_assignments'] }}</td><td>{{ $row['in_progress'] }}</td><td>{{ $row['arrived'] }}</td></tr>@empty<tr><td colspan="5" class="muted">No active field assignments in the current scope.</td></tr>@endforelse
   </tbody></table>
 </section>
</div>
@endsection
