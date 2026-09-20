@extends('layouts.app')
@section('title','Maintenance Request Workflow')
@section('content')
<style>
.wf-shell{display:grid;gap:18px}.wf-hero{background:linear-gradient(135deg,#071a33,#123c73);color:#fff;border-radius:18px;padding:22px}.wf-hero h1{margin:4px 0 6px}.wf-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.wf-card{background:#fff;border:1px solid #e4ebf3;border-radius:14px;padding:16px;box-shadow:0 7px 22px rgba(15,37,67,.05)}.wf-card b{display:block;color:#123c73;font-size:18px}.muted{font-size:12px;color:#718198}.pill{display:inline-flex;border-radius:999px;padding:5px 9px;background:#eef4fb;color:#26496e;font-size:11px;font-weight:800}.wf-form{display:grid;gap:10px}.wf-form input,.wf-form select,.wf-form textarea{width:100%;border:1px solid #d6e0eb;border-radius:9px;padding:10px}.wf-form button{border:0;border-radius:9px;padding:11px 15px;background:#123c73;color:#fff;font-weight:800;cursor:pointer}.wf-form button.alt{background:#a72c36}@media(max-width:850px){.wf-grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.wf-grid{grid-template-columns:1fr}}
</style>
<div class="wf-shell">
<section class="wf-hero"><div style="font-size:11px;font-weight:800;color:#a9c5e7">UNIFCO · REQUEST EXECUTION WORKSPACE</div><h1>{{ $serviceRequest->request_no }}</h1><div>{{ $serviceRequest->subject }}</div></section>
@if(session('status'))<div class="wf-card" style="border-left:4px solid #1f8a5b">{{ session('status') }}</div>@endif
<section class="wf-grid">
<div class="wf-card"><span class="muted">Workflow</span><b>{{ str_replace('_',' ',$serviceRequest->workflow_key ?: '-') }}</b></div>
<div class="wf-card"><span class="muted">Current Stage</span><b>{{ str_replace('_',' ',$serviceRequest->workflow_stage ?: '-') }}</b></div>
<div class="wf-card"><span class="muted">Department</span><b>{{ $serviceRequest->assigned_department ?: '-' }}</b></div>
<div class="wf-card"><span class="muted">SLA Due</span><b style="font-size:14px">{{ optional($serviceRequest->current_stage_due_at)->format('Y-m-d H:i') ?: '-' }}</b></div>
</section>
<section class="wf-card">
<div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap"><div><div class="muted">Required Role</div><strong>{{ $step?->approval_role ?: '—' }}</strong></div><span class="pill">{{ $canAct ? 'ACTION AVAILABLE' : 'READ ONLY' }}</span></div>
@if($canAct)
<div style="margin-top:16px">
@if(in_array($serviceRequest->workflow_stage,['TRIAGE','EMERGENCY_DISPATCH']))
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.triage',$serviceRequest) }}">@csrf
<label><span class="muted">Responsible Project</span><select name="project_id"><option value="">Unassigned / no project</option>@foreach($projectOptions as $project)<option value="{{ $project->id }}" @selected((int)old('project_id',$serviceRequest->project_id)===(int)$project->id)>{{ $project->project_no }} · {{ $project->name }}</option>@endforeach</select></label>
@if($projectOptions->count()>1 && !$serviceRequest->project_id)<div class="muted" style="color:#a72c36">Select the responsible project before routing. Requests are never sent to a Project Manager from another project.</div>@endif
<textarea name="notes" rows="3" placeholder="Triage / dispatch notes"></textarea><button>Complete & Route</button></form>
@elseif($serviceRequest->workflow_stage==='PROJECT_MANAGER_REVIEW')
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.project-review',$serviceRequest) }}">@csrf<select name="decision" required><option value="APPROVE">Approve</option><option value="RETURN">Return for review</option></select><textarea name="notes" rows="3" placeholder="Project review notes"></textarea><button>Record Project Review</button></form>
@elseif($serviceRequest->workflow_stage==='TECHNICIAN_ASSIGNMENT')
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.assign-technician',$serviceRequest) }}">@csrf<select name="technician_id" required><option value="">Select technician</option>@foreach($technicians as $tech)<option value="{{ $tech->id }}">{{ $tech->name }} · {{ $tech->role }}</option>@endforeach</select><textarea name="notes" rows="2" placeholder="Assignment notes"></textarea><button>Assign & Start Execution</button></form>
@elseif($serviceRequest->workflow_stage==='EXECUTION')
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.complete-execution',$serviceRequest) }}">@csrf<textarea name="completion_notes" rows="5" required placeholder="Work performed, readings, findings and completion notes"></textarea><button>Complete Technical Execution</button></form>
@elseif(in_array($serviceRequest->workflow_stage,['QUALITY_VERIFICATION','HSE_VERIFICATION']))
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.verify',$serviceRequest) }}">@csrf<select name="decision" required><option value="APPROVE">Approve</option><option value="REWORK">Return for rework</option></select><textarea name="notes" rows="3" placeholder="Verification notes"></textarea><button>Record Verification</button></form>
@elseif($serviceRequest->workflow_stage==='CLOSURE')
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.close',$serviceRequest) }}">@csrf<textarea name="notes" rows="3" placeholder="Operational closure notes"></textarea><button>Operational Close & Send CSAT</button></form>
@endif
</div>
@else
<p class="muted" style="margin-top:14px">This stage is assigned to another role, user or scope. The server enforces the same restriction on every workflow action.</p>
@endif
</section>
<section class="wf-card"><div class="muted">Request Details</div><p>{{ $serviceRequest->details }}</p><div class="muted">Priority: {{ $serviceRequest->priority }} · Customer ID: {{ $serviceRequest->customer_id }} · Work Order: {{ $serviceRequest->work_order_id ?: '—' }}</div></section>
</div>
@endsection
