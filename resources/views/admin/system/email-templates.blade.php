@extends('layouts.app')
@section('title','Email Templates | UNIFCO Platform')
@section('heading','Email Templates')
@section('content')
@include('admin.system.operations-styles')
<section class="ops-hero"><h1>Email Templates</h1><p>Bilingual notification content. Template configuration does not grant authority to send business decisions.</p></section>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<section class="ops-card"><form method="POST" action="{{ route('admin.system.email-templates.store') }}">@csrf<div class="ops-filter"><label>Template Code<input name="code" required></label><label>Subject AR<input name="subject_ar"></label><label>Subject EN<input name="subject_en" required></label></div><div class="ops-filter" style="margin-top:10px"><label>Body AR<textarea name="body_ar" rows="8"></textarea></label><label>Body EN<textarea name="body_en" rows="8" required></textarea></label><button class="btn">Save Template</button></div></form></section>
<section class="ops-card"><table class="ops-table"><thead><tr><th>Code</th><th>Arabic Subject</th><th>English Subject</th><th>Status</th></tr></thead><tbody>@forelse($templates as $template)<tr><td>{{ $template->code }}</td><td>{{ $template->subject_ar ?: '—' }}</td><td>{{ $template->subject_en }}</td><td>{{ $template->is_active?'ACTIVE':'INACTIVE' }}</td></tr>@empty<tr><td colspan="4" class="ops-empty">No email templates.</td></tr>@endforelse</tbody></table></section>
@endsection
