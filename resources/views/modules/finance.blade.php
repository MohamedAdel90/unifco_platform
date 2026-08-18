@extends('layouts.app')
@section('title','Finance · UNIFCO')
@section('heading','Finance')
@section('content')
<style>
.finance-shell{display:grid;gap:18px}.finance-hero{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:24px;border-radius:18px;background:linear-gradient(135deg,#132137 0%,#1e315b 100%);color:#fff;overflow:hidden;position:relative}.finance-hero:after{content:"";position:absolute;width:210px;height:210px;border:36px solid rgba(255,255,255,.06);border-radius:50%;left:-70px;top:-80px}.finance-hero h1{margin:0 0 6px;font-size:28px}.finance-hero p{margin:0;color:#d9e2ef}.finance-hero-actions{display:flex;gap:10px;flex-wrap:wrap;position:relative;z-index:1}.finance-hero .btn{background:#fff;color:#1e315b}.finance-hero .btn.primary{background:#ce122d;color:#fff}.finance-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.finance-metric{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:18px;min-width:0;box-shadow:0 4px 14px rgba(19,33,55,.05)}.finance-metric .label{font-size:13px;color:#68758a;margin-bottom:8px}.finance-metric .value{font-size:27px;font-weight:800;color:#1e315b;line-height:1.15;overflow-wrap:anywhere}.finance-metric .hint{font-size:12px;color:#8a95a6;margin-top:7px}.finance-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:18px}.finance-card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:18px;box-shadow:0 4px 14px rgba(19,33,55,.04);min-width:0}.finance-card h2{margin:0;color:#132137;font-size:19px}.finance-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.finance-links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.finance-link{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:13px 14px;border:1px solid #e6eaf0;border-radius:11px;text-decoration:none;color:#1e315b;background:#fbfcfe;font-weight:700}.finance-link:hover{border-color:#c7d0df;background:#f4f7fb}.finance-link small{display:block;color:#7b8798;font-weight:400;margin-top:3px}.finance-badge{min-width:34px;height:34px;border-radius:9px;background:#eef3fa;display:grid;place-items:center;color:#1e315b;font-size:12px}.finance-table-wrap{overflow-x:auto;border:1px solid #e7ebf1;border-radius:11px}.finance-table{width:100%;border-collapse:collapse;min-width:640px;margin:0}.finance-table th,.finance-table td{padding:11px 12px;border-bottom:1px solid #edf0f5;text-align:left;font-size:13px}.finance-table th{background:#f6f8fb;color:#536075;font-weight:700}.finance-table tr:last-child td{border-bottom:0}.status{display:inline-block;padding:4px 8px;border-radius:999px;background:#eef3fa;color:#1e315b;font-size:12px;font-weight:700}.status.posted{background:#eaf8ef;color:#207a43}.status.draft{background:#fff6e5;color:#8a5a00}.finance-empty{padding:24px;text-align:center;color:#7a8798}.finance-mobile-actions{display:none}.finance-kpi-note{display:flex;align-items:flex-start;gap:10px;padding:12px;border-radius:10px;background:#f8fafc;color:#536075;font-size:13px}.finance-kpi-dot{width:9px;height:9px;border-radius:50%;background:#ce122d;margin-top:5px;flex:0 0 auto}@media(max-width:1100px){.finance-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.finance-grid{grid-template-columns:1fr}}@media(max-width:700px){.finance-shell{gap:14px}.finance-hero{padding:18px;align-items:flex-start;flex-direction:column}.finance-hero h1{font-size:23px}.finance-hero-actions{width:100%}.finance-hero-actions .btn{flex:1;text-align:center;min-width:130px}.finance-metrics{grid-template-columns:1fr 1fr;gap:10px}.finance-metric{padding:14px}.finance-metric .value{font-size:22px}.finance-links{grid-template-columns:1fr}.finance-card{padding:14px}.finance-card-head{align-items:flex-start}.finance-table th,.finance-table td{padding:9px}.finance-desktop-actions{display:none}.finance-mobile-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}}@media(max-width:440px){.finance-metrics{grid-template-columns:1fr}.finance-hero-actions .btn{width:100%;flex:1 0 100%}}
</style>
<div class="finance-shell">
  <section class="finance-hero">
    <div><div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#bfcbe0;margin-bottom:7px">Finance workspace</div><h1>Financial control at a glance</h1><p>Journals, receivables, payables and core finance operations in one responsive workspace.</p></div>
    <div class="finance-hero-actions"><a class="btn primary" href="{{ route('finance.journals.create') }}">New Journal</a><a class="btn" href="{{ route('finance.core.index') }}">Finance Core</a></div>
  </section>

  <section class="finance-metrics">
    <div class="finance-metric"><div class="label">Total Journals</div><div class="value">{{ number_format($financeMetrics['journals']) }}</div><div class="hint">All tenant journals</div></div>
    <div class="finance-metric"><div class="label">Posted Journals</div><div class="value">{{ number_format($financeMetrics['posted_journals']) }}</div><div class="hint">Successfully posted</div></div>
    <div class="finance-metric"><div class="label">Draft Journals</div><div class="value">{{ number_format($financeMetrics['draft_journals']) }}</div><div class="hint">Awaiting completion</div></div>
    <div class="finance-metric"><div class="label">This Month</div><div class="value">{{ number_format($financeMetrics['this_month']) }}</div><div class="hint">Current-month journals</div></div>
    <div class="finance-metric"><div class="label">Open Receivables</div><div class="value">{{ number_format($financeMetrics['open_receivables'],2) }}</div><div class="hint">Outstanding AR balance</div></div>
    <div class="finance-metric"><div class="label">Open Payables</div><div class="value">{{ number_format($financeMetrics['open_payables'],2) }}</div><div class="hint">Outstanding AP balance</div></div>
  </section>

  <section class="finance-grid">
    <div class="finance-card">
      <div class="finance-card-head"><div><h2>Recent journals</h2><div class="muted" style="font-size:13px;margin-top:4px">Latest financial entries and posting status.</div></div><a class="btn secondary finance-desktop-actions" href="{{ route('finance.journals.index') }}">View all</a></div>
      <div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>Journal</th><th>Date</th><th>Description</th><th>Status</th></tr></thead><tbody>@forelse($records as $record)<tr><td><strong>{{ $record->journal_no }}</strong></td><td>{{ optional($record->journal_date)->format('Y-m-d') }}</td><td>{{ $record->description ?: '—' }}</td><td><span class="status {{ strtolower($record->status) }}">{{ $record->status }}</span></td></tr>@empty<tr><td colspan="4"><div class="finance-empty">No finance journals yet.</div></td></tr>@endforelse</tbody></table></div>
      <div class="finance-mobile-actions"><a class="btn secondary" href="{{ route('finance.journals.index') }}">View all journals</a></div>
      <div style="margin-top:14px">{{ $records->links() }}</div>
    </div>

    <div class="finance-card">
      <div class="finance-card-head"><div><h2>Quick actions</h2><div class="muted" style="font-size:13px;margin-top:4px">Common finance tasks.</div></div></div>
      <div class="finance-links">
        <a class="finance-link" href="{{ route('finance.journals.create') }}"><span>New Journal<small>Create a balanced journal entry</small></span><span class="finance-badge">JE</span></a>
        <a class="finance-link" href="{{ route('finance.journals.index') }}"><span>Journal Register<small>Review and post journals</small></span><span class="finance-badge">JR</span></a>
        <a class="finance-link" href="{{ route('finance.core.index') }}"><span>Finance Core<small>Accounts, periods and documents</small></span><span class="finance-badge">FC</span></a>
        <a class="finance-link" href="{{ route('reporting.executive') }}"><span>Executive Reporting<small>Financial and operational overview</small></span><span class="finance-badge">ER</span></a>
      </div>
      <div class="finance-kpi-note" style="margin-top:14px"><span class="finance-kpi-dot"></span><span>Use Finance Core for AR/AP documents, payments, accounting periods and general ledger controls.</span></div>
    </div>
  </section>
</div>
@endsection
