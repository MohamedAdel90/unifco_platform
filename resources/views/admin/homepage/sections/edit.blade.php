@extends('layouts.app')
@section('title','Edit Section: '.$section->section_key.' | UNIFCO Platform')
@section('heading','Edit Section: '.$section->section_key)
@section('content')
<style>
.json-editor{display:grid;gap:16px;margin-top:14px}
.json-editor label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px}
.json-editor textarea{width:100%;min-height:220px;border:1px solid #dce1e8;border-radius:10px;padding:12px;font-family:'Courier New',monospace;font-size:12px;color:#17243c;background:#fafbfc;resize:vertical}
.json-editor textarea:focus{outline:2px solid #2563eb;border-color:#2563eb}
.json-editor .field-group{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:16px}
.json-editor .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.json-error{font-size:11px;color:#9f1f1f;margin-top:4px;display:none}
</style>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.homepage.sections.update', $section) }}" class="json-editor" onsubmit="return validateJson(this)">@csrf @method('PUT')
<div class="row">
  <div class="field-group">
    <label>data_ar (Arabic JSON)</label>
    <textarea name="data_ar" required>{{ json_encode($section->data_ar, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</textarea>
    <div class="json-error" id="err-ar">Invalid JSON</div>
  </div>
  <div class="field-group">
    <label>data_en (English JSON)</label>
    <textarea name="data_en" required>{{ json_encode($section->data_en, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</textarea>
    <div class="json-error" id="err-en">Invalid JSON</div>
  </div>
</div>
<div style="display:flex;gap:10px;align-items:center">
  <label style="font-size:12px;font-weight:700;margin:0">Sort order:</label>
  <input type="number" name="sort_order" value="{{ $section->sort_order }}" min="0" style="width:80px;border:1px solid #dce1e8;border-radius:8px;padding:6px 10px;font-size:13px">
  <div style="flex:1"></div>
  <a class="btn" href="{{ route('admin.homepage.sections.index') }}" style="background:#f2f6fa;color:#374151">Cancel</a>
  <button class="btn" type="submit">Save Section</button>
</div>
</form>
<script>
function validateJson(form){
  var ok=true;
  ['ar','en'].forEach(function(lang){
    var ta=form.querySelector('textarea[name="data_'+lang+'"]');
    var err=document.getElementById('err-'+lang);
    try{JSON.parse(ta.value);err.style.display='none';ta.style.borderColor='#dce1e8'}
    catch(e){err.textContent='Invalid JSON: '+e.message;err.style.display='block';ta.style.borderColor='#9f1f1f';ok=false}
  });
  return ok;
}
</script>
@endsection
