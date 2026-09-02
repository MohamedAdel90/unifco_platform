@extends('layouts.app')
@section('title',($client?'Edit':'Create').' Client | UNIFCO Platform')
@section('heading',($client?'Edit':'Create').' Client')
@section('content')
<style>
.form-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:24px;max-width:700px}
.form-card label{display:block;font-size:12px;font-weight:700;color:#374151;margin:12px 0 5px}
.form-card input{width:100%;border:1px solid #dce1e8;border-radius:9px;padding:9px 12px;font-size:13px;box-sizing:border-box}
.form-card input:focus{outline:2px solid #2563eb;border-color:#2563eb}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-actions{display:flex;gap:10px;margin-top:18px;justify-content:flex-end}
.logo-preview{margin:10px 0;display:flex;align-items:center;gap:14px}.logo-preview img{width:80px;height:80px;object-fit:contain;border-radius:10px;border:1px solid #e3e8ef;background:#f8fafc;padding:8px}
</style>
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<form class="form-card" method="POST" action="{{ $client?route('admin.homepage.clients.update',$client):route('admin.homepage.clients.store') }}">@csrf
@if($client)@method('PUT')@endif

<div class="form-row">
  <div><label>Name (EN)</label><input type="text" name="name_en" value="{{ old('name_en',$client->name_en??'') }}" required></div>
  <div><label>Name (AR)</label><input type="text" name="name_ar" value="{{ old('name_ar',$client->name_ar??'') }}" required></div>
</div>

@if($client && $client->image)
<div class="logo-preview">
  <img src="{{ $client->image }}" alt="{{ $client->name_en }}">
  <div><small style="color:#728096;font-size:11px">Current logo</small></div>
</div>
@endif

<label>Logo/Image Path (relative to public, e.g. /images/home/clients/xxx.webp)</label>
<input type="text" name="image" class="img-picker-target" value="{{ old('image',$client->image??'') }}" placeholder="/images/home/clients/">
@include('admin.homepage.partials.image-picker')

<label>Sort Order</label>
<input type="number" name="sort_order" value="{{ old('sort_order',$client->sort_order??0) }}" min="0" style="width:120px">

<div class="form-actions">
  <a class="btn" href="{{ route('admin.homepage.clients.index') }}" style="background:#f2f6fa;color:#374151">Cancel</a>
  <button class="btn" type="submit">{{ $client?'Update':'Create' }} Client</button>
</div>
</form>
@endsection
