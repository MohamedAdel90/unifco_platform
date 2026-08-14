@extends('layouts.app')
@section('content')
<h1>{{ $asset->exists?'Edit':'Register' }} Asset</h1>
@if($errors->any())<div class="error">{{ implode(' | ',$errors->all()) }}</div>@endif
<form method="POST" action="{{ $asset->exists?route('eam.assets.update',$asset):route('eam.assets.store') }}">@csrf @if($asset->exists)@method('PUT')@endif
<label>Asset Code <input name="asset_code" value="{{ old('asset_code',$asset->asset_code) }}" required></label>
<label>Name <input name="name" value="{{ old('name',$asset->name) }}" required></label>
<label>Acquisition Cost <input type="number" step="0.01" min="0" name="acquisition_cost" value="{{ old('acquisition_cost',$asset->acquisition_cost) }}" required></label>
<label>Commission Date <input type="date" name="commission_date" value="{{ old('commission_date',optional($asset->commission_date)->format('Y-m-d')) }}"></label>
<button>Save</button></form>
@endsection
