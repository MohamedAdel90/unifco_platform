@extends('layouts.app')
@section('content')
<h1>Enterprise Assets</h1><p><a href="{{ route('eam.assets.create') }}">Register Asset</a></p>
@if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<table><thead><tr><th>Code</th><th>Name</th><th>Cost</th><th>Commission</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@foreach($assets as $a)<tr><td>{{ $a->asset_code }}</td><td>{{ $a->name }}</td><td>{{ $a->acquisition_cost }}</td><td>{{ optional($a->commission_date)->format('Y-m-d') }}</td><td>{{ $a->status }}</td><td>@if($a->status==='REGISTERED')<a href="{{ route('eam.assets.edit',$a) }}">Edit</a> <form style="display:inline" method="POST" action="{{ route('eam.assets.capitalize',$a) }}">@csrf<button>Capitalize</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $assets->links() }}
@endsection
