@extends('layouts.app')

@section('title', $workspace['title'].' | UNIFCO Platform')
@section('heading', $workspace['title'])

@section('content')
<style>
.workspace-shell{display:grid;gap:14px}.workspace-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#132137 0%,#1e315b 72%,#263f74 100%);color:#fff;border-radius:18px;padding:24px 26px;display:flex;justify-content:space-between;gap:20px;align-items:center;box-shadow:0 14px 34px rgba(19,33,55,.14)}.workspace-hero:after{content:"";position:absolute;width:220px;height:220px;border:38px solid rgba(255,255,255,.045);border-radius:50%;right:-75px;top:-115px}.workspace-hero>*{position:relative;z-index:1}.workspace-hero small{display:block;color:#b8c6da;text-transform:uppercase;letter-spacing:.14em;font-size:10px;font-weight:800;margin-bottom:8px}.workspace-hero h1{font-size:27px;margin:0 0 8px}.workspace-hero p{margin:0;color:#dce5f2;max-width:720px;line-height:1.55;font-size:13px}.workspace-hero .btn{white-space:nowrap;background:#fff;color:#1e315b;border-color:#fff}.workspace-grid{display:grid;grid-template-columns:1.45fr .55fr;gap:14px}.workspace-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:19px;box-shadow:0 5px 18px rgba(19,33,55,.035)}.workspace-card h3{margin:0 0 7px;color:#17243c;font-size:15px}.workspace-card p{margin:0;color:#68758a;font-size:12px;line-height:1.6}.workspace-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:16px}.workspace-actions .btn{min-height:40px;display:inline-flex;align-items:center;justify-content:center}.workspace-actions .primary{background:#1e315b;color:#fff}.workspace-context{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:15px}.workspace-context div{background:#f7f9fc;border:1px solid #edf1f5;border-radius:10px;padding:10px}.workspace-context small{display:block;color:#7a8799;font-size:9px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}.workspace-context strong{color:#1e315b;font-size:11px}.workspace-status{display:grid;gap:9px;margin-top:12px}.workspace-status div{display:flex;justify-content:space-between;align-items:center;padding:10px 11px;background:#f7f9fc;border-radius:9px;font-size:11px}.workspace-status b{color:#1e315b}.workspace-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#22a064;margin-inline-end:7px}.workspace-foot{padding:11px 13px;background:#f8fafc;border-inline-start:3px solid #1e315b;border-radius:8px;color:#5f6c80;font-size:11px;line-height:1.5}@media(max-width:850px){.workspace-grid{grid-template-columns:1fr}.workspace-hero{padding:20px;align-items:flex-start;flex-direction:column}.workspace-hero h1{font-size:23px}.workspace-hero .btn{width:100%;text-align:center}.workspace-context{grid-template-columns:1fr}.workspace-actions{display:grid;grid-template-columns:1fr}.workspace-actions .btn{width:100%}}
</style>

<div class="workspace-shell">
<section class="workspace-hero">
  <div>
    <small>{{ $workspace['group'] }}</small>
    <h1>{{ $workspace['title'] }}</h1>
    <p>{{ $workspace['description'] }}</p>
  </div>
  <a class="btn" href="{{ $workspace['primary_url'] }}">Open {{ $workspace['title'] }}</a>
</section>

<div class="workspace-grid">
  <section class="workspace-card">
    <h3>Operational Access</h3>
    <p>Use this entry point to continue into the connected UNIFCO operational module. Your existing role, tenant and assigned-scope controls remain enforced throughout the workflow.</p>
    <div class="workspace-actions">
      <a class="btn primary" href="{{ $workspace['primary_url'] }}">Continue to {{ $workspace['title'] }}</a>
      @if(auth()->user()?->role === 'OPERATIONS_MANAGER')
        <a class="btn secondary" href="{{ route('operations-manager.dashboard') }}">Back to Operations Command Center</a>
      @else
        <a class="btn secondary" href="{{ route('dashboard') }}">Back to Dashboard</a>
      @endif
    </div>
    <div class="workspace-context">
      <div><small>Workspace</small><strong>{{ $workspace['title'] }}</strong></div>
      <div><small>Area</small><strong>{{ $workspace['group'] }}</strong></div>
      <div><small>Access</small><strong>Role & Scope Controlled</strong></div>
    </div>
  </section>
  <aside class="workspace-card">
    <h3>Access Status</h3>
    <p>Connection and security status for this workspace.</p>
    <div class="workspace-status">
      <div><span><i class="workspace-dot"></i>Navigation</span><b>Ready</b></div>
      <div><span><i class="workspace-dot"></i>Authentication</span><b>Protected</b></div>
      <div><span><i class="workspace-dot"></i>Operational Module</span><b>Connected</b></div>
    </div>
  </aside>
</div>
<div class="workspace-foot">UNIFCO operational workspaces provide a consistent navigation layer while business transactions remain handled by their dedicated modules and permission controls.</div>
</div>
@endsection
