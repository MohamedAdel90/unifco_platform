<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\MaintenanceRequestTransitionService;
use Illuminate\Http\{RedirectResponse,Request};

class MaintenanceRequestWorkflowController extends Controller
{
    public function triage(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable','string','max:2000']]);
        $transitions->complete($request->user(), $serviceRequest, ['TRIAGE','EMERGENCY_DISPATCH'], $data['notes'] ?? null);
        return back()->with('status', 'Request routed to the next workflow stage.');
    }

    public function projectReview(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required','in:APPROVE,RETURN'],
            'notes' => ['nullable','string','max:2000'],
        ]);
        if ($data['decision'] === 'RETURN') {
            $target = $serviceRequest->workflow_key === 'EMERGENCY_MAINTENANCE' ? 'EMERGENCY_DISPATCH' : 'TRIAGE';
            $transitions->rework($request->user(), $serviceRequest, 'PROJECT_MANAGER_REVIEW', $data['notes'] ?? 'Project Manager requested additional review.');
            if ($target !== 'EXECUTION') app(\App\Services\ServiceRequestWorkflowService::class)->returnTo($serviceRequest, $target, $request->user()->id, $data['notes'] ?? null);
        } else {
            $transitions->complete($request->user(), $serviceRequest, ['PROJECT_MANAGER_REVIEW'], $data['notes'] ?? null);
        }
        return back()->with('status', 'Project Manager review recorded.');
    }

    public function assignTechnician(Request $request, ServiceRequest $serviceRequest, MaintenanceRequestTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate([
            'technician_id' => ['required','integer'],
            'notes' => ['nullable','string','max:2000'],
        ]);
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
        $data = $request->validate([
            'decision' => ['required','in:APPROVE,REWORK'],
            'notes' => ['nullable','string','max:2000'],
        ]);
        $stage = $serviceRequest->workflow_stage;
        if ($data['decision'] === 'REWORK') $transitions->rework($request->user(), $serviceRequest, $stage, $data['notes'] ?? null);
        else $transitions->complete($request->user(), $serviceRequest, [$stage], $data['notes'] ?? null);
        return back()->with('status', 'Verification decision recorded.');
    }
}
