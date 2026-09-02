@extends('layouts.app')
@section('title','Homepage Sections | UNIFCO Platform')
@section('heading','Homepage CMS — Sections')
@section('content')
<style>
.hp-grid{display:grid;gap:10px;margin-top:16px}
.hp-card{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:16px 18px;display:grid;grid-template-columns:1fr auto auto auto;gap:12px;align-items:center}
.hp-card.disabled{opacity:.55}
.hp-key{font-size:14px;font-weight:700;color:#17243c}.hp-key small{display:block;font-weight:400;color:#728096;font-size:11px;margin-top:2px}
.hp-badge{font-size:10px;padding:4px 10px;border-radius:20px;font-weight:700;text-transform:uppercase}.hp-badge.on{background:#e6f7ee;color:#177a3d}.hp-badge.off{background:#fce8e8;color:#9f1f1f}
.hp-order{font-size:11px;color:#728096;font-weight:700;background:#f2f6fa;padding:4px 10px;border-radius:8px}
.hp-actions{display:flex;gap:8px}
.hp-actions .btn{font-size:11px;padding:6px 14px}
.btn-sm{font-size:11px;padding:5px 12px;border-radius:8px;cursor:pointer;border:1px solid #dce1e8;background:#fff;color:#374151}.btn-sm:hover{background:#f2f6fa}
.btn-ghost{background:transparent;border:1px solid #dce1e8;color:#374151}
</style>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
<p style="font-size:12px;color:#728096;margin:0 0 4px">Edit bilingual JSON content for each homepage section. Changes are reflected on the public site after cache clear (automatic on save).</p>
<div class="hp-grid">
@foreach($sections as $s)
<div class="hp-card {{ $s->is_active ? '' : 'disabled' }}">
  <div class="hp-key">{{ $s->section_key }}<small>{{ $s->section_label ?? $s->section_key }} · sort {{ $s->sort_order }}</small></div>
  <span class="hp-badge {{ $s->is_active ? 'on' : 'off' }}">{{ $s->is_active ? 'Active' : 'Off' }}</span>
  <div class="hp-actions">
    <a class="btn btn-sm" href="{{ route('admin.homepage.sections.edit', $s) }}">Edit JSON</a>
    <form method="POST" action="{{ route('admin.homepage.sections.toggle', $s) }}" style="display:inline">@csrf<button class="btn btn-sm btn-ghost" type="submit">{{ $s->is_active ? 'Disable' : 'Enable' }}</button></form>
  </div>
</div>
@endforeach
</div>
@endsection
