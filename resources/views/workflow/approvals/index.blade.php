@extends('layouts.app')
@section('content')
<h1>Approval Inbox</h1>
@if(session('status')) <p class="notice">{{ session('status') }}</p> @endif
<table><thead><tr><th>ID</th><th>Entity</th><th>Action</th><th>Status</th><th>Requested by</th><th>Decision</th></tr></thead><tbody>
@foreach($approvals as $approval)
<tr><td>{{ $approval->id }}</td><td>{{ class_basename($approval->entity_type) }} #{{ $approval->entity_id }}</td><td>{{ $approval->action }}</td><td>{{ $approval->status }}</td><td>{{ $approval->requested_by }}</td><td>
@if($approval->status==='PENDING')
<form method="POST" action="{{ route('workflow.approvals.decide',$approval) }}">@csrf
<select name="decision"><option>APPROVED</option><option>REJECTED</option></select><input name="note" placeholder="Decision note"><button>Submit</button>
</form>
@endif
</td></tr>
@endforeach
</tbody></table>{{ $approvals->links() }}
@endsection
