@extends('layouts.app')
@section('title','Scope Enforcement Audit | UNIFCO Platform')
@section('heading','Scope Enforcement Audit')
@section('content')
@include('admin.system.operations-styles')
@php($modules=[
['CRM / Customers','Customer · Site · Contract · Own records'],
['Maintenance / Field Service','Customer · Site · Asset · Project · Assigned records'],
['Assets / EAM','Customer · Site · Asset · Project'],
['Inventory / Warehouses','Company · Branch · Project · Site'],
['Procurement','Company · Branch · Project'],
['Finance','Company · Customer · Project · Contract'],
['Projects','Company · Project · Customer'],
['Manufacturing','Company · Project'],
['HR / People','Company · Department · Own records'],
['Reporting / Analytics','Company · Department · Project · Customer · Site'],
['Platform / Workflow','Company · Project · Customer · Site · Own records']
])
<section class="ops-hero"><h1>Scope Enforcement Audit</h1><p>Platform-wide closure matrix for data visibility. Each module must pass list, detail, export and analytics scope tests before being marked complete.</p></section>
<section class="ops-card"><table class="ops-table"><thead><tr><th>Module</th><th>Required scope dimensions</th><th>Acceptance gate</th></tr></thead><tbody>@foreach($modules as [$module,$scope])<tr><td><b>{{ $module }}</b></td><td>{{ $scope }}</td><td>List · Detail · Export · Analytics</td></tr>@endforeach</tbody></table></section>
<section class="ops-card" style="margin-top:12px"><h3 style="margin-top:0">Closure rule</h3><p class="muted">GLOBAL may see all tenant data. Restricted users may see only resources matching their assigned scope. Structured-role users without an applicable scope must receive no scoped operational rows. Customer Portal restrictions are additional and can never be widened by internal scope assignment.</p></section>
@endsection
