@extends('layouts.app')
@section('title','Finance Core')
@section('heading','Finance Core')
@section('content')
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="grid">
  <div class="card"><div class="muted">Chart Accounts</div><div class="metric">{{ $accounts->count() }}</div></div>
  <div class="card"><div class="muted">Open Periods</div><div class="metric">{{ $periods->where('status','OPEN')->count() }}</div></div>
  <div class="card"><div class="muted">Open AP/AR</div><div class="metric">{{ number_format($documents->sum('open_amount'),2) }}</div></div>
  <div class="card"><div class="muted">Posted Journals</div><div class="metric">{{ \App\Models\Journal::where('status','POSTED')->count() }}</div></div>
</div>

<h2>Chart of Accounts</h2>
<form class="card form-grid" method="POST" action="{{ route('finance.core.accounts.store') }}">@csrf
<label>Code<input name="code" required></label><label>Name<input name="name" required></label>
<label>Type<select name="type">@foreach(['ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE'] as $v)<option>{{ $v }}</option>@endforeach</select></label>
<label>Normal Balance<select name="normal_balance"><option>DEBIT</option><option>CREDIT</option></select></label><div><button class="btn">Add Account</button></div>
</form>
<table class="table"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Normal</th><th>Status</th></tr></thead><tbody>@foreach($accounts as $a)<tr><td>{{ $a->code }}</td><td>{{ $a->name }}</td><td>{{ $a->type }}</td><td>{{ $a->normal_balance }}</td><td>{{ $a->status }}</td></tr>@endforeach</tbody></table>

<h2>Fiscal Periods</h2>
<form class="card form-grid" method="POST" action="{{ route('finance.core.periods.store') }}">@csrf
<label>Code<input name="code" required></label><label>Start<input type="date" name="starts_on" required></label><label>End<input type="date" name="ends_on" required></label><div><button class="btn">Open Period</button></div>
</form>
<table class="table"><thead><tr><th>Code</th><th>Start</th><th>End</th><th>Status</th><th>Action</th></tr></thead><tbody>@foreach($periods as $p)<tr><td>{{ $p->code }}</td><td>{{ $p->starts_on?->toDateString() }}</td><td>{{ $p->ends_on?->toDateString() }}</td><td>{{ $p->status }}</td><td>@if($p->status==='OPEN')<form method="POST" action="{{ route('finance.core.periods.close',$p) }}">@csrf<button>Close</button></form>@else<form method="POST" action="{{ route('finance.core.periods.reopen',$p) }}">@csrf<button>Reopen</button></form>@endif</td></tr>@endforeach</tbody></table>

<h2>AP / AR Documents</h2>
<form class="card form-grid" method="POST" action="{{ route('finance.core.documents.store') }}">@csrf
<label>No<input name="document_no" required></label><label>Type<select name="document_type"><option>AP_INVOICE</option><option>AR_INVOICE</option></select></label>
<label>Counterparty<input name="counterparty_name" required></label><label>Date<input type="date" name="document_date" required></label><label>Due<input type="date" name="due_date"></label>
<label>Currency<input name="currency" value="USD" maxlength="3" required></label><label>Amount<input type="number" step="0.01" min="0.01" name="amount" required></label>
<label>Control Account<input name="control_account_code" placeholder="AP or AR" required></label><label>Offset Account<input name="offset_account_code" placeholder="EXPENSE or REVENUE" required></label><div><button class="btn">Create Document</button></div>
</form>
<table class="table"><thead><tr><th>No</th><th>Type</th><th>Counterparty</th><th>Amount</th><th>Open</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@foreach($documents as $d)<tr><td>{{ $d->document_no }}</td><td>{{ $d->document_type }}</td><td>{{ $d->counterparty_name }}</td><td>{{ $d->currency }} {{ number_format($d->amount,2) }}</td><td>{{ number_format($d->open_amount,2) }}</td><td>{{ $d->status }}</td><td>
@if($d->status==='DRAFT')<form method="POST" action="{{ route('finance.core.documents.post',$d) }}">@csrf<button>Post</button></form>@endif
@if(in_array($d->status,['POSTED']) && $d->open_amount>0)<form class="row" method="POST" action="{{ route('finance.core.documents.pay',$d) }}">@csrf<input name="payment_no" placeholder="Payment no" required><input type="date" name="payment_date" required><input type="number" step="0.01" name="amount" max="{{ $d->open_amount }}" required><input name="cash_account_code" placeholder="Cash/Bank acct" required><button>Settle</button></form>@endif
</td></tr>@endforeach
</tbody></table>

<h2>Trial Balance</h2>
<table class="table"><thead><tr><th>Account</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead><tbody>@foreach($trial as $code=>$v)<tr><td>{{ $code }}</td><td>{{ number_format($v['debit'],2) }}</td><td>{{ number_format($v['credit'],2) }}</td><td>{{ number_format($v['balance'],2) }}</td></tr>@endforeach</tbody></table>
@endsection
