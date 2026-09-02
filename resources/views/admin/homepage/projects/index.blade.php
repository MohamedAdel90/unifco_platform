@extends('layouts.app')
@section('title','Homepage Projects | UNIFCO Platform')
@section('heading','Homepage CMS — Projects')
@section('content')
<style>
.proj-table{width:100%;margin-top:14px;border-collapse:separate;border-spacing:0;border:1px solid #e3e8ef;border-radius:12px;overflow:hidden;font-size:12px}
.proj-table th{background:#f2f6fa;padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:#728096;font-weight:700;border-bottom:1px solid #e3e8ef}
.proj-table td{padding:10px 12px;border-bottom:1px solid #f0f3f7;vertical-align:middle}
.proj-table tr:last-child td{border-bottom:none}
.proj-table tr:hover td{background:#fafbfc}
.proj-title{font-weight:700;color:#17243c}.proj-sub{font-size:11px;color:#728096}
.proj-img{width:48px;height:36px;object-fit:cover;border-radius:6px;background:#f2f6fa}
.badge{display:inline-block;font-size:10px;padding:3px 9px;border-radius:16px;font-weight:700}.badge.on{background:#e6f7ee;color:#177a3d}.badge.off{background:#fce8e8;color:#9f1f1f}
</style>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
<div style="display:flex;justify-content:space-between;align-items:center">
  <p style="font-size:12px;color:#728096;margin:0">{{ $projects->count() }} project(s) in CMS</p>
  <a class="btn" href="{{ route('admin.homepage.projects.create') }}">+ New Project</a>
</div>
<table class="proj-table">
<thead><tr><th></th><th>Project</th><th>Owner</th><th>Location</th><th>Scope</th><th>Years</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
@foreach($projects as $p)
<tr>
  <td>@if($p->image)<img class="proj-img" src="{{ $p->image }}" alt="">@endif</td>
  <td><span class="proj-title">{{ $p->title_en }}</span><br><span class="proj-sub">{{ $p->title_ar }}</span></td>
  <td>{{ $p->owner_en }}</td>
  <td>{{ $p->location_en }}</td>
  <td><span class="proj-sub">{{ $p->scope_en }}</span></td>
  <td><span class="proj-sub">{{ $p->year }}</span></td>
  <td><span class="badge {{ $p->is_active?'on':'off' }}">{{ $p->is_active?'Active':'Off' }}</span></td>
  <td style="white-space:nowrap">
    <a class="btn" style="font-size:10px;padding:4px 10px" href="{{ route('admin.homepage.projects.edit',$p) }}">Edit</a>
    <form method="POST" action="{{ route('admin.homepage.projects.toggle',$p) }}" style="display:inline">@csrf<button class="btn" style="font-size:10px;padding:4px 10px;background:#f2f6fa;color:#374151" type="submit">{{ $p->is_active?'Disable':'Enable' }}</button></form>
    <form method="POST" action="{{ route('admin.homepage.projects.destroy',$p) }}" style="display:inline" onsubmit="return confirm('Delete this project?')">@csrf @method('DELETE')<button class="btn" style="font-size:10px;padding:4px 10px;background:#fce8e8;color:#9f1f1f" type="submit">Delete</button></form>
  </td>
</tr>
@endforeach
</tbody>
</table>
@endsection
