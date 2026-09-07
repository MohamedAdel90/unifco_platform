@extends('layouts.app')
@section('title','Edit Section: '.$section->section_key.' | UNIFCO Platform')
@section('heading','Edit Section: '.$section->section_key)
@section('content')
@php
  $scalars = $schema['scalars'] ?? [];
  $items = $schema['items'] ?? [];
  $checks = $schema['checks'] ?? [];
  $imgKeys = ['image'];
  $long = fn($f) => in_array($f, ['text','desc','about','contact'], true) || str_contains($f,'desc') || str_contains($f,'text') || str_contains($f,'about') || str_contains($f,'contact');
@endphp
<style>
.section-form{display:grid;gap:18px;margin-top:14px}
.seg{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.card{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:18px}
.card label{display:block;font-size:11px;font-weight:700;color:#374151;margin:12px 0 5px;text-transform:capitalize}
.card label.first{margin-top:0}
.card input[type=text],.card input[type=number],.card textarea,.card select{width:100%;border:1px solid #dce1e8;border-radius:8px;padding:8px 11px;font-size:13px;box-sizing:border-box;color:#17243c;background:#fff}
.card input:focus,.card textarea:focus,.card select:focus{outline:2px solid #2563eb;border-color:#2563eb}
.card textarea{min-height:76px;resize:vertical}
.col-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px}
.col-head b{font-size:13px;color:#1e315b;letter-spacing:.02em}
.col-head small{font-size:10px;color:#8a97aa;font-weight:600}
.hero-mode-help{margin-top:7px;padding:9px 10px;border-radius:8px;background:#f5f8fd;border:1px solid #dbe7f5;color:#53647d;font-size:10px;line-height:1.6}
.hero-mode-help strong{color:#17366c}
.item-block{border:1px solid #e3e8ef;background:#fbfcfe;border-radius:10px;padding:12px;margin:10px 0}
.item-block .item-title{font-size:11px;font-weight:800;color:#374151;display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.item-block .item-title .idx{color:#8a97aa}
.item-subrow{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.repeat-grid{display:grid;gap:10px}
.row-actions{display:flex;gap:8px;margin-top:10px}
.btn-sm{border:1px solid #d8dfe8;background:#f2f6fa;color:#374151;border-radius:7px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer}
.btn-sm.danger{border-color:#ead9d9;background:#fdf2f2;color:#9f1f1f}
.btn-sm.primary{color:#1f4e9c;background:#eef4fd;border-color:#d6e4f6}
.check-list{display:grid;gap:8px}
.check-list .cl-row{display:flex;gap:8px;align-items:center}
.check-list input{flex:1}
.form-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:14px 18px}
.preview-actions{display:flex;gap:7px;align-items:center}
.preview-btn{border:1px solid #9fb6da;background:#f5f8fd;color:#17366c;border-radius:7px;padding:8px 12px;font-size:12px;font-weight:800;cursor:pointer}
.preview-btn:hover{background:#eaf1fb}
.image-slot{display:flex;gap:6px;align-items:center}
.image-slot input{flex:1}
.hp-img-field{display:grid;grid-template-columns:56px 1fr;gap:10px;align-items:center}
.hp-img-field.active-img-field .hp-img-preview{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.hp-img-preview{width:56px;height:56px;border:1px solid #e3e8ef;border-radius:10px;overflow:hidden;background:#f2f6fa;position:relative;display:flex;align-items:center;justify-content:center}
.hp-img-preview img{width:100%;height:100%;object-fit:cover;display:block}
.hp-img-preview .hp-img-ph{font-size:9px;color:#97a4b6;text-align:center;line-height:1.2;padding:4px}
.hp-img-clear{position:absolute;top:2px;right:2px;background:rgba(159,31,31,.9);color:#fff;border:0;border-radius:50%;width:18px;height:18px;font-size:13px;line-height:1;cursor:pointer;padding:0}
.hp-img-field .hp-img-input{font-size:11.5px}
.hp-img-actions{margin-top:4px}
.hp-img-actions .btn-sm{font-size:11px;padding:5px 11px}
.preview-modal{position:fixed;inset:0;z-index:10000;background:rgba(8,18,35,.78);padding:18px;display:none;align-items:center;justify-content:center}
.preview-modal.open{display:flex}
.preview-shell{width:min(1440px,100%);height:min(94vh,980px);background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.34);display:grid;grid-template-rows:auto 1fr}
.preview-head{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#132137;color:#fff}
.preview-head strong{font-size:13px}.preview-head small{font-size:10px;color:#cbd5e1}.preview-head .spacer{flex:1}
.preview-close{border:1px solid #7d8ca3;background:transparent;color:#fff;border-radius:7px;padding:6px 11px;font-weight:800;cursor:pointer}
.preview-frame{width:100%;height:100%;border:0;background:#fff}
.preview-loading{position:absolute;background:#fff;border:1px solid #dce3ec;border-radius:9px;padding:10px 14px;font-size:12px;font-weight:700;color:#41516b;box-shadow:0 8px 25px rgba(0,0,0,.12);display:none}
.preview-loading.show{display:block}
@media(max-width:760px){.seg{grid-template-columns:1fr}.item-subrow{grid-template-columns:1fr}.form-actions{align-items:stretch}.form-actions .grow{display:none}.preview-actions{width:100%}.preview-btn{flex:1}.preview-modal{padding:4px}.preview-shell{height:98vh;border-radius:8px}}
</style>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif

<form id="homepage-section-form" method="POST" action="{{ route('admin.homepage.sections.update', $section) }}" class="section-form">@csrf @method('PUT')

@if(count($scalars)||count($checks)||count($items))
  <div class="seg">
    <div class="card">
      <div class="col-head"><b>Arabic</b><small>RTL · اليمين</small></div>
      @foreach($scalars as $f)
        @if($f === 'render_mode')
          @php($modeAr = old('scalar_ar_render_mode', $section->data_ar['render_mode'] ?? 'background'))
          <label class="first">Hero Render Mode (AR)</label>
          <select name="scalar_ar_render_mode" data-hero-mode-select="ar">
            <option value="background" @selected($modeAr === 'background')>Background image + CMS content</option>
            <option value="full_banner" @selected($modeAr === 'full_banner')>Full banner image (content already inside image)</option>
          </select>
          <div class="hero-mode-help" data-hero-mode-help="ar"></div>
        @elseif(in_array($f,$imgKeys,true))
          <label class="first">Image (AR)</label>
          @include('admin.homepage.sections.partials.image-field', [
            'name'=>'scalar_ar_'.$f,
            'value'=>old('scalar_ar_'.$f, $section->data_ar[$f] ?? ''),
            'placeholder'=>'/images/...',
            'label'=>'Arabic '.$f
          ])
        @elseif($long($f))
          <label class="first">{{ $f }} (AR)</label>
          <textarea name="scalar_ar_{{$f}}">{{ old('scalar_ar_'.$f, $section->data_ar[$f] ?? '') }}</textarea>
        @else
          <label class="first">{{ $f }} (AR)</label>
          <input type="text" name="scalar_ar_{{$f}}" value="{{ old('scalar_ar_'.$f, $section->data_ar[$f] ?? '') }}">
        @endif
      @endforeach
    </div>
    <div class="card">
      <div class="col-head"><b>English</b><small>LTR · Left</small></div>
      @foreach($scalars as $f)
        @if($f === 'render_mode')
          @php($modeEn = old('scalar_en_render_mode', $section->data_en['render_mode'] ?? 'background'))
          <label class="first">Hero Render Mode (EN)</label>
          <select name="scalar_en_render_mode" data-hero-mode-select="en">
            <option value="background" @selected($modeEn === 'background')>Background image + CMS content</option>
            <option value="full_banner" @selected($modeEn === 'full_banner')>Full banner image (content already inside image)</option>
          </select>
          <div class="hero-mode-help" data-hero-mode-help="en"></div>
        @elseif(in_array($f,$imgKeys,true))
          <label class="first">Image (EN)</label>
          @include('admin.homepage.sections.partials.image-field', [
            'name'=>'scalar_en_'.$f,
            'value'=>old('scalar_en_'.$f, $section->data_en[$f] ?? ''),
            'placeholder'=>'/images/...',
            'label'=>'English '.$f
          ])
        @elseif($long($f))
          <label class="first">{{ $f }} (EN)</label>
          <textarea name="scalar_en_{{$f}}">{{ old('scalar_en_'.$f, $section->data_en[$f] ?? '') }}</textarea>
        @else
          <label class="first">{{ $f }} (EN)</label>
          <input type="text" name="scalar_en_{{$f}}" value="{{ old('scalar_en_'.$f, $section->data_en[$f] ?? '') }}">
        @endif
      @endforeach
    </div>
  </div>

  @foreach($items as $listKey => $itemFields)
    @include('admin.homepage.sections.partials.items', [
      'listKey'=>$listKey,'itemFields'=>$itemFields,'locale'=>'ar',
      'rows'=>$section->data_ar[$listKey] ?? []
    ])
    @include('admin.homepage.sections.partials.items', [
      'listKey'=>$listKey,'itemFields'=>$itemFields,'locale'=>'en',
      'rows'=>$section->data_en[$listKey] ?? []
    ])
  @endforeach

  @foreach($checks as $listKey)
    @include('admin.homepage.sections.partials.checklist', [
      'listKey'=>$listKey,'locale'=>'ar','rows'=>$section->data_ar[$listKey] ?? []
    ])
    @include('admin.homepage.sections.partials.checklist', [
      'listKey'=>$listKey,'locale'=>'en','rows'=>$section->data_en[$listKey] ?? []
    ])
  @endforeach
@else
<div class="card">
  <p style="margin:0;color:#8a97aa;font-size:12px">This section has flexible/no predefined fields. No form definition available for key "<b>{{ $section->section_key }}</b>".</p>
</div>
@endif

@include('admin.homepage.partials.image-picker')

<div class="form-actions">
  <label style="font-size:12px;font-weight:700;color:#374151;margin:0">Sort order:</label>
  <input type="number" name="sort_order" value="{{ $section->sort_order }}" min="0" style="width:80px;border:1px solid #dce1e8;border-radius:8px;padding:6px 10px;font-size:13px">
  <div class="grow" style="flex:1"></div>
  <div class="preview-actions">
    <button type="button" class="preview-btn" data-preview-locale="ar">Preview AR</button>
    <button type="button" class="preview-btn" data-preview-locale="en">Preview EN</button>
  </div>
  <a class="btn" href="{{ route('admin.homepage.sections.index') }}" style="background:#f2f6fa;color:#374151">Cancel</a>
  <button class="btn" type="submit">Save Section</button>
</div>
</form>

<div class="preview-modal" id="section-preview-modal" aria-hidden="true">
  <div class="preview-shell">
    <div class="preview-head">
      <strong>Unsaved Preview · {{ $section->section_key }}</strong>
      <small id="section-preview-locale-label"></small>
      <div class="spacer"></div>
      <button type="button" class="preview-close" id="section-preview-close">Close</button>
    </div>
    <iframe class="preview-frame" id="section-preview-frame" title="Homepage section preview"></iframe>
  </div>
  <div class="preview-loading" id="section-preview-loading">Building preview…</div>
</div>

<script>
(function(){
  document.querySelectorAll('[data-hero-mode-select]').forEach(function(select){
    var locale=select.dataset.heroModeSelect;
    var help=document.querySelector('[data-hero-mode-help="'+locale+'"]');
    var refresh=function(){
      if(!help)return;
      help.innerHTML=select.value==='full_banner'
        ? '<strong>Full banner:</strong> the selected image is rendered exactly once at full width. CMS title, text, buttons and proofs are preserved in storage but intentionally hidden on the public Hero.'
        : '<strong>Background:</strong> the selected image is used as a clean background and CMS title, text, buttons and proofs are rendered above it.';
    };
    select.addEventListener('change',refresh);
    refresh();
  });

  document.querySelectorAll('[data-repeater]').forEach(function(zone){
    var wrap=zone.querySelector('.rows');
    var used=Array.from(wrap.querySelectorAll('input[type="hidden"][name$="_index[]"]'))
      .map(function(i){return parseInt(i.value,10);})
      .filter(function(i){return Number.isFinite(i);});
    var nextIndex=used.length ? Math.max.apply(null,used)+1 : 0;

    zone.querySelector('.add-row').addEventListener('click',function(){
      var tpl=zone.querySelector('.row-template').innerHTML;
      var idx=nextIndex++;
      wrap.insertAdjacentHTML('beforeend',tpl.replace(/\{\{index\}\}/g,idx));
    });
    wrap.addEventListener('click',function(e){
      var rm=e.target.closest('.remove-row');
      if(rm){rm.closest('.item-block').remove();}
    });
  });

  document.querySelectorAll('[data-checklist]').forEach(function(zone){
    var wrap=zone.querySelector('.rows');
    var used=Array.from(wrap.querySelectorAll('input[type="text"]')).map(function(input){
      var m=(input.name||'').match(/\[(\d+)\]$/);
      return m ? parseInt(m[1],10) : -1;
    }).filter(function(i){return i>=0;});
    var nextIndex=used.length ? Math.max.apply(null,used)+1 : 0;

    zone.querySelector('.add-row').addEventListener('click',function(){
      var idx=nextIndex++;
      wrap.insertAdjacentHTML('beforeend','<div class="cl-row"><input type="text" name="'+zone.dataset.base+'['+idx+']" value=""><button type="button" class="btn-sm danger remove-row">×</button></div>');
    });
    wrap.addEventListener('click',function(e){
      var rm=e.target.closest('.remove-row');
      if(rm){rm.closest('.cl-row').remove();}
    });
  });

  var form=document.getElementById('homepage-section-form');
  var modal=document.getElementById('section-preview-modal');
  var frame=document.getElementById('section-preview-frame');
  var close=document.getElementById('section-preview-close');
  var loading=document.getElementById('section-preview-loading');
  var localeLabel=document.getElementById('section-preview-locale-label');
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
        if(!r.ok){throw new Error('Preview failed ('+r.status+')');}
        return r.text();
      }).then(function(html){
        frame.srcdoc=html;
        localeLabel.textContent=locale==='ar'?'Arabic · RTL':'English · LTR';
        modal.classList.add('open');
        modal.setAttribute('aria-hidden','false');
      }).catch(function(err){
        alert(err.message || 'Preview failed');
      }).finally(function(){
        loading.classList.remove('show');
        btn.disabled=false;
      });
    });
  });
})();
</script>
@endsection
