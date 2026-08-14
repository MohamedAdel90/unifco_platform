@extends('layouts.app')
@section('title',$title.' · UNIFCO')
@section('heading',$title)
@section('content')
<div class="card"><div style="display:flex;justify-content:space-between"><div><h2 style="margin-top:0">{{ $title }} workspace</h2><p>Tenant-scoped operational records.</p></div><span class="pill">{{ $records->total() }} records</span></div>
<table class="table"><thead><tr><th>ID</th><th>{{ $key }}</th><th>{{ $secondary }}</th><th>Status</th></tr></thead><tbody>@forelse($records as $record)<tr><td>{{ $record->id }}</td><td>{{ data_get($record,$key) }}</td><td>{{ data_get($record,$secondary) }}</td><td><span class="pill">{{ $record->status }}</span></td></tr>@empty<tr><td colspan="4">No records yet.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $records->links() }}</div></div>
@endsection
