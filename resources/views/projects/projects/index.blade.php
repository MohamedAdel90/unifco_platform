@extends('layouts.app')
@section('content')
<h1>Projects</h1><p><a href="{{ route('projects.projects.create') }}">New Project</a></p>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
<table><thead><tr><th>No</th><th>Name</th><th>Budget</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($projects as $p)<tr><td>{{ $p->project_no }}</td><td>{{ $p->name }}</td><td>{{ $p->budget }}</td><td>{{ $p->status }}</td><td><a href="{{ route('projects.projects.edit',$p) }}">Edit</a> @if($p->status==='DRAFT')<form style="display:inline" method="POST" action="{{ route('projects.projects.activate',$p) }}">@csrf<button>Activate</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $projects->links() }}
@endsection
