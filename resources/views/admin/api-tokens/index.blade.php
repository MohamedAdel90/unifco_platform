@extends('layouts.app')
@section('content')
<h1>API Tokens</h1>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
@if(session('new_api_token'))<div class="card"><strong>New token — copy now</strong><pre style="white-space:pre-wrap;word-break:break-all">{{ session('new_api_token') }}</pre></div>@endif
<form method="POST" action="{{ route('admin.api-tokens.store') }}" class="card">@csrf
<label>Name <input name="name" required></label>
<label>Abilities <input name="abilities" value="status,summary" placeholder="status,summary"></label>
<label>Expires in days <input type="number" name="expires_in_days" min="1" max="365" value="90"></label>
<button class="btn">Create token</button></form>
<table class="table"><thead><tr><th>Name</th><th>Abilities</th><th>Last used</th><th>Expires</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($tokens as $t)<tr><td>{{ $t->name }}</td><td>{{ implode(', ',$t->abilities ?? []) }}</td><td>{{ $t->last_used_at }}</td><td>{{ $t->expires_at }}</td><td>{{ $t->revoked_at?'REVOKED':'ACTIVE' }}</td><td>@if(!$t->revoked_at)<form method="POST" action="{{ route('admin.api-tokens.destroy',$t) }}">@csrf @method('DELETE')<button>Revoke</button></form>@endif</td></tr>@endforeach
</tbody></table>
@endsection
