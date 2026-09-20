@extends('layouts.app')
@section('title','System Administration | UNIFCO Platform')
@section('heading','System Administration')
@section('content')
@php($integrationSummary=app(\App\Services\IntegrationHealthService::class)->summary(auth()->user()->tenant_id))
<style>
.sys-page{max-width:1540px;margin:0 auto;color:#10264f}.sys-hero{position:relative;overflow:hidden;background:radial-gradient(circle at 76% 45%,rgba(34,99,180,.55),transparent 28%),linear-gradient(135deg,#071d46 0%,#0d2a61 58%,#0b1e45 100%);color:#fff;border-radius:16px;padding:22px 24px;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 14px 32px rgba(7,30,70,.13)}.sys-hero:after{content:'';position:absolute;right:13%;top:-65%;width:360px;height:360px;border:1px solid rgba(255,255,255,.12);border-radius:50%;box-shadow:0 0 0 28px rgba(255,255,255,.03),0 0 0 56px rgba(255,255,255,.02)}.sys-hero-copy{position:relative;z-index:1}.sys-eyebrow{font-size:8px;font-weight:900;letter-spacing:.16em;text-transform:uppercase;color:#8fc0ff}.sys-hero h1{font-size:24px;margin:5px 0 6px;letter-spacing:-.02em}.sys-hero p{font-size:10px;line-height:1.55;color:#dce7f7;margin:0;max-width:740px}.sys-hero-side{position:relative;z-index:1;display:flex;align-items:center;gap:20px}.hero-motto{display:grid;gap:3px;font-size:9px;color:#e6eefb;text-transform:uppercase;letter-spacing:.05em}.hero-mark{width:64px;height:64px;border-radius:16px;border:1px solid rgba(255,255,255,.16);display:grid;place-items:center;font-size:12px;font-weight:900;background:rgba(255,255,255,.06)}.system-state{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;background:#ffffff14;border:1px solid #ffffff1f;font-size:8px;font-weight:900;white-space:nowrap}.system-state:before{content:'';width:7px;height:7px;border-radius:50%;background:#41d18b;box-shadow:0 0 0 4px rgba(65,209,139,.12)}.system-state.WARNING:before{background:#f3b64c}.system-state.DEGRADED:before{background:#f0833f}.system-state.CRITICAL:before{background:#f04b5f}
.sys-health{margin-top:10px;background:#fff;border:1px solid #e2e9f2;border-radius:12px;padding:9px 11px;display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:10px;box-shadow:0 4px 14px rgba(10,35,79,.04)}.health-label{display:flex;align-items:center;gap:8px;font-size:9px;font-weight:900;color:#173660}.health-icon{width:28px;height:28px;border-radius:8px;background:#eef5ff;display:grid;place-items:center;color:#1269d3}.health-list{display:flex;gap:7px;min-width:0;overflow:auto}.health-chip{display:flex;align-items:center;gap:7px;padding:7px 9px;background:#f7f9fc;border:1px solid #edf1f5;border-radius:9px;font-size:7.5px;color:#425873;white-space:nowrap}.health-chip:before{content:'';width:7px;height:7px;border-radius:50%;background:#9aa8b8}.health-chip.ok:before{background:#17a365}.health-chip.warn:before{background:#e19a16}.health-chip.bad:before{background:#d63c4b}.health-summary{font-size:7.5px;color:#148257;font-weight:850;white-space:nowrap}
.sys-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:9px;margin:10px 0}.sys-kpi{background:#fff;border:1px solid #e3e9f1;border-radius:12px;padding:13px;display:flex;gap:10px;align-items:flex-start;box-shadow:0 4px 14px rgba(10,35,79,.035);position:relative;overflow:hidden}.sys-kpi.alert{border-left:3px solid #e01935}.sys-kpi-icon{width:34px;height:34px;border-radius:10px;background:#edf5ff;color:#176fd4;display:grid;place-items:center;font-size:16px;flex:0 0 auto}.sys-kpi.alert .sys-kpi-icon{background:#fff0f2;color:#d9233c}.sys-kpi.good .sys-kpi-icon{background:#e9f8ef;color:#14915b}.sys-kpi small{font-size:7.3px;color:#697b92;font-weight:850;text-transform:uppercase;letter-spacing:.035em}.sys-kpi b{display:block;font-size:23px;line-height:1;margin:4px 0 5px;color:#10264f}.sys-kpi span{display:block;font-size:7.5px;color:#8795a8}.sys-kpi .delta{margin-top:5px;font-size:7px;font-weight:800;color:#168d59}.sys-kpi.alert .delta{color:#d9233c}
.sys-actions{display:flex;gap:6px;flex-wrap:wrap;margin:8px 0 10px}.sys-actions .btn{font-size:8px;padding:7px 10px;border-radius:8px}.sys-actions .btn.active{background:#0b2f6c}
.sys-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(320px,.75fr);gap:11px}.sys-panel{background:#fff;border:1px solid #e3e9f1;border-radius:12px;padding:13px;box-shadow:0 4px 14px rgba(10,35,79,.035)}.sys-panel-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}.sys-panel-title{display:flex;gap:8px;align-items:center}.sys-panel-title i{width:28px;height:28px;border-radius:8px;background:#eef5ff;color:#166dd0;display:grid;place-items:center;font-style:normal}.sys-panel h3{font-size:12px;margin:0}.sys-panel-head p{font-size:7.5px;color:#8795a8;margin:2px 0 0}.sys-panel-head a{font-size:7.5px;color:#0d6cd8;font-weight:800}.user-tools{display:flex;gap:7px;margin-bottom:8px}.user-tools input{flex:1;height:32px;border:1px solid #dde5ef;border-radius:8px;padding:0 10px;font-size:8px}.user-tools button{height:32px;border:1px solid #dde5ef;border-radius:8px;background:#fff;color:#24466f;font-size:8px;padding:0 10px}.sys-table{width:100%;border-collapse:collapse;font-size:8px}.sys-table th,.sys-table td{padding:8px 7px;border-bottom:1px solid #edf1f5;text-align:left;vertical-align:middle}.sys-table th{font-size:7px;color:#718096;text-transform:uppercase;letter-spacing:.04em}.sys-table tr:last-child td{border-bottom:0}.user-cell{display:flex;align-items:center;gap:8px}.user-avatar{width:26px;height:26px;border-radius:50%;background:#e9f3ff;color:#176bc9;display:grid;place-items:center;font-size:7px;font-weight:900}.user-meta b{font-size:8.5px;display:block}.user-meta small{font-size:6.8px;color:#8592a6}.role-chip,.scope-chip,.active-chip{display:inline-flex;padding:4px 6px;border-radius:999px;background:#eef4fb;color:#345b8c;font-size:6.8px;font-weight:800}.active-chip{background:#eaf8ef;color:#147a4c}.more-btn{border:0;background:#f5f7fa;width:26px;height:26px;border-radius:7px}
.alert-list{display:grid}.alert-row{display:grid;grid-template-columns:auto 1fr auto;gap:9px;align-items:center;padding:9px 0;border-bottom:1px solid #edf1f5}.alert-row:last-child{border:0}.alert-icon{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;background:#eef5ff;color:#176fd4;font-weight:900}.alert-icon.red{background:#fff0f2;color:#d9233c}.alert-icon.amber{background:#fff6e6;color:#d58a08}.alert-copy b{display:block;font-size:8.5px}.alert-copy small{display:block;font-size:7px;color:#8492a5;margin-top:2px;line-height:1.35}.severity{display:inline-flex;padding:4px 7px;border-radius:999px;background:#eef3f8;color:#526277;font-size:6.8px;font-weight:850}.severity.HIGH,.severity.CRITICAL{background:#fdecec;color:#a52424}.severity.WARNING,.severity.MEDIUM{background:#fff5e5;color:#996000}.severity.LOW,.severity.INFO{background:#eaf7f0;color:#16744b}
.audit-panel{margin-top:11px}.audit-table{width:100%;border-collapse:collapse;font-size:8px}.audit-table th,.audit-table td{padding:8px 7px;border-bottom:1px solid #edf1f5;text-align:left}.audit-table th{font-size:7px;text-transform:uppercase;color:#748297}.audit-table tr:last-child td{border-bottom:0}.sys-empty{padding:20px;text-align:center;color:#8492a5;font-size:8px;background:#fafbfd;border-radius:9px}
@media(max-width:1150px){.sys-kpis{grid-template-columns:repeat(3,1fr)}.sys-grid{grid-template-columns:1fr}}
@media(max-width:760px){.sys-page{padding-bottom:10px}.sys-hero{padding:15px;align-items:flex-start}.sys-hero h1{font-size:21px}.sys-hero p{font-size:9px}.sys-hero-side{display:none}.sys-health{grid-template-columns:1fr auto;padding:9px}.health-list{grid-column:1/-1}.sys-kpis{grid-template-columns:repeat(2,1fr);gap:7px}.sys-kpi{padding:10px;min-height:86px}.sys-kpi-icon{width:30px;height:30px}.sys-kpi b{font-size:20px}.sys-actions{overflow:auto;flex-wrap:nowrap;padding-bottom:3px}.sys-actions .btn{white-space:nowrap}.sys-grid{grid-template-columns:1fr}.sys-panel{padding:10px}.desktop-only{display:none}.sys-table th:nth-child(3),.sys-table td:nth-child(3){display:none}.user-tools input{min-width:0}.audit-table th:nth-child(4),.audit-table td:nth-child(4){display:none}}
</style>
<div class="sys-page">
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif

<section class="sys-hero">
  <div class="sys-hero-copy">
    <div class="sys-eyebrow">SYSTEM AUTHORITY · IT GOVERNANCE</div>
    <h1>System Administrator</h1>
    <p>Central control for identity, access, security, integrations and immutable audit. Operational, financial and contractual execution remains intentionally outside this role.</p>
  </div>
  <div class="sys-hero-side">
    <div class="hero-motto"><span>Secure</span><span>Govern</span><span>Monitor</span><span>Maintain</span></div>
    <div class="hero-mark">UNIFCO<br>PLATFORM</div>
    <span class="system-state {{ $status }}">{{ $status }}</span>
  </div>
</section>

<section class="sys-health">
  <div class="health-label"><span class="health-icon">⌁</span>System Health</div>
  <div class="health-list">
    @foreach($integrationSummary['rows'] as $row)
      <span class="health-chip {{ in_array($row->status,['OPERATIONAL','READY'])?'ok':(in_array($row->status,['PENDING','DEGRADED'])?'warn':'bad') }}">{{ $row->name }}<b>{{ $row->status }}</b></span>
    @endforeach
    @if($integrationSummary['rows']->isEmpty())<span class="health-chip warn">Integration health not initialized</span>@endif
  </div>
  <span class="health-summary">{{ $status === 'OPERATIONAL' ? 'All Systems Operational' : $status }}</span>
</section>

<section class="sys-kpis">
  <div class="sys-kpi good"><span class="sys-kpi-icon">👥</span><div><small>Active Users</small><b>{{ $kpis['active_users'] }}</b><span>Internal platform users</span><div class="delta">Live access</div></div></div>
  <div class="sys-kpi"><span class="sys-kpi-icon">👤</span><div><small>Portal Users</small><b>{{ $kpis['customer_users'] }}</b><span>Customer access accounts</span><div class="delta">Customer portal</div></div></div>
  <div class="sys-kpi"><span class="sys-kpi-icon">▣</span><div><small>Active Sessions</small><b>{{ $kpis['active_sessions'] }}</b><span>Live authenticated sessions</span><div class="delta">Current sessions</div></div></div>
  <div class="sys-kpi {{ $kpis['failed_logins']?'alert':'' }}"><span class="sys-kpi-icon">◆</span><div><small>Failed Logins · 24h</small><b>{{ $kpis['failed_logins'] }}</b><span>Security monitoring</span><div class="delta">Last 24 hours</div></div></div>
  <div class="sys-kpi {{ $kpis['system_alerts']?'alert':'' }}"><span class="sys-kpi-icon">●</span><div><small>System Alerts</small><b>{{ $kpis['system_alerts'] }}</b><span>Items needing attention</span><div class="delta">Active alerts</div></div></div>
  <div class="sys-kpi {{ $kpis['integrations']===$kpis['integration_total']?'good':'' }}"><span class="sys-kpi-icon">⌘</span><div><small>Integration Health</small><b>{{ $kpis['integrations'] }}/{{ $kpis['integration_total'] }}</b><span>Healthy registered services</span><div class="delta">Integration status</div></div></div>
</section>

<nav class="sys-actions">
  <a class="btn active" href="{{ route('workspace.show','users') }}">Users & Access</a>
  <a class="btn secondary" href="{{ route('admin.permissions.index') }}">Roles & Permissions</a>
  <a class="btn secondary" href="{{ route('admin.system.login-activity') }}">Login Activity</a>
  <a class="btn secondary" href="{{ route('admin.system.sessions') }}">Active Sessions</a>
  <a class="btn secondary" href="{{ route('admin.system.security-events') }}">Security Events</a>
  <a class="btn secondary" href="{{ route('admin.system.integrations') }}">Integrations & Health</a>
  <a class="btn secondary" href="{{ route('admin.system.scope-audit') }}">Scope Audit</a>
  <a class="btn secondary" href="{{ route('admin.audit.index') }}">Audit Trail</a>
</nav>

<div class="sys-grid">
  <section class="sys-panel">
    <div class="sys-panel-head"><div class="sys-panel-title"><i>👥</i><div><h3>Users & Access</h3><p>Manage system users, roles and access scope.</p></div></div><a href="{{ route('workspace.show','users') }}">View all users →</a></div>
    <div class="user-tools"><input type="search" placeholder="Search users by name, email or role..."><button type="button">☷ Filter</button></div>
    <table class="sys-table">
      <thead><tr><th>User</th><th>Role</th><th>Scope</th><th>Status</th><th>Last Active</th><th></th></tr></thead>
      <tbody>
      @forelse($recentUsers as $user)
        <tr>
          <td><div class="user-cell"><span class="user-avatar">{{ strtoupper(substr($user->name,0,2)) }}</span><span class="user-meta"><b>{{ $user->name }}</b><small>{{ $user->email }}</small></span></div></td>
          <td><span class="role-chip">{{ $user->activeRoles->pluck('code')->join(' + ') ?: $user->role }}</span></td>
          <td><span class="scope-chip">{{ $user->accessScopes->pluck('name')->filter()->join(', ') ?: ($user->customer_id?'Customer scope':'Global') }}</span></td>
          <td><span class="active-chip">{{ $user->status }}</span></td>
          <td>{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
          <td><a class="more-btn" href="{{ route('admin.users.show',$user) }}">•••</a></td>
        </tr>
      @empty<tr><td colspan="6"><div class="sys-empty">No users available.</div></td></tr>@endforelse
      </tbody>
    </table>
  </section>

  <section class="sys-panel">
    <div class="sys-panel-head"><div class="sys-panel-title"><i>●</i><div><h3>System Alerts</h3><p>Security, integration and system health alerts.</p></div></div><a href="{{ route('admin.system.security-events') }}">View all alerts →</a></div>
    <div class="alert-list">
      @if($failedJobs)<div class="alert-row"><span class="alert-icon red">!</span><span class="alert-copy"><b>Background Job Failure</b><small>{{ $failedJobs }} failed in the last 24 hours</small></span><span class="severity HIGH">CRITICAL</span></div>@endif
      @forelse($events as $event)
        <div class="alert-row"><span class="alert-icon {{ in_array($event->severity,['HIGH','CRITICAL'])?'red':(in_array($event->severity,['WARNING','MEDIUM'])?'amber':'') }}">{{ in_array($event->severity,['HIGH','CRITICAL'])?'!':'i' }}</span><span class="alert-copy"><b>{{ ucwords(str_replace('_',' ',$event->event_type)) }}</b><small>{{ $event->occurred_at }}</small></span><span class="severity {{ $event->severity }}">{{ $event->severity }}</span></div>
      @empty @if(!$failedJobs)<div class="sys-empty">No active security alerts.</div>@endif @endforelse
    </div>
  </section>
</div>

<section class="sys-panel audit-panel">
  <div class="sys-panel-head"><div class="sys-panel-title"><i>◷</i><div><h3>Recent System Activity</h3><p>Immutable audit trail of recent system activity.</p></div></div><a href="{{ route('admin.audit.index') }}">View full audit trail →</a></div>
  <table class="audit-table">
    <thead><tr><th>Time</th><th>Action</th><th>Target</th><th>Details</th></tr></thead>
    <tbody>
      @forelse($activity as $event)
        <tr><td>{{ $event->created_at }}</td><td><b>{{ $event->action }}</b></td><td>{{ $event->entity_type ? class_basename($event->entity_type).' #'.$event->entity_id : 'SYSTEM' }}</td><td>{{ $event->ip_address ?? 'IP not recorded' }}</td></tr>
      @empty<tr><td colspan="4"><div class="sys-empty">No audit activity recorded.</div></td></tr>@endforelse
    </tbody>
  </table>
</section>
</div>
@endsection
