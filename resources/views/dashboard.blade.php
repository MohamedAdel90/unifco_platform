@extends('layouts.app')
@section('title','Dashboard · UNIFCO')
@section('heading','Platform Dashboard')
@section('content')
<div class="grid">@foreach($stats as $name=>$count)<div class="card"><div>{{ $name }}</div><div class="metric">{{ number_format($count) }}</div><small>Records in your tenant</small></div>@endforeach</div>
@endsection
