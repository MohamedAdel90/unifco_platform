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
.form-actions{display:flex;gap:10px;align-items:center;background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:14px 18px}
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
@media(max-width:760px){.seg{grid-template-columns:1fr}.item-subrow{grid-template-columns:1fr}}
</style>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('admin.homepage.sections.update', $section) }}" class="section-form">@csrf @method('PUT')

@if(count($scalars)||count($checks)||count($items))
  <div class="seg">
    <div class="card">
      <div class="col-head"><b>Arabic</b><small>RTL · اليمين</small></div>
      @foreach($scalars as $f)
        @if(in_array($f,$imgKeys,true))
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
        @if(in_array($f,$imgKeys,true))
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
  <div style="flex:1"></div>
  <a class="btn" href="{{ route('admin.homepage.sections.index') }}" style="background:#f2f6fa;color:#374151">Cancel</a>
  <button class="btn" type="submit">Save Section</button>
</div>
</form>

<script>
(function(){
  document.querySelectorAll('[data-repeater]').forEach(function(zone){
    zone.querySelector('.add-row').addEventListener('click',function(){
      var tpl=zone.querySelector('.row-template').innerHTML;
      var wrap=zone.querySelector('.rows');
      var idx=wrap.children.length;
      wrap.insertAdjacentHTML('beforeend',tpl.replace(/\{\{index\}\}/g,idx));
    });
    zone.querySelector('.rows').addEventListener('click',function(e){
      var rm=e.target.closest('.remove-row');
      if(rm){rm.closest('.item-block').remove();}
    });
  });
  document.querySelectorAll('[data-checklist]').forEach(function(zone){
    zone.querySelector('.add-row').addEventListener('click',function(){
      var wrap=zone.querySelector('.rows');
      var idx=wrap.children.length;
      wrap.insertAdjacentHTML('beforeend','<div class="cl-row"><input type="text" name="'+zone.dataset.base+'['+idx+']" value=""><button type="button" class="btn-sm danger remove-row">×</button></div>');
    });
    zone.querySelector('.rows').addEventListener('click',function(e){
      var rm=e.target.closest('.remove-row');
      if(rm){rm.closest('.cl-row').remove();}
    });
  });
})();
</script>
@endsection
