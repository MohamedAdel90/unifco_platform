@extends('layouts.app')
@section('title','Dashboard · UNIFCO')
@section('heading','Platform Dashboard')
@section('content')
<div class="grid">@foreach($stats as $name=>$count)<div class="card"><div>{{ $name }}</div><div class="metric">{{ number_format($count) }}</div><small>Records in your tenant</small></div>@endforeach</div>
<div class="card" style="margin-top:18px"><h2>Implementation baseline</h2><p>This Laravel Blade runtime implements the UNIFCO v2.0 nine-module structure with tenant-scoped data ownership. The next implementation waves add transactional workflows, approvals, accounting posting, inventory movements, integrations and release qualification.</p></div>
@endsection
