@extends('layouts.app')
@section('heading','Notifications')
@section('content')
<div class="page-head"><div><h1>Notifications</h1><p class="muted">Your tenant-scoped operational alerts.</p></div><form method="POST" action="{{ route('platform.notifications.read-all') }}">@csrf<button class="btn">Mark all read</button></form></div>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
<div class="stack">@forelse($notifications as $n)<div class="card"><div class="row"><div><strong>{{ $n->title }}</strong><div class="muted">{{ $n->message }}</div><small>{{ $n->created_at }}</small></div><div>@if(!$n->read_at)<form method="POST" action="{{ route('platform.notifications.read',$n) }}">@csrf<button class="btn secondary">Mark read</button></form>@else<span class="pill">Read</span>@endif</div></div></div>@empty<div class="card">No notifications.</div>@endforelse</div>
{{ $notifications->links() }}
@endsection
