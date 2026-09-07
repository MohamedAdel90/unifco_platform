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
.cms-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:18px}.cms-head p{margin:5px 0 0;color:#728096;font-size:12px;line-height:1.7;max-width:720px}.cms-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.cms-card{background:#fff;border:1px solid #e1e7ef;border-radius:14px;padding:18px}.cms-card h3{margin:0 0 12px;color:#17243c;font-size:15px}.cms-card label{display:block;margin:12px 0 5px;font-size:11px;font-weight:800;color:#41506b}.cms-card input[type=text],.cms-card textarea{width:100%;box-sizing:border-box;border:1px solid #d7dfe9;border-radius:8px;padding:9px 11px;font-size:13px;color:#17243c}.cms-card textarea{min-height:92px;resize:vertical}.cms-actions{margin-top:16px;display:flex;gap:9px;justify-content:flex-end;flex-wrap:wrap;align-items:center}.cms-preview-actions{display:flex;gap:8px;margin-right:auto}.preview-btn{border:1px solid #9fb6da;background:#f5f8fd;color:#17366c;border-radius:9px;padding:9px 16px;font-size:12px;font-weight:800;cursor:pointer}.preview-btn:hover{background:#eaf1fb}.cms-check{display:flex;gap:7px;margin-bottom:7px}.cms-check input{flex:1;border:1px solid #d7dfe9;border-radius:8px;padding:8px 10px}.cms-note{background:#f5f8fd;border:1px solid #dbe6f4;border-radius:10px;padding:10px 12px;color:#53647d;font-size:11px;margin-bottom:15px}.hp-img-field{display:grid;grid-template-columns:64px 1fr;gap:10px;align-items:center}.hp-img-preview{width:64px;height:64px;border:1px solid #e3e8ef;border-radius:10px;overflow:hidden;background:#f2f6fa;position:relative;display:flex;align-items:center;justify-content:center}.hp-img-preview img{width:100%;height:100%;object-fit:cover}.hp-img-preview .hp-img-ph{font-size:9px;color:#97a4b6;text-align:center}.hp-img-clear{position:absolute;top:2px;right:2px;background:#9f1f1f;color:#fff;border:0;border-radius:50%;width:18px;height:18px}.hp-img-actions{margin-top:4px}.btn-sm{border:1px solid #d8dfe8;background:#f2f6fa;color:#374151;border-radius:7px;padding:6px 12px;font-size:11px;font-weight:700;cursor:pointer}.preview-modal{position:fixed;inset:0;z-index:10000;background:rgba(8,18,35,.78);padding:18px;display:none;align-items:center;justify-content:center}.preview-modal.open{display:flex}.preview-shell{width:min(1440px,100%);height:min(94vh,980px);background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.34);display:grid;grid-template-rows:auto 1fr}.preview-head{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#132137;color:#fff}.preview-head strong{font-size:13px}.preview-head small{font-size:10px;color:#cbd5e1}.preview-head .spacer{flex:1}.preview-close{border:1px solid #7d8ca3;background:transparent;color:#fff;border-radius:7px;padding:6px 11px;font-weight:800;cursor:pointer}.preview-frame{width:100%;height:100%;border:0;background:#fff}.preview-loading{position:absolute;background:#fff;border:1px solid #dce3ec;border-radius:9px;padding:10px 14px;font-size:12px;font-weight:700;color:#41516b;box-shadow:0 8px 25px rgba(0,0,0,.12);display:none}.preview-loading.show{display:block}@media(max-width:760px){.cms-grid{grid-template-columns:1fr}.cms-actions{align-items:stretch}.cms-preview-actions{width:100%;margin-right:0}.preview-btn{flex:1}.preview-modal{padding:4px}.preview-shell{height:98vh;border-radius:8px}}
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
<div class="cms-note">Arabic remains the primary text source. Images copy from Arabic to English automatically unless a different English image is selected manually.</div>

<form id="portal-cms-form" method="POST" action="{{ route('admin.homepage.sections.update', $section) }}">
@csrf @method('PUT')

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

    <input type="text"
           name="scalar_{{ $locale }}_{{ $prefix }}_display_mode"
           value="{{ old('scalar_'.$locale.'_'.$prefix.'_display_mode', $section->{'data_'.$locale}[$prefix.'_display_mode'] ?? 'structured') }}">
    <input type="text"
           name="scalar_{{ $locale }}_{{ $prefix }}_full_section_image"
           value="{{ old('scalar_'.$locale.'_'.$prefix.'_full_section_image', $section->{'data_'.$locale}[$prefix.'_full_section_image'] ?? '') }}">

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
  <div class="cms-preview-actions">
    <button type="button" class="preview-btn" data-preview-locale="ar">Preview AR</button>
    <button type="button" class="preview-btn" data-preview-locale="en">Preview EN</button>
  </div>
  <a class="btn secondary" href="{{ route('admin.homepage.sections.index') }}">Cancel</a>
  <button class="btn" type="submit">Save {{ $cmsTitle }}</button>
</div>
</form>

<div class="preview-modal" id="portal-preview-modal" aria-hidden="true">
  <div class="preview-shell">
    <div class="preview-head">
      <strong>Unsaved Preview · {{ $cmsTitle }}</strong>
      <small id="portal-preview-locale-label"></small>
      <div class="spacer"></div>
      <button type="button" class="preview-close" id="portal-preview-close">Close</button>
    </div>
    <iframe class="preview-frame" id="portal-preview-frame" title="{{ $cmsTitle }} preview"></iframe>
  </div>
  <div class="preview-loading" id="portal-preview-loading">Building preview…</div>
</div>

<script>
(function(){
  var form=document.getElementById('portal-cms-form');
  var modal=document.getElementById('portal-preview-modal');
  var frame=document.getElementById('portal-preview-frame');
  var close=document.getElementById('portal-preview-close');
  var loading=document.getElementById('portal-preview-loading');
  var localeLabel=document.getElementById('portal-preview-locale-label');
  var previewUrl='{{ route('admin.homepage.sections.preview', $section) }}';

  function closePreview(){
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden','true');
    frame.srcdoc='';
  }

  close.addEventListener('click',closePreview);
  modal.addEventListener('click',function(e){if(e.target===modal)closePreview();});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('open'))closePreview();});

  document.querySelectorAll('[data-preview-locale]').forEach(function(btn){
    btn.addEventListener('click',function(){
      var locale=btn.dataset.previewLocale;
      var fd=new FormData(form);
      fd.delete('_method');
      fd.set('locale',locale);
      loading.classList.add('show');
      btn.disabled=true;

      fetch(previewUrl,{
        method:'POST',
        headers:{'Accept':'text/html'},
        body:fd,
        credentials:'same-origin'
      }).then(function(r){
        if(!r.ok)throw new Error('Preview failed ('+r.status+')');
        return r.text();
      }).then(function(html){
        frame.srcdoc=html;
        localeLabel.textContent=locale==='ar'?'Arabic · RTL':'English · LTR';
        modal.classList.add('open');
        modal.setAttribute('aria-hidden','false');
      }).catch(function(err){
        alert(err.message||'Preview failed');
      }).finally(function(){
        loading.classList.remove('show');
        btn.disabled=false;
      });
    });
  });
})();
</script>
@endsection
