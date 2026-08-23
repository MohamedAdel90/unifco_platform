@extends('layouts.app')
@section('title','Website Branding | UNIFCO Platform')
@section('heading','Website Branding')
@section('content')
<style>
.branding-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);gap:16px}.branding-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:20px}.branding-card h2{font-size:17px;margin:0 0 5px;color:#17243c}.branding-card p{font-size:11px;color:#728096;line-height:1.7}.logo-preview{min-height:210px;border:1px dashed #bcc7d6;border-radius:12px;display:grid;place-items:center;background:#f8fafc;padding:24px;margin:16px 0}.logo-preview img{display:block;max-width:min(460px,90%);max-height:170px;object-fit:contain}.upload-box{display:grid;gap:10px;margin-top:14px}.upload-box input[type=file]{background:#fff}.meta{display:grid;grid-template-columns:repeat(2,1fr);gap:9px;margin-top:12px}.meta div{background:#f7f9fc;border-radius:9px;padding:11px}.meta small{display:block;font-size:8px;text-transform:uppercase;color:#8190a4;font-weight:800}.meta b{display:block;margin-top:4px;font-size:11px;color:#25344e;word-break:break-word}.impact{display:grid;gap:8px;margin-top:14px}.impact div{padding:11px;border-radius:9px;background:#f7f9fc;font-size:10px}.impact b{display:block;color:#1e315b;margin-bottom:3px}.danger-zone{border-top:1px solid #edf0f4;margin-top:18px;padding-top:16px}.danger-btn{background:#9f1f1f}.note{font-size:9px;color:#7a889b;margin-top:8px}@media(max-width:900px){.branding-grid{grid-template-columns:1fr}}@media(max-width:520px){.meta{grid-template-columns:1fr}}
</style>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<div class="branding-grid">
<section class="branding-card">
<h2>Website Logo</h2>
<p>Upload one logo here and the platform will serve it dynamically everywhere the shared brand asset is used: login, sidebar, public website, and other branded sections.</p>
<div class="logo-preview"><img src="{{ route('brand.logo') }}?v={{ $branding?->updated_at?->timestamp ?? 'default' }}" alt="Current website logo"></div>
<form class="upload-box" method="POST" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data">@csrf
<label><strong>Choose new logo</strong><input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required></label>
<div><button class="btn" type="submit">Upload & Apply Everywhere</button></div>
</form>
<div class="note">Accepted: PNG, JPG/JPEG, WEBP · Maximum size: 5 MB. Transparent PNG/WEBP is recommended.</div>
@if($branding)
<div class="meta"><div><small>Current source file</small><b>{{ $branding->logo_original_name ?: 'Uploaded logo' }}</b></div><div><small>Last updated</small><b>{{ $branding->updated_at?->format('d M Y H:i') ?: '—' }}</b></div></div>
@endif
<div class="danger-zone"><form method="POST" action="{{ route('admin.branding.reset') }}" onsubmit="return confirm('Reset the website logo to the default UNIFCO logo?')">@csrf<button class="btn danger-btn" type="submit">Reset to Default Logo</button></form></div>
</section>
<section class="branding-card">
<h2>Where this logo is used</h2><p>The application references a single dynamic brand endpoint rather than hard-coded logo files.</p>
<div class="impact"><div><b>Authentication</b>Login hero, login card and secure-access footer branding.</div><div><b>Application Shell</b>Main sidebar branding shown throughout authenticated modules.</div><div><b>Public Website</b>Header and other public-facing brand placements using the shared logo endpoint.</div><div><b>Future Sections</b>Any new section using <code>route('brand.logo')</code> automatically inherits the selected logo.</div></div>
</section>
</div>
@endsection
