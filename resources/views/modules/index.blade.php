@extends('layouts.app')
@section('title',$title.' · UNIFCO')
@section('heading',$title)
@section('content')
@if($module === 'finance')
<style>
.finance-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#132137 0%,#1e315b 72%,#263f74 100%);color:#fff;border-radius:18px;padding:26px;margin-bottom:18px;box-shadow:0 14px 34px rgba(19,33,55,.16)}
.finance-hero:after{content:"";position:absolute;width:250px;height:250px;border:42px solid rgba(255,255,255,.05);border-radius:50%;left:-90px;top:-130px}.finance-hero>*{position:relative;z-index:1}.finance-hero-row{display:flex;justify-content:space-between;align-items:center;gap:18px;flex-wrap:wrap}.finance-brand{display:flex;align-items:center;gap:16px}.finance-brand img{width:72px;height:72px;object-fit:contain;background:#fff;border-radius:14px;padding:7px}.finance-title{margin:0;font-size:28px}.finance-subtitle{margin:6px 0 0;color:#d7deeb;max-width:670px;line-height:1.6}.finance-actions{display:flex;gap:9px;flex-wrap:wrap}.finance-actions .btn{white-space:nowrap}.finance-actions .btn.primary-red{background:#ce122d}.finance-actions .btn.white{background:#fff;color:#1e315b}
.finance-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.finance-kpi{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:17px;min-width:0}.finance-kpi .label{font-size:12px;color:#68758a;text-transform:uppercase;letter-spacing:.05em;font-weight:700}.finance-kpi .value{font-size:28px;font-weight:800;color:#1e315b;margin-top:7px}.finance-kpi .hint{font-size:12px;color:#8a95a5;margin-top:4px}
.finance-links{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}.finance-link{display:block;background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:17px;text-decoration:none;color:#132137;transition:.18s ease;min-width:0}.finance-link:hover{transform:translateY(-2px);border-color:#1e315b;box-shadow:0 8px 20px rgba(19,33,55,.08)}.finance-link strong{display:block;color:#1e315b;margin-bottom:6px}.finance-link span{display:block;color:#68758a;font-size:13px;line-height:1.55}.finance-link.accent{border-top:3px solid #ce122d}.finance-link.navy{border-top:3px solid #1e315b}
.finance-table-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:18px}.finance-table-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px}.finance-table-head h2{margin:0;color:#132137;font-size:20px}.finance-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid #edf0f5;border-radius:11px}.finance-table{min-width:720px;margin:0}.finance-table td,.finance-table th{white-space:nowrap}.journal-main{font-weight:700;color:#1e315b}.journal-date{color:#68758a}.status-posted{background:#eaf8ef;color:#237a45}.status-draft{background:#fff4e5;color:#9a5b00}.record-mobile{display:none}
@media(max-width:1050px){.finance-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.finance-links{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.finance-hero{padding:20px;border-radius:14px}.finance-brand{align-items:flex-start}.finance-brand img{width:58px;height:58px}.finance-title{font-size:23px}.finance-actions{width:100%}.finance-actions .btn{flex:1 1 145px;text-align:center}.finance-kpis{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.finance-kpi{padding:14px}.finance-kpi .value{font-size:23px}.finance-links{grid-template-columns:1fr}.finance-table-card{padding:13px}.finance-table-wrap{display:none}.record-mobile{display:grid;gap:10px}.journal-card{border:1px solid #e3e8ef;border-radius:11px;padding:14px;background:#fff}.journal-card-top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}.journal-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px;font-size:12px}.journal-meta div{background:#f7f9fc;border-radius:8px;padding:8px}.journal-meta span{display:block;color:#7a8799;margin-bottom:3px}.finance-table-head .pill{margin-inline-start:auto}}
@media(max-width:420px){.finance-kpis{grid-template-columns:1fr}.finance-brand{gap:10px}.finance-brand img{width:50px;height:50px}.finance-title{font-size:21px}.journal-meta{grid-template-columns:1fr}}
</style>

<section class="finance-hero">
    <div class="finance-hero-row">
        <div class="finance-brand">
            <img src="{{ route('brand.logo') }}" alt="UNIFCO">
            <div>
                <h1 class="finance-title">Finance Workspace</h1>
                <p class="finance-subtitle">A responsive financial operations hub for journals, accounting periods, receivables, payables and day-to-day posting activities.</p>
            </div>
        </div>
        <div class="finance-actions">
            <a class="btn primary-red" href="{{ route('finance.journals.create') }}">+ New Journal</a>
            <a class="btn white" href="{{ route('finance.core.index') }}">Finance Core</a>
        </div>
    </div>
</section>

<section class="finance-kpis" aria-label="Finance summary">
    <div class="finance-kpi"><div class="label">All Journals</div><div class="value">{{ number_format($financeSummary['total']) }}</div><div class="hint">Tenant-scoped records</div></div>
    <div class="finance-kpi"><div class="label">Posted</div><div class="value">{{ number_format($financeSummary['posted']) }}</div><div class="hint">Finalized accounting entries</div></div>
    <div class="finance-kpi"><div class="label">Draft</div><div class="value">{{ number_format($financeSummary['draft']) }}</div><div class="hint">Awaiting review or posting</div></div>
    <div class="finance-kpi"><div class="label">This Month</div><div class="value">{{ number_format($financeSummary['this_month']) }}</div><div class="hint">Journal activity in current month</div></div>
</section>

<section class="finance-links" aria-label="Finance shortcuts">
    <a class="finance-link accent" href="{{ route('finance.journals.index') }}"><strong>General Journal</strong><span>Review, create and post journal entries with controlled workflow.</span></a>
    <a class="finance-link navy" href="{{ route('finance.core.index') }}"><strong>Chart of Accounts & Periods</strong><span>Manage accounts, accounting periods and finance control documents.</span></a>
    <a class="finance-link navy" href="{{ route('reporting.executive') }}"><strong>Executive Reporting</strong><span>Open consolidated operational and financial management reporting.</span></a>
</section>

<section class="finance-table-card">
    <div class="finance-table-head">
        <div><h2>Recent Journals</h2><div class="muted" style="font-size:13px;margin-top:4px">Latest tenant-scoped financial activity</div></div>
        <span class="pill">{{ $records->total() }} records</span>
    </div>
    <div class="finance-table-wrap">
        <table class="finance-table"><thead><tr><th>ID</th><th>Journal No.</th><th>Journal Date</th><th>Status</th></tr></thead><tbody>
        @forelse($records as $record)
            <tr><td>{{ $record->id }}</td><td class="journal-main">{{ $record->journal_no }}</td><td class="journal-date">{{ optional($record->journal_date)->format('Y-m-d') }}</td><td><span class="pill {{ $record->status === 'POSTED' ? 'status-posted' : ($record->status === 'DRAFT' ? 'status-draft' : '') }}">{{ $record->status }}</span></td></tr>
        @empty<tr><td colspan="4">No journals yet. Create your first journal to begin financial posting.</td></tr>@endforelse
        </tbody></table>
    </div>
    <div class="record-mobile">
        @forelse($records as $record)
            <article class="journal-card">
                <div class="journal-card-top"><div><div class="journal-main">{{ $record->journal_no }}</div><div class="muted" style="font-size:12px">Journal #{{ $record->id }}</div></div><span class="pill {{ $record->status === 'POSTED' ? 'status-posted' : ($record->status === 'DRAFT' ? 'status-draft' : '') }}">{{ $record->status }}</span></div>
                <div class="journal-meta"><div><span>Journal date</span>{{ optional($record->journal_date)->format('Y-m-d') ?: '—' }}</div><div><span>Status</span>{{ $record->status }}</div></div>
            </article>
        @empty<div class="card">No journals yet. Create your first journal to begin financial posting.</div>@endforelse
    </div>
    <div style="margin-top:14px">{{ $records->links() }}</div>
</section>
@else
<div class="card"><div style="display:flex;justify-content:space-between"><div><h2 style="margin-top:0">{{ $title }} workspace</h2><p>Tenant-scoped operational records.</p></div><span class="pill">{{ $records->total() }} records</span></div>
<table class="table"><thead><tr><th>ID</th><th>{{ $key }}</th><th>{{ $secondary }}</th><th>Status</th></tr></thead><tbody>@forelse($records as $record)<tr><td>{{ $record->id }}</td><td>{{ data_get($record,$key) }}</td><td>{{ data_get($record,$secondary) }}</td><td><span class="pill">{{ $record->status }}</span></td></tr>@empty<tr><td colspan="4">No records yet.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $records->links() }}</div></div>
@endif
@endsection
