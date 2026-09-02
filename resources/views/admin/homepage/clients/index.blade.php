@extends('layouts.app')
@section('title','Homepage Clients | UNIFCO Platform')
@section('heading','Homepage CMS — Clients')
@section('content')
<style>
.cli-table{width:100%;margin-top:14px;border-collapse:separate;border-spacing:0;border:1px solid #e3e8ef;border-radius:12px;overflow:hidden;font-size:12px}
.cli-table th{background:#f2f6fa;padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:#728096;font-weight:700;border-bottom:1px solid #e3e8ef}
.cli-table td{padding:10px 12px;border-bottom:1px solid #f0f3f7;vertical-align:middle}
.cli-table tr:last-child td{border-bottom:none}
.cli-table tr:hover td{background:#fafbfc}
.cli-img{width:48px;height:48px;object-fit:contain;border-radius:8px;background:#f2f6fa;padding:4px}
.badge{display:inline-block;font-size:10px;padding:3px 9px;border-radius:16px;font-weight:700}.badge.on{background:#e6f7ee;color:#177a3d}.badge.off{background:#fce8e8;color:#9f1f1f}
</style>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
<div style="display:flex;justify-content:space-between;align-items:center">
  <p style="font-size:12px;color:#728096;margin:0">{{ $clients->count() }} client(s) in CMS</p>
  <a class="btn" href="{{ route('admin.homepage.clients.create') }}">+ New Client</a>
</div>
<table class="cli-table">
<thead><tr><th></th><th>Name (EN)</th><th>Name (AR)</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
@foreach($clients as $c)
<tr>
  <td>@if($c->image)<img class="cli-img" src="{{ $c->image }}" alt="">@endif</td>
  <td style="font-weight:700;color:#17243c">{{ $c->name_en }}</td>
  <td>{{ $c->name_ar }}</td>
  <td><span class="badge {{ $c->is_active?'on':'off' }}">{{ $c->is_active?'Active':'Off' }}</span></td>
  <td style="white-space:nowrap">
    <a class="btn" style="font-size:10px;padding:4px 10px" href="{{ route('admin.homepage.clients.edit',$c) }}">Edit</a>
    <form method="POST" action="{{ route('admin.homepage.clients.toggle',$c) }}" style="display:inline">@csrf<button class="btn" style="font-size:10px;padding:4px 10px;background:#f2f6fa;color:#374151" type="submit">{{ $c->is_active?'Disable':'Enable' }}</button></form>
    <form method="POST" action="{{ route('admin.homepage.clients.destroy',$c) }}" style="display:inline" onsubmit="return confirm('Delete this client?')">@csrf @method('DELETE')<button class="btn" style="font-size:10px;padding:4px 10px;background:#fce8e8;color:#9f1f1f" type="submit">Delete</button></form>
  </td>
</tr>
@endforeach
</tbody>
</table>
@endsection
