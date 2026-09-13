@extends('layouts.app')
@section('title','Invitations | UNIFCO Platform')
@section('heading','Invitations')
@section('content')
@include('admin.system.operations-styles')
<section class="ops-hero"><h1>Invitations</h1><p>Secure, expiring, single-use account setup and password reset invitations. Credentials are never displayed.</p></section>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
<section class="ops-card"><form class="ops-filter" method="GET"><label>Search<input name="q" value="{{ request('q') }}" placeholder="Name or email"></label><label>Status<select name="status"><option value="">All statuses</option>@foreach(['PENDING','ACCEPTED','EXPIRED','REVOKED'] as $value)<option @selected(request('status')===$value)>{{ $value }}</option>@endforeach</select></label><button class="btn">Filter</button></form>
<table class="ops-table"><thead><tr><th>User</th><th>Email</th><th>Status</th><th>Expiry</th><th>Accepted / Revoked</th><th>Actions</th></tr></thead><tbody>@forelse($invitations as $invitation)<tr><td>{{ $invitation->user?->name ?: 'Unlinked' }}</td><td>{{ $invitation->email }}</td><td><span class="pill">{{ $invitation->status }}</span></td><td>{{ $invitation->expires_at?->format('d M Y H:i') }}</td><td>{{ $invitation->accepted_at?->format('d M Y H:i') ?: ($invitation->revoked_at?->format('d M Y H:i') ?: '—') }}</td><td><span style="display:flex;gap:5px">@if($invitation->status==='PENDING')<form method="POST" action="{{ route('admin.access-control.invitations.revoke',$invitation) }}">@csrf<button class="btn danger">Revoke</button></form>@endif<form method="POST" action="{{ route('admin.access-control.invitations.resend',$invitation) }}">@csrf<button class="btn secondary">Resend</button></form></span></td></tr>@empty<tr><td colspan="6" class="ops-empty">No invitations.</td></tr>@endforelse</tbody></table>{{ $invitations->links() }}</section>
@endsection
