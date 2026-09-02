@extends('layouts.app')
@section('title','Temporary Files | UNIFCO Platform')
@section('heading','Temporary Files')
@section('content')
<style>
.temp-layout{display:grid;grid-template-columns:minmax(300px,.72fr) minmax(0,1.28fr);gap:16px;align-items:start}.temp-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:20px}.temp-card h2{font-size:17px;margin:0 0 6px;color:#17243c}.temp-card p{font-size:11px;color:#728096;line-height:1.75}.upload-zone{margin-top:16px;padding:22px;border:1.5px dashed #b9c5d5;border-radius:12px;background:#f8fafc;display:grid;gap:12px}.upload-zone input{background:#fff;width:100%}.security-note{margin-top:14px;padding:12px;border-radius:9px;background:#fff8e8;color:#705313;font-size:10px;line-height:1.65}.file-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:13px;margin-top:15px}.file-card{border:1px solid #e1e7ef;border-radius:12px;overflow:hidden;background:#fff}.file-preview{height:175px;background:#f3f6fa;display:grid;place-items:center;border-bottom:1px solid #e7ebf1}.file-preview img{width:100%;height:100%;object-fit:contain}.pdf-preview{font-size:46px;color:#ce122d;font-weight:900}.file-info{padding:13px}.file-info strong{display:block;font-size:12px;color:#1e315b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.file-meta{font-size:9px;color:#7b899d;margin:5px 0 10px;line-height:1.6}.share-row{display:grid;grid-template-columns:1fr auto;gap:6px}.share-row input{min-width:0;font-size:9px;background:#f8fafc}.copy-btn{border:0;background:#eef3fa;color:#1e315b;border-radius:7px;padding:8px 10px;font-weight:800;cursor:pointer}.file-actions{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:10px}.file-actions a{font-size:10px;font-weight:800;color:#1e315b}.delete-btn{border:0;background:#9f1f1f;color:#fff;border-radius:7px;padding:8px 10px;font-size:10px;font-weight:800;cursor:pointer}.empty-temp{padding:44px 18px;text-align:center;border:1px dashed #c7d0dd;border-radius:12px;color:#748198;margin-top:15px}.temp-count{display:inline-flex;padding:4px 8px;border-radius:999px;background:#eef3fa;color:#1e315b;font-size:10px;font-weight:800}@media(max-width:920px){.temp-layout{grid-template-columns:1fr}}@media(max-width:520px){.file-grid{grid-template-columns:1fr}}
.xlsx-preview{color:#1b7f48;font-size:40px;font-weight:900;letter-spacing:.02em}
</style>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<div class="temp-layout">
<section class="temp-card">
<h2>Upload a temporary reference</h2>
<p>Use this space for design screenshots, PDFs, and XLSX spreadsheets you want to share temporarily. After upload, copy the public link and paste it into this conversation.</p>
<form class="upload-zone" method="POST" action="{{ route('admin.temporary-files.store') }}" enctype="multipart/form-data">@csrf
<label><strong>Choose file</strong><input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.xlsx" required></label>
<button class="btn" type="submit">Upload Temporary File</button>
</form>
<p>Accepted: JPG, PNG, WEBP, GIF, PDF, XLSX · Maximum size: 10 MB.</p>
<div class="security-note"><strong>Temporary public access</strong><br>Anyone with the unguessable link can view the file until you delete it. Do not upload passwords, identity documents, or confidential customer data.</div>
</section>
<section class="temp-card">
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><div><h2>Uploaded files</h2><p style="margin:0">Copy a link for sharing, then delete the file when the design work is complete.</p></div><span class="temp-count">{{ $files->count() }} files</span></div>
@if($files->isEmpty())
<div class="empty-temp">No temporary files uploaded yet.</div>
@else
<div class="file-grid">
@foreach($files as $file)
<article class="file-card">
<a class="file-preview" href="{{ $file['public_url'] }}" target="_blank" rel="noopener">
@if($file['is_image'])<img src="{{ $file['public_url'] }}" alt="{{ $file['original_name'] }}">@elseif($file['is_spreadsheet'])<span class="xlsx-preview">XLSX</span>@else<span class="pdf-preview">PDF</span>@endif
</a>
<div class="file-info">
<strong title="{{ $file['original_name'] }}">{{ $file['original_name'] }}</strong>
<div class="file-meta">{{ number_format(($file['size'] ?? 0) / 1024, 1) }} KB · {{ \Illuminate\Support\Carbon::parse($file['uploaded_at'])->format('d M Y H:i') }}</div>
<div class="share-row"><input id="link-{{ $file['token'] }}" value="{{ $file['public_url'] }}" readonly><button class="copy-btn" type="button" data-copy="link-{{ $file['token'] }}">Copy</button></div>
<div class="file-actions"><a href="{{ $file['public_url'] }}" target="_blank" rel="noopener">Open / download ↗</a><form method="POST" action="{{ route('admin.temporary-files.destroy',$file['token']) }}" onsubmit="return confirm('Delete this temporary file? Its public link will stop working immediately.')">@csrf @method('DELETE')<button class="delete-btn" type="submit">Delete</button></form></div>
</div>
</article>
@endforeach
</div>
@endif
</section>
</div>
<script>document.querySelectorAll('[data-copy]').forEach(function(button){button.addEventListener('click',function(){var input=document.getElementById(this.dataset.copy);navigator.clipboard.writeText(input.value).then(()=>{var old=this.textContent;this.textContent='Copied';setTimeout(()=>this.textContent=old,1400)});});});</script>
@endsection
