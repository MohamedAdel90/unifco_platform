@extends('layouts.app')
@section('content')
<h1>Create Journal</h1>
@if($errors->any())<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="post" action="{{ route('finance.journals.store') }}">@csrf
<label>Journal No <input name="journal_no" required></label><br>
<label>Date <input type="date" name="journal_date" required></label><br>
<label>Description <input name="description"></label><br>
<h3>Debit line</h3><input name="lines[0][account_code]" placeholder="Account" required><input name="lines[0][debit]" type="number" step="0.01" min="0" value="0"><input name="lines[0][credit]" type="number" step="0.01" min="0" value="0">
<h3>Credit line</h3><input name="lines[1][account_code]" placeholder="Account" required><input name="lines[1][debit]" type="number" step="0.01" min="0" value="0"><input name="lines[1][credit]" type="number" step="0.01" min="0" value="0">
<br><button>Create Draft</button></form>
@endsection
