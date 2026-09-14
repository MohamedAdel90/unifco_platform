<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalRequest,ServiceRequest,User};
use App\Services\{AuthorizationService,MaintenanceRequestTransitionService,ScopeService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class MaintenanceRequestWorkflowController extends Controller
{
    public function show(Request $request, ServiceRequest $serviceRequest, AuthorizationService $authorization, ScopeService $scopes): View
    {
        $user = $request->user();
        abort_unless((int) $serviceRequest->tenant_id === (int) $user->tenant_id, 404);
        if ($user->role === 'CUSTOMER') {
            abort_unless((int) $user->customer_id === (int) $serviceRequest->customer_id, 404);
        } else {
            $visible = $scopes->apply(ServiceRequest::query()->where('tenant_id', $user->tenant_id)->whereKey($serviceRequest->id), $user)->exists();
            abort_unless($visible, 404);
        }

        $step = ApprovalRequest::query()
            ->where('tenant_id', $serviceRequest->tenant_id)
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $serviceRequest->id)
            ->where('action', $serviceRequest->workflow_stage)
            ->where('status', 'PENDING')
            ->first();
        $roles = $authorization->roleCodes($user)->push(strtoupper((string) $user->role))->filter()->unique();
        $canAct = $step && $roles->contains(strtoupper((string) $step->approval_role));
        if ($serviceRequest->workflow_stage === 'EXECUTION') $canAct = $canAct && (int) $serviceRequest->assigned_engineer_id === (int) $user->id;

        $technicians = User::query()->where('tenant_id', $user->tenant_id)
            ->whereIn('status', ['ACTIVE','ENABLED'])->whereIn('role', ['TECHNICIAN','MAINTENANCE_ENGINEER'])
            ->orderBy('name')->get(['id','name','role']);
        return view('workflow.maintenance-request', compact('serviceRequest','step','canAct','technicians'));
    }

    public function triage(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable','string','max:2000']]);
        $transitions->complete($request->user(), $serviceRequest, ['TRIAGE','EMERGENCY_DISPATCH'], $data['notes'] ?? null);
        return back()->with('status', 'Request routed to the next workflow stage.');
    }

    public function projectReview(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required','in:APPROVE,RETURN'],'notes' => ['nullable','string','max:2000']]);
        if ($data['decision'] === 'RETURN') {
            $target = $serviceRequest->workflow_key === 'EMERGENCY_MAINTENANCE' ? 'EMERGENCY_DISPATCH' : 'TRIAGE';
            $transitions->returnToStage($request->user(), $serviceRequest, ['PROJECT_MANAGER_REVIEW'], $target, $data['notes'] ?? 'Project Manager requested additional review.');
        } else {
            $transitions->complete($request->user(), $serviceRequest, ['PROJECT_MANAGER_REVIEW'], $data['notes'] ?? null);
        }
        return back()->with('status', 'Project Manager review recorded.');
    }

    public function assignTechnician(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['technician_id' => ['required','integer'],'notes' => ['nullable','string','max:2000']]);
        $transitions->assignTechnician($request->user(), $serviceRequest, (int) $data['technician_id'], $data['notes'] ?? null);
        return back()->with('status', 'Technician assigned and request moved to execution.');
    }

    public function completeExecution(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['completion_notes' => ['required','string','max:5000']]);
        $transitions->completeExecution($request->user(), $serviceRequest, $data['completion_notes']);
        return back()->with('status', 'Execution completed and sent to verification / customer acceptance.');
    }

    public function verify(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required','in:APPROVE,REWORK'],'notes' => ['nullable','string','max:2000']]);
        $stage = $serviceRequest->workflow_stage;
        abort_unless(in_array($stage, ['QUALITY_VERIFICATION','HSE_VERIFICATION'], true), 422);
        if ($data['decision'] === 'REWORK') $transitions->rework($request->user(), $serviceRequest, $stage, $data['notes'] ?? null);
        else $transitions->complete($request->user(), $serviceRequest, [$stage], $data['notes'] ?? null);
        return back()->with('status', 'Verification decision recorded.');
    }

    public function close(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable','string','max:2000']]);
        $context = (array) ($serviceRequest->workflow_context ?? []);
        $context['operationally_closed_at'] = now()->toIso8601String();
        $context['operationally_closed_by'] = $request->user()->id;
        $serviceRequest->update(['workflow_context' => $context,'status' => 'RESOLVED','resolved_at' => $serviceRequest->resolved_at ?: now()]);
        $transitions->complete($request->user(), $serviceRequest, ['CLOSURE'], $data['notes'] ?? 'Operational closure completed.');
        return back()->with('status', 'Request operationally closed and sent for customer satisfaction.');
    }
}
