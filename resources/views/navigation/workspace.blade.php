@extends('layouts.app')

@section('title', $workspace['title'].' | UNIFCO Platform')
@section('heading', $workspace['title'])

@section('content')
<style>
.workspace-hero{background:linear-gradient(135deg,#132137,#1e315b);color:#fff;border-radius:16px;padding:22px 24px;margin-bottom:16px;display:flex;justify-content:space-between;gap:18px;align-items:flex-start}.workspace-hero small{display:block;color:#aebbd0;text-transform:uppercase;letter-spacing:.12em;font-size:9px;font-weight:800;margin-bottom:7px}.workspace-hero h1{font-size:24px;margin:0 0 7px}.workspace-hero p{margin:0;color:#dce5f2;max-width:760px;line-height:1.55}.workspace-grid{display:grid;grid-template-columns:1.35fr .65fr;gap:14px}.workspace-card{background:#fff;border:1px solid #e3e8ef;border-radius:13px;padding:18px}.workspace-card h3{margin:0 0 8px;color:#17243c;font-size:14px}.workspace-card p{margin:0;color:#68758a;font-size:12px;line-height:1.55}.workspace-status{display:grid;gap:9px;margin-top:12px}.workspace-status div{display:flex;justify-content:space-between;align-items:center;padding:9px 10px;background:#f7f9fc;border-radius:9px;font-size:11px}.workspace-status b{color:#1e315b}.workspace-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#22a064;margin-right:6px}.workspace-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.workspace-note{margin-top:14px;padding:11px 12px;background:#f8fafc;border-left:3px solid #1e315b;border-radius:8px;color:#5f6c80;font-size:11px;line-height:1.5}@media(max-width:850px){.workspace-grid{grid-template-columns:1fr}.workspace-hero{padding:18px;display:block}.workspace-hero .btn{margin-top:14px}.workspace-hero h1{font-size:20px}}
</style>

<section class="workspace-hero">
  <div>
    <small>{{ $workspace['group'] }}</small>
    <h1>{{ $workspace['title'] }}</h1>
    <p>{{ $workspace['description'] }}</p>
  </div>
  <a class="btn" href="{{ $workspace['primary_url'] }}">Open Operations</a>
</section>

<div class="workspace-grid">
  <section class="workspace-card">
    <h3>Workspace Overview</h3>
    <p>This dedicated workspace gives {{ $workspace['title'] }} a stable navigation destination while preserving the existing operational workflow and permission controls.</p>
    <div class="workspace-actions"><a class="btn" href="{{ $workspace['primary_url'] }}">Continue to Operational Page</a><a class="btn secondary" href="{{ route('dashboard') }}">Operations Command Center</a></div>
    <div class="workspace-note">This workspace is ready to receive dedicated KPIs, filters, tables and actions as the module grows. Existing business transactions continue through the established operational controller to avoid duplication.</div>
  </section>
  <aside class="workspace-card">
    <h3>Readiness</h3>
    <div class="workspace-status">
      <div><span><i class="workspace-dot"></i>Navigation</span><b>Active</b></div>
      <div><span><i class="workspace-dot"></i>Authentication</span><b>Protected</b></div>
      <div><span><i class="workspace-dot"></i>Operations Link</span><b>Connected</b></div>
    </div>
  </aside>
</div>
@endsection
