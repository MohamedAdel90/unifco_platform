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
$operations = $sections->firstWhere('section_key', 'operations');
$totalSections = $homepageSections->count() + 2;
$totalActive = $homepageSections->where('is_active', true)->count() + ($operations?->is_active ? 2 : 0);
$operationsUpdated = $operations?->updated_at ? $operations->updated_at->diffForHumans() : '—';
@endphp
<style>
.hp-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}.hp-intro{max-width:700px}.hp-intro p{font-size:12.5px;color:#728096;margin:4px 0 0;line-height:1.6}
.hp-section-title{margin:20px 0 0;font-size:16px;color:#17243c}.hp-stats{display:flex;gap:10px;margin-top:10px;flex-wrap:wrap}.hp-stat{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:12px 16px;min-width:150px}.hp-stat b{display:block;font-size:22px;color:#17243c;line-height:1}.hp-stat span{font-size:11px;color:#728096;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.hp-grid{display:grid;gap:10px;margin-top:14px;grid-template-columns:repeat(auto-fill,minmax(330px,1fr))}.hp-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:16px 18px;display:flex;gap:14px;align-items:flex-start;box-shadow:0 1px 2px rgba(23,36,60,.04)}.hp-card.disabled{opacity:.6}.hp-ic{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:19px;background:#eef3fa;color:var(--navy);flex:0 0 auto}.hp-card-body{flex:1;min-width:0}.hp-top{display:flex;align-items:center;justify-content:space-between;gap:10px}.hp-title{font-size:14px;font-weight:750;color:#17243c;display:flex;align-items:center;gap:8px;flex-wrap:wrap}.hp-key{font-size:10.5px;color:#728096;font-weight:600;background:#f2f6fa;border:1px solid #e3e8ef;padding:2px 8px;border-radius:20px}.hp-meta{font-size:11.5px;color:#728096;margin-top:4px}.hp-meta b{color:#41506b}.hp-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px}.hp-actions{display:flex;gap:8px}.hp-actions .btn{font-size:11.5px;padding:7px 15px;border-radius:9px}.btn-ghost{background:#fff;border:1px solid #d7dfe9;color:#41506b}.badge{font-size:10px;padding:4px 11px;border-radius:20px;font-weight:700;text-transform:uppercase;display:inline-flex;align-items:center;gap:5px}.badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}.badge.on{background:#e6f7ee;color:#177a3d}.badge.off{background:#fae9e9;color:#a42323}
</style>

<div class="hp-hero">
  <div class="hp-intro">
    <h2 style="margin:0;font-size:20px;color:#17243c">Website Content Management</h2>
    <p>Manage all website areas from one consistent CMS list. Maintenance and Client Portal remain independently editable while appearing alongside the other sections.</p>
  </div>
  <a class="btn secondary" target="_blank" rel="noopener" href="{{ url('/') }}">View public site</a>
</div>

@if(session('status'))<div class="notice" style="margin-top:16px">{{ session('status') }}</div>@endif

<h3 class="hp-section-title">Homepage Sections</h3>
<div class="hp-stats">
  <div class="hp-stat"><b>{{ $totalSections }}</b><span>Total sections</span></div>
  <div class="hp-stat"><b>{{ $totalActive }}</b><span>Active</span></div>
  <div class="hp-stat"><b>{{ $totalSections - $totalActive }}</b><span>Disabled</span></div>
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

<div class="hp-card {{ $operations?->is_active ? '' : 'disabled' }}">
  <div class="hp-ic">⚒</div>
  <div class="hp-card-body">
    <div class="hp-top"><span class="hp-title">Maintenance <span class="hp-key">maintenance</span></span><span class="badge {{ $operations?->is_active ? 'on' : 'off' }}">{{ $operations?->is_active ? 'Active' : 'Off' }}</span></div>
    <div class="hp-meta"><b>5</b> fields per language · separate CMS</div>
    <div class="hp-foot"><span class="hp-meta" style="margin:0">Updated {{ $operationsUpdated }}</span><div class="hp-actions"><a class="btn" href="{{ route('admin.maintenance-cms') }}">Edit</a></div></div>
  </div>
</div>

<div class="hp-card {{ $operations?->is_active ? '' : 'disabled' }}">
  <div class="hp-ic">▣</div>
  <div class="hp-card-body">
    <div class="hp-top"><span class="hp-title">Client Portal <span class="hp-key">client_portal</span></span><span class="badge {{ $operations?->is_active ? 'on' : 'off' }}">{{ $operations?->is_active ? 'Active' : 'Off' }}</span></div>
    <div class="hp-meta"><b>5</b> fields per language · separate CMS</div>
    <div class="hp-foot"><span class="hp-meta" style="margin:0">Updated {{ $operationsUpdated }}</span><div class="hp-actions"><a class="btn" href="{{ route('admin.client-portal-cms') }}">Edit</a></div></div>
  </div>
</div>
</div>
@endsection
