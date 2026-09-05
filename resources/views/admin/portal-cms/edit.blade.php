@extends('layouts.app')
@section('title', $cmsTitle.' | UNIFCO Platform')
@section('heading', $cmsTitle)
@section('content')
@php
    $prefix = $cmsType === 'maintenance' ? 'maintenance' : 'portal';
    $other = $cmsType === 'maintenance' ? 'portal' : 'maintenance';
    $labels = $cmsType === 'maintenance'
        ? ['image' => 'Maintenance image', 'title' => 'Maintenance title', 'text' => 'Maintenance description', 'button' => 'Maintenance button', 'checks' => 'Maintenance features']
        : ['image' => 'Client Portal image', 'title' => 'Client Portal title', 'text' => 'Client Portal description', 'button' => 'Client Portal button', 'checks' => 'Client Portal features'];
@endphp
<style>
.cms-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:18px}.cms-head p{margin:5px 0 0;color:#728096;font-size:12px;line-height:1.7;max-width:720px}.cms-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.cms-card{background:#fff;border:1px solid #e1e7ef;border-radius:14px;padding:18px}.cms-card h3{margin:0 0 12px;color:#17243c;font-size:15px}.cms-card label{display:block;margin:12px 0 5px;font-size:11px;font-weight:800;color:#41506b}.cms-card input[type=text],.cms-card textarea{width:100%;box-sizing:border-box;border:1px solid #d7dfe9;border-radius:8px;padding:9px 11px;font-size:13px;color:#17243c}.cms-card textarea{min-height:92px;resize:vertical}.cms-actions{margin-top:16px;display:flex;gap:9px;justify-content:flex-end;flex-wrap:wrap}.cms-check{display:flex;gap:7px;margin-bottom:7px}.cms-check input{flex:1;border:1px solid #d7dfe9;border-radius:8px;padding:8px 10px}.cms-note{background:#f5f8fd;border:1px solid #dbe6f4;border-radius:10px;padding:10px 12px;color:#53647d;font-size:11px;margin-bottom:15px}.hp-img-field{display:grid;grid-template-columns:64px 1fr;gap:10px;align-items:center}.hp-img-preview{width:64px;height:64px;border:1px solid #e3e8ef;border-radius:10px;overflow:hidden;background:#f2f6fa;position:relative;display:flex;align-items:center;justify-content:center}.hp-img-preview img{width:100%;height:100%;object-fit:cover}.hp-img-preview .hp-img-ph{font-size:9px;color:#97a4b6;text-align:center}.hp-img-clear{position:absolute;top:2px;right:2px;background:#9f1f1f;color:#fff;border:0;border-radius:50%;width:18px;height:18px}.hp-img-actions{margin-top:4px}.btn-sm{border:1px solid #d8dfe8;background:#f2f6fa;color:#374151;border-radius:7px;padding:6px 12px;font-size:11px;font-weight:700;cursor:pointer}@media(max-width:760px){.cms-grid{grid-template-columns:1fr}}
</style>

<div class="cms-head">
  <div>
    <h2 style="margin:0;color:#17243c">{{ $cmsTitle }}</h2>
    <p>This is a separate CMS workspace. Changes here affect only {{ $cmsType === 'maintenance' ? 'Maintenance' : 'Client Portal' }} content and media; Homepage CMS sections remain separate.</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a class="btn secondary" href="{{ route('admin.homepage.sections.index') }}">Homepage CMS</a>
    <a class="btn" target="_blank" rel="noopener" href="{{ $publicUrl }}">Open {{ $cmsType === 'maintenance' ? 'Maintenance' : 'Client Portal' }}</a>
  </div>
</div>

@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<div class="cms-note">You can change Arabic and English text independently and select/upload an image for this area. Saving clears the public homepage cache through the existing CMS update flow.</div>

<form method="POST" action="{{ route('admin.homepage.sections.update', $section) }}">
@csrf @method('PUT')

{{-- Preserve the other portal checklist so editing one CMS never wipes the other. --}}
@foreach(($section->data_ar[$other.'_checks'] ?? []) as $i => $value)
<input type="hidden" name="check_ar_{{ $other }}_checks[{{ $i }}]" value="{{ $value }}">
@endforeach
@foreach(($section->data_en[$other.'_checks'] ?? []) as $i => $value)
<input type="hidden" name="check_en_{{ $other }}_checks[{{ $i }}]" value="{{ $value }}">
@endforeach

<div class="cms-grid">
  @foreach(['ar' => 'Arabic', 'en' => 'English'] as $locale => $language)
  <section class="cms-card">
    <h3>{{ $language }}</h3>

    <label>{{ $labels['image'] }} ({{ strtoupper($locale) }})</label>
    @include('admin.homepage.sections.partials.image-field', [
      'name' => 'scalar_'.$locale.'_'.$prefix.'_image',
      'value' => old('scalar_'.$locale.'_'.$prefix.'_image', $section->{'data_'.$locale}[$prefix.'_image'] ?? ''),
      'placeholder' => '/images/...',
      'label' => $language.' '.$labels['image'],
    ])

    <label>{{ $labels['title'] }}</label>
    <input type="text" name="scalar_{{ $locale }}_{{ $prefix }}_title" value="{{ old('scalar_'.$locale.'_'.$prefix.'_title', $section->{'data_'.$locale}[$prefix.'_title'] ?? '') }}">

    <label>{{ $labels['text'] }}</label>
    <textarea name="scalar_{{ $locale }}_{{ $prefix }}_text">{{ old('scalar_'.$locale.'_'.$prefix.'_text', $section->{'data_'.$locale}[$prefix.'_text'] ?? '') }}</textarea>

    <label>{{ $labels['button'] }}</label>
    <input type="text" name="scalar_{{ $locale }}_{{ $prefix }}_button" value="{{ old('scalar_'.$locale.'_'.$prefix.'_button', $section->{'data_'.$locale}[$prefix.'_button'] ?? '') }}">

    <label>{{ $labels['checks'] }}</label>
    @php($rows = old('check_'.$locale.'_'.$prefix.'_checks', $section->{'data_'.$locale}[$prefix.'_checks'] ?? []))
    @for($i = 0; $i < max(6, count($rows)); $i++)
      <div class="cms-check"><input type="text" name="check_{{ $locale }}_{{ $prefix }}_checks[{{ $i }}]" value="{{ $rows[$i] ?? '' }}" placeholder="Feature {{ $i + 1 }}"></div>
    @endfor
  </section>
  @endforeach
</div>

@include('admin.homepage.partials.image-picker')

<div class="cms-actions">
  <a class="btn secondary" href="{{ route('admin.homepage.sections.index') }}">Cancel</a>
  <button class="btn" type="submit">Save {{ $cmsTitle }}</button>
</div>
</form>
@endsection
