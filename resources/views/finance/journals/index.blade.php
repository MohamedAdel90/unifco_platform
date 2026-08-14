@extends('layouts.app')
@section('content')
<h1>Finance — Journals</h1>
@if(session('status')) <p>{{ session('status') }}</p> @endif
<p><a href="{{ route('finance.journals.create') }}">Create journal</a></p>
<table><thead><tr><th>No.</th><th>Date</th><th>Status</th><th>Debit</th><th>Credit</th><th></th></tr></thead><tbody>
@foreach($journals as $journal)
<tr><td>{{ $journal->journal_no }}</td><td>{{ $journal->journal_date?->format('Y-m-d') }}</td><td>{{ $journal->status }}</td><td>{{ number_format($journal->lines->sum('debit'),2) }}</td><td>{{ number_format($journal->lines->sum('credit'),2) }}</td><td>@if($journal->status==='DRAFT')<form method="post" action="{{ route('finance.journals.post',$journal) }}">@csrf<button>Post</button></form>@endif</td></tr>
@endforeach
</tbody></table>{{ $journals->links() }}
@endsection
