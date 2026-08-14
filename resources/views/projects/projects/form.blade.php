@extends('layouts.app')
@section('content')
<h1>{{ $project->exists?'Edit':'New' }} Project</h1>
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<form method="POST" action="{{ $project->exists?route('projects.projects.update',$project):route('projects.projects.store') }}">@csrf @if($project->exists)@method('PUT')@endif
<label>Project No <input name="project_no" value="{{ old('project_no',$project->project_no) }}" required></label>
<label>Name <input name="name" value="{{ old('name',$project->name) }}" required></label>
<label>Customer <select name="customer_id"><option value="">—</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id',$project->customer_id)==$c->id)>{{ $c->customer_code }} — {{ $c->name }}</option>@endforeach</select></label>
<label>Start <input type="date" name="planned_start" value="{{ old('planned_start',optional($project->planned_start)->format('Y-m-d')) }}"></label>
<label>Finish <input type="date" name="planned_finish" value="{{ old('planned_finish',optional($project->planned_finish)->format('Y-m-d')) }}"></label>
<label>Budget <input type="number" step="0.01" min="0" name="budget" value="{{ old('budget',$project->budget??0) }}" required></label><button>Save</button></form>
@endsection
