@extends('layouts.app')
@section('title','Website CMS | UNIFCO Platform')
@section('heading','Website CMS')
@section('content')
@php
use App\Services\HomepageSectionSchema;

$labels = [
    'hero' => ['Hero', '⌂'],
    'capabilities' => ['Capabilities', '◈'],
    'about' => ['About', '◎'],
    'services' => ['Services', '⚒'],
    'process' => ['Process', '⇶'],
    'industries' => ['Industries', '▦'],
    'why' => ['Why Us', '✦'],
    'showcase' => ['Showcase', '▣'],
    'clients' => ['Clients', '♜'],
    'emergency' => ['Emergency CTA', '⚠'],
    'footer_cta' => ['Footer CTA', '↧'],
    'footer' => ['Footer', '☰'],
];
$homepageSections = $sections->where('section_key', '!=', 'operations')->values();
$totalActive = $homepageSections->where('is_active', true)->count();
$operations = $sections->firstWhere('section_key', 'operations');
@endphp
<style>
.hp-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}.hp-intro{max-width:700px}.hp-intro p{font-size:12.5px;color:#728096;margin:4px 0 0;line-height:1.6}
.cms-workspaces{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:18px 0}.workspace{background:#fff;border:1px solid #dce4ee;border-radius:14px;padding:17px;box-shadow:0 4px 14px rgba(23,36,60,.05);display:flex;gap:14px;align-items:flex-start}.workspace .ic{width:46px;height:46px;border-radius:12px;background:#eef3fa;color:#1e315b;display:grid;place-items:center;font-size:21px;flex:0 0 auto}.workspace h3{margin:0;color:#17243c;font-size:15px}.workspace p{margin:5px 0 13px;color:#728096;font-size:11.5px;line-height:1.6}.workspace .tag{display:inline-block;margin-left:6px;padding:2px 8px;border-radius:20px;background:#edf7f1;color:#177a3d;font-size:10px;font-weight:800}
.sep-title{margin:24px 0 8px;color:#17243c;font-size:16px}.sep-sub{margin:0;color:#728096;font-size:11.5px}
.hp-stats{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap}.hp-stat{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:12px 16px;min-width:150px}.hp-stat b{display:block;font-size:22px;color:#17243c;line-height:1}.hp-stat span{font-size:11px;color:#728096;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.hp-grid{display:grid;gap:10px;margin-top:14px;grid-template-columns:repeat(auto-fill,minmax(330px,1fr))}.hp-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:16px 18px;display:flex;gap:14px;align-items:flex-start;box-shadow:0 1px 2px rgba(23,36,60,.04)}.hp-card.disabled{opacity:.6}.hp-ic{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:19px;background:#eef3fa;color:var(--navy);flex:0 0 auto}.hp-card-body{flex:1;min-width:0}.hp-top{display:flex;align-items:center;justify-content:space-between;gap:10px}.hp-title{font-size:14px;font-weight:750;color:#17243c;display:flex;align-items:center;gap:8px;flex-wrap:wrap}.hp-key{font-size:10.5px;color:#728096;font-weight:600;background:#f2f6fa;border:1px solid #e3e8ef;padding:2px 8px;border-radius:20px}.hp-meta{font-size:11.5px;color:#728096;margin-top:4px}.hp-meta b{color:#41506b}.hp-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px}.hp-actions{display:flex;gap:8px}.hp-actions .btn{font-size:11.5px;padding:7px 15px;border-radius:9px}.btn-ghost{background:#fff;border:1px solid #d7dfe9;color:#41506b}.badge{font-size:10px;padding:4px 11px;border-radius:20px;font-weight:700;text-transform:uppercase;display:inline-flex;align-items:center;gap:5px}.badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}.badge.on{background:#e6f7ee;color:#177a3d}.badge.off{background:#fae9e9;color:#a42323}
@media(max-width:760px){.cms-workspaces{grid-template-columns:1fr}}
</style>

<div class="hp-hero">
  <div class="hp-intro">
    <h2 style="margin:0;font-size:20px;color:#17243c">Website Content Management</h2>
    <p>Homepage, Maintenance and Client Portal are now separate CMS workspaces. Edit text, images and section content without mixing one area with another.</p>
  </div>
  <a class="btn secondary" target="_blank" rel="noopener" href="{{ url('/') }}">View public site</a>
</div>

@if(session('status'))<div class="notice" style="margin-top:16px">{{ session('status') }}</div>@endif

<h3 class="sep-title">Dedicated CMS Workspaces</h3>
<p class="sep-sub">Maintenance and Client Portal are managed independently from Homepage sections.</p>
<div class="cms-workspaces">
  <div class="workspace">
    <div class="ic">⚒</div>
    <div>
      <h3>Maintenance CMS <span class="tag">SEPARATE</span></h3>
      <p>Edit Maintenance image, Arabic/English titles, descriptions, button text and feature list.</p>
      <a class="btn" href="{{ route('admin.maintenance-cms') }}">Open Maintenance CMS</a>
    </div>
  </div>
  <div class="workspace">
    <div class="ic">▣</div>
    <div>
      <h3>Client Portal CMS <span class="tag">SEPARATE</span></h3>
      <p>Edit Client Portal image, Arabic/English titles, descriptions, button text and feature list.</p>
      <a class="btn" href="{{ route('admin.client-portal-cms') }}">Open Client Portal CMS</a>
    </div>
  </div>
</div>

<h3 class="sep-title">Homepage CMS</h3>
<p class="sep-sub">Only homepage sections are listed below. The old combined Operations editor is intentionally hidden.</p>
<div class="hp-stats">
  <div class="hp-stat"><b>{{ $homepageSections->count() }}</b><span>Homepage sections</span></div>
  <div class="hp-stat"><b>{{ $totalActive }}</b><span>Active</span></div>
  <div class="hp-stat"><b>2</b><span>Separate CMS areas</span></div>
</div>

<div class="hp-grid">
@foreach($homepageSections as $s)
@php
  $lbl = $labels[$s->section_key] ?? [$s->section_key, '▤'];
  $schema = HomepageSectionSchema::fields($s->section_key);
  $fieldCount = count($schema['scalars'] ?? []) + count($schema['checks'] ?? []) + count($schema['items'] ?? []);
  $updated = $s->updated_at ? $s->updated_at->diffForHumans() : '—';
@endphp
  <div class="hp-card {{ $s->is_active ? '' : 'disabled' }}">
    <div class="hp-ic">{{ $lbl[1] }}</div>
    <div class="hp-card-body">
      <div class="hp-top"><span class="hp-title">{{ $lbl[0] }} <span class="hp-key">{{ $s->section_key }}</span></span><span class="badge {{ $s->is_active ? 'on' : 'off' }}">{{ $s->is_active ? 'Active' : 'Off' }}</span></div>
      <div class="hp-meta"><b>{{ $fieldCount }}</b> field{{ $fieldCount === 1 ? '' : 's' }} per language · order <b>{{ $s->sort_order }}</b></div>
      <div class="hp-foot"><span class="hp-meta" style="margin:0">Updated {{ $updated }}</span><div class="hp-actions"><a class="btn" href="{{ route('admin.homepage.sections.edit', $s) }}">Edit</a><form method="POST" action="{{ route('admin.homepage.sections.toggle', $s) }}" style="display:inline">@csrf<button class="btn btn-ghost" type="submit">{{ $s->is_active ? 'Disable' : 'Enable' }}</button></form></div></div>
    </div>
  </div>
@endforeach
</div>
@endsection
