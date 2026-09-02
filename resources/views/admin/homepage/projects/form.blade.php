@extends('layouts.app')
@section('title',($project?'Edit':'Create').' Project | UNIFCO Platform')
@section('heading',($project?'Edit':'Create').' Project')
@section('content')
<style>
.form-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:24px;max-width:900px}
.form-card label{display:block;font-size:12px;font-weight:700;color:#374151;margin:12px 0 5px}
.form-card input,.form-card select{width:100%;border:1px solid #dce1e8;border-radius:9px;padding:9px 12px;font-size:13px;box-sizing:border-box}
.form-card input:focus,.form-card select:focus{outline:2px solid #2563eb;border-color:#2563eb}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-actions{display:flex;gap:10px;margin-top:18px;justify-content:flex-end}
</style>
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<form class="form-card" method="POST" action="{{ $project?route('admin.homepage.projects.update',$project):route('admin.homepage.projects.store') }}">@csrf
@if($project)@method('PUT')@endif

<label>Title (AR)</label>
<input type="text" name="title_ar" value="{{ old('title_ar',$project->title_ar??'') }}" required>

<label>Title (EN)</label>
<input type="text" name="title_en" value="{{ old('title_en',$project->title_en??'') }}" required>

<div class="form-row">
  <div><label>Owner (AR)</label><input type="text" name="owner_ar" value="{{ old('owner_ar',$project->owner_ar??'') }}" required></div>
  <div><label>Owner (EN)</label><input type="text" name="owner_en" value="{{ old('owner_en',$project->owner_en??'') }}" required></div>
</div>

<div class="form-row">
  <div><label>Location (AR)</label><input type="text" name="location_ar" value="{{ old('location_ar',$project->location_ar??'') }}" required></div>
  <div><label>Location (EN)</label><input type="text" name="location_en" value="{{ old('location_en',$project->location_en??'') }}" required></div>
</div>

<div class="form-row">
  <div><label>Scope (AR)</label><input type="text" name="scope_ar" value="{{ old('scope_ar',$project->scope_ar??'') }}" required></div>
  <div><label>Scope (EN)</label><input type="text" name="scope_en" value="{{ old('scope_en',$project->scope_en??'') }}" required></div>
</div>

<div class="form-row">
  <div><label>Year (display, e.g. 2024-2025)</label><input type="text" name="year" value="{{ old('year',$project->year??date('Y')) }}" maxlength="30" required></div>
  <div><label>Sort Order</label><input type="number" name="sort_order" value="{{ old('sort_order',$project->sort_order??0) }}" min="0"></div>
</div>

<label>Image Path (relative to public, e.g. /images/home/projects/xxx.webp)</label>
<input type="text" name="image" class="img-picker-target" value="{{ old('image',$project->image??'') }}" placeholder="/images/home/projects/">
@include('admin.homepage.partials.image-picker')

<div class="form-actions">
  <a class="btn" href="{{ route('admin.homepage.projects.index') }}" style="background:#f2f6fa;color:#374151">Cancel</a>
  <button class="btn" type="submit">{{ $project?'Update':'Create' }} Project</button>
</div>
</form>
@endsection
