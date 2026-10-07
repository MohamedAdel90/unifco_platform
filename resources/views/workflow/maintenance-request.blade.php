@extends('layouts.app')
@section('title','Maintenance Request Workflow')
@section('content')
<style>
.wf-shell{display:grid;gap:18px}.wf-hero{background:linear-gradient(135deg,#071a33,#123c73);color:#fff;border-radius:18px;padding:22px}.wf-hero h1{margin:4px 0 6px}.wf-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.wf-card{background:#fff;border:1px solid #e4ebf3;border-radius:14px;padding:16px;box-shadow:0 7px 22px rgba(15,37,67,.05)}.wf-card b{display:block;color:#123c73;font-size:18px}.muted{font-size:12px;color:#718198}.pill{display:inline-flex;border-radius:999px;padding:5px 9px;background:#eef4fb;color:#26496e;font-size:11px;font-weight:800}.wf-form{display:grid;gap:10px}.wf-form input,.wf-form select,.wf-form textarea{width:100%;border:1px solid #d6e0eb;border-radius:9px;padding:10px}.wf-form button{border:0;border-radius:9px;padding:11px 15px;background:#123c73;color:#fff;font-weight:800;cursor:pointer}.wf-form button.alt{background:#a72c36}@media(max-width:850px){.wf-grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.wf-grid{grid-template-columns:1fr}}
</style>
<div class="wf-shell">
<section class="wf-hero"><div style="font-size:11px;font-weight:800;color:#a9c5e7">UNIFCO · REQUEST EXECUTION WORKSPACE</div><h1>{{ $serviceRequest->request_no }}</h1><div>{{ $serviceRequest->subject }}</div></section>
@if(session('status'))<div class="wf-card" style="border-left:4px solid #1f8a5b">{{ session('status') }}</div>@endif
@if($errors->any())<div class="wf-card" role="alert" style="border-left:4px solid #a72c36;color:#8f2230"><strong>Workflow action was not completed</strong><ul style="margin:8px 0 0;padding-left:20px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="wf-grid">
<div class="wf-card"><span class="muted">Workflow</span><b>{{ str_replace('_',' ',$serviceRequest->workflow_key ?: '-') }}</b></div>
<div class="wf-card"><span class="muted">Current Stage</span><b>{{ str_replace('_',' ',$serviceRequest->workflow_stage ?: '-') }}</b></div>
<div class="wf-card"><span class="muted">Department</span><b>{{ $serviceRequest->assigned_department ?: '-' }}</b></div>
<div class="wf-card"><span class="muted">SLA Due</span><b style="font-size:14px">{{ optional($serviceRequest->current_stage_due_at)->format('Y-m-d H:i') ?: '-' }}</b></div>
</section>
<section class="wf-card">
<div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap"><div><div class="muted">Required Role</div><strong>{{ $step?->approval_role ?: '—' }}</strong><div class="muted" style="margin-top:4px">Routing: {{ $step?->routing_status ?: '—' }}@if($step?->assigned_user_id) · User #{{ $step->assigned_user_id }}@endif</div></div><span class="pill">{{ $canAct ? 'ACTION AVAILABLE' : ($canAssignOwner ? 'OWNER REQUIRED' : 'READ ONLY') }}</span></div>
@if($canAssignProject)
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.assign-project',$serviceRequest) }}" style="margin-top:16px;padding:14px;border:1px solid #f0c7cc;border-radius:10px;background:#fff7f8">@csrf
<div><strong style="color:#a72c36">Responsible customer project required</strong><div class="muted">Operations must select the active customer project before this project-scoped review can proceed.</div></div>
<select name="project_id" required><option value="">Select responsible project</option>@foreach($projectOptions as $project)<option value="{{ $project->id }}" @selected((int)old('project_id')===(int)$project->id)>{{ $project->project_no }} · {{ $project->name }}</option>@endforeach</select>
@if($projectOptions->isEmpty())<div class="muted" style="color:#a72c36">No active project exists for this customer. Create one and assign its Project Manager before routing.</div>@endif
<button type="submit" @disabled($projectOptions->isEmpty())>Link Project & Reassign Review</button>
</form>
@endif
@if($canAssignOwner)
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.assign-stage-owner',$serviceRequest) }}" style="margin-top:16px;padding:14px;border:1px solid #f0c7cc;border-radius:10px;background:#fff7f8">@csrf
<div><strong style="color:#a72c36">Responsible user must be selected</strong><div class="muted">Only eligible active users with the required role inside this project are listed.</div></div>
<select name="owner_user_id" required><option value="">Select responsible user</option>@foreach($ownerCandidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->name }} · {{ $candidate->email }}</option>@endforeach</select>
<button class="alt" type="submit">Assign Stage Owner</button>
</form>
@endif
@if($canAct)
<div style="margin-top:16px">
@if(in_array($serviceRequest->workflow_stage,['TRIAGE','EMERGENCY_DISPATCH','OPERATIONS_REVIEW']))
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.triage',$serviceRequest) }}">@csrf
<label><span class="muted">Responsible Project</span><select name="project_id"><option value="">Select responsible project</option>@foreach($projectOptions as $project)<option value="{{ $project->id }}" @selected((int)old('project_id',$serviceRequest->project_id)===(int)$project->id)>{{ $project->project_no }} · {{ $project->name }}</option>@endforeach</select></label>
@if($projectOptions->isEmpty())<div class="muted" style="color:#a72c36">No active project exists for this customer. Create a project and assign its Project Manager before routing.</div>@elseif($projectOptions->count()>1 && !$serviceRequest->project_id)<div class="muted" style="color:#a72c36">Select the responsible project before routing. Requests are never sent to a Project Manager from another project.</div>@endif
<textarea name="notes" rows="3" placeholder="Triage / dispatch notes"></textarea><button @disabled($projectOptions->isEmpty())>Complete & Route</button></form>
@elseif($serviceRequest->workflow_stage==='PROJECT_MANAGER_REVIEW')
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.project-review',$serviceRequest) }}">@csrf<select name="decision" required><option value="APPROVE">Approve</option><option value="RETURN">Return for review</option></select><textarea name="notes" rows="3" placeholder="Project review notes"></textarea><button>Record Project Review</button></form>
@elseif(in_array($serviceRequest->workflow_stage,['MAINTENANCE_MANAGER_REVIEW','TECHNICAL_ASSESSMENT','TECHNICAL_REVIEW','TECHNICAL_REPORT']))
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.stage-review',$serviceRequest) }}">@csrf
<select name="decision" required>
<option value="APPROVE">Approve & Continue</option>
<option value="RETURN">Return for revision</option>
</select>
<textarea name="notes" rows="4" @required($serviceRequest->workflow_stage==='TECHNICAL_REPORT') placeholder="Technical review notes, findings, risks, recommendations"></textarea>
@if($serviceRequest->workflow_stage==='TECHNICAL_REPORT')
<label>Customer-visible consultation report<textarea name="customer_report" rows="6" placeholder="Describe the findings, outcome and recommendations the customer should review before accepting delivery.">{{ old('customer_report') }}</textarea></label>
<p class="muted">This report is shown to the customer. Keep internal notes in the field above.</p>
@endif
<button>Record Review</button>
</form>
@elseif(($serviceRequest->workflow_stage==='CONTRACT_REVIEW' && $serviceRequest->workflow_key==='SPARE_PARTS_QUOTATION') || ($serviceRequest->workflow_stage==='PRICING' && $serviceRequest->workflow_key==='TECHNICAL_VISIT') || ($serviceRequest->workflow_stage==='FINANCE_REVIEW' && $serviceRequest->workflow_key==='MAINTENANCE_CONTRACT_QUOTATION'))
@if($quotation)
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.quotation-pricing',$serviceRequest) }}">@csrf
<strong>Estimated quotation · {{ $quotation->quotation_no }}</strong>
<p class="muted">Enter an indicative cost and customer quotation amount in SAR. Describe the assumed scope and quantities, and any details requiring confirmation before a binding offer.</p>
<label>Estimated cost (SAR)<input name="cost_amount" type="number" step="0.01" min="0" required value="{{ old('cost_amount',$quotation->cost_amount) }}"></label>
<label>Estimated customer quotation (SAR)<input name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount',$quotation->amount) }}"></label>
@if($serviceRequest->workflow_key==='MAINTENANCE_CONTRACT_QUOTATION')
<label>Payment due after invoice (days)<input name="payment_terms_days" type="number" step="1" min="0" max="365" required value="{{ old('payment_terms_days',$quotation->payment_terms_days) }}"></label>
<p class="muted">Record the proposed contract duration, equipment list, visit frequency, exclusions and billing assumptions in the pricing basis. These remain indicative until approved.</p>
@endif
<label>Pricing basis and unconfirmed details<textarea name="pricing_basis" rows="4" required>{{ old('pricing_basis',data_get($serviceRequest->workflow_context,'quotation_pricing.basis')) }}</textarea></label>
<button type="submit">Save Estimated Pricing</button>
</form>
@else
<p role="alert">The linked quotation is missing or outside this customer scope. Pricing review cannot continue.</p>
@endif
@elseif($serviceRequest->workflow_stage==='TECHNICIAN_ASSIGNMENT')
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.assign-technician',$serviceRequest) }}">@csrf<select name="technician_id" required><option value="">Select technician</option>@foreach($technicians as $tech)<option value="{{ $tech->id }}">{{ $tech->name }} · {{ $tech->role }}</option>@endforeach</select><textarea name="notes" rows="2" placeholder="Assignment notes"></textarea><button>Assign & Start Execution</button></form>
@elseif(in_array($serviceRequest->workflow_stage,['EXECUTION','SITE_VISIT']))
@if($serviceRequest->work_order_id)
<p>Record the work, required checklist results, photos, and final costs on the linked work order. Complete that order before submitting the technical work.</p>
<a href="{{ route('maintenance.work-orders.show',$serviceRequest->work_order_id) }}">Open Work Order #{{ $serviceRequest->work_order_id }}</a>
@endif
@if(!$serviceRequest->work_order_id || $serviceRequest->workflow_stage==='SITE_VISIT')
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.complete-execution',$serviceRequest) }}">@csrf<textarea name="completion_notes" rows="5" required placeholder="Work performed, readings, findings and completion notes"></textarea><button>{{ $serviceRequest->workflow_stage==='SITE_VISIT' ? 'Complete Site Visit & Send Technical Report' : 'Complete Technical Execution' }}</button></form>
@endif
@elseif(in_array($serviceRequest->workflow_stage,['QUALITY_VERIFICATION','HSE_CLEARANCE']))
<form class="wf-form" method="post" action="{{ route('service-requests.workflow.verify',$serviceRequest) }}">@csrf<select name="decision" required><option value="APPROVE">Approve</option><option value="REWORK">Return / Require corrective action</option></select><textarea name="notes" rows="3" placeholder="Verification / clearance notes"></textarea><button>Record Verification</button></form>
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
