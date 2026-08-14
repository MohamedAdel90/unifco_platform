@extends('layouts.app')
@section('heading','Executive Reporting')
@section('content')
<div class="page-head"><div><h1>Executive Report</h1><p class="muted">Live tenant-scoped operational snapshot.</p></div></div>
<div class="grid">@foreach($metrics as $label=>$value)<div class="card"><div class="muted">{{ $label }}</div><div class="metric">{{ $value }}</div></div>@endforeach</div>
<div class="card" style="margin-top:18px"><h2>Posted Finance Control</h2><div class="grid"><div><div class="muted">Total Debits</div><div class="metric">{{ number_format((float)$finance->debit,2) }}</div></div><div><div class="muted">Total Credits</div><div class="metric">{{ number_format((float)$finance->credit,2) }}</div></div><div><div class="muted">Variance</div><div class="metric">{{ number_format((float)$finance->debit-(float)$finance->credit,2) }}</div></div></div></div>
@endsection
