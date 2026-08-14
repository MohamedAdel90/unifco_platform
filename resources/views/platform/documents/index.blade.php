@extends('layouts.app')
@section('heading','Documents')
@section('content')
<div class="page-head"><div><h1>Document Center</h1><p class="muted">Tenant-isolated operational files stored outside the public web root.</p></div></div>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<div class="card"><form method="POST" enctype="multipart/form-data" action="{{ route('platform.documents.store') }}">@csrf<div class="form-grid"><label>Document No<input name="document_no" required></label><label>Title<input name="title" required></label><label>File<input type="file" name="file" required></label></div><button class="btn">Upload</button></form></div>
<table class="table"><thead><tr><th>No</th><th>Title</th><th>File</th><th>Size</th><th>Uploaded</th><th></th></tr></thead><tbody>@foreach($documents as $d)<tr><td>{{ $d->document_no }}</td><td>{{ $d->title }}</td><td>{{ $d->original_name }}</td><td>{{ number_format($d->size_bytes/1024,1) }} KB</td><td>{{ $d->created_at }}</td><td><a href="{{ route('platform.documents.download',$d) }}">Download</a></td></tr>@endforeach</tbody></table>{{ $documents->links() }}
@endsection
