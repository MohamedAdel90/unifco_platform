<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>UNIFCO Customer 360 Search</title>
<style>
:root{font-family:Inter,"Segoe UI",Arial,sans-serif;--navy:#06275c;--blue:#1475d1;--red:#e20b24;--bg:#f3f6fa;--line:#dfe6ef;--muted:#6d7b90}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:#0a234f}.wrap{max-width:1120px;margin:auto;padding:28px}.top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}.top h1{font-size:24px;margin:0 0 5px}.top p{font-size:10px;color:var(--muted);margin:0}.back{background:var(--navy);color:#fff;text-decoration:none;padding:9px 12px;border-radius:8px;font-size:10px;font-weight:800}.search{display:flex;gap:8px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;margin-bottom:14px}.search input{flex:1;border:1px solid #ccd7e5;border-radius:8px;padding:10px 12px;font:inherit;font-size:11px}.search button{border:0;border-radius:8px;background:var(--navy);color:#fff;padding:0 18px;font-size:10px;font-weight:800}.card{background:#fff;border:1px solid var(--line);border-radius:12px}.row{display:grid;grid-template-columns:120px 170px 1fr 110px auto;gap:12px;align-items:center;padding:13px 15px;border-bottom:1px solid #edf1f5;text-decoration:none;color:inherit}.row:last-child{border-bottom:0}.type{font-size:8px;text-transform:uppercase;letter-spacing:.08em;color:var(--blue);font-weight:900}.ref{font-size:10px;font-weight:900}.title{font-size:10px}.status{font-size:8px;color:var(--muted);font-weight:800}.open{font-size:9px;color:var(--blue);font-weight:900}.empty{padding:35px;text-align:center;color:var(--muted);font-size:10px}.hint{font-size:9px;color:var(--muted);margin:0 0 12px}@media(max-width:720px){.wrap{padding:15px}.top{align-items:flex-start}.row{grid-template-columns:1fr auto}.type,.ref,.title{grid-column:1}.status,.open{grid-column:2}.search{display:grid}.search button{height:38px}}
</style>
</head>
<body><main class="wrap">
<div class="top"><div><h1>Customer 360 Search</h1><p>Search across your service requests, assets, work orders, invoices and contracts.</p></div><a class="back" href="{{ route('customer.portal') }}">Back to Dashboard</a></div>
<form class="search" method="GET" action="{{ route('customer.search') }}"><input name="q" value="{{ $q }}" autofocus placeholder="Request no, asset code, work order, invoice or contract"><button>Search</button></form>
@if(mb_strlen($q)<2)<p class="hint">Enter at least two characters to search the full customer account.</p>@else<p class="hint">{{ $results->count() }} matching records for “{{ $q }}”. Results are restricted to your customer account.</p>@endif
<section class="card">
@forelse($results as $result)<a class="row" href="{{ $result->url }}"><span class="type">{{ $result->type }}</span><span class="ref">{{ $result->reference }}</span><span class="title">{{ $result->title }}</span><span class="status">{{ str_replace('_',' ',$result->status ?: '—') }}</span><span class="open">Open →</span></a>@empty<div class="empty">@if(mb_strlen($q)>=2)No matching customer records found.@elseSearch your complete Customer 360 workspace from one place.@endif</div>@endforelse
</section>
</main></body></html>
