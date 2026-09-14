<?php

namespace App\Services;

use App\Models\{ApprovalRequest,ServiceRequest,User,WorkOrder};

class MaintenanceRequestTransitionService
{
    public function __construct(
        private AuthorizationService $authorization,
        private ScopeService $scopes,
        private ServiceRequestWorkflowService $workflow,
    ) {}

    public function complete(User $actor, ServiceRequest $request, array $stages, ?string $note = null): void
    {
        $this->assertCanAct($actor, $request, $stages);
        $this->workflow->advance($request, $request->workflow_stage, $actor->id, $note);
    }

    public function returnToStage(User $actor, ServiceRequest $request, array $fromStages, string $targetStage, ?string $note = null): void
    {
        $this->assertCanAct($actor, $request, $fromStages);
        $this->workflow->returnTo($request, $targetStage, $actor->id, $note ?: 'Workflow returned for additional work.');
    }

    public function assignTechnician(User $actor, ServiceRequest $request, int $technicianId, ?string $note = null): User
    {
        $this->assertCanAct($actor, $request, ['TECHNICIAN_ASSIGNMENT']);
        $technician = User::query()
            ->where('tenant_id', $actor->tenant_id)
            ->whereKey($technicianId)
            ->whereIn('status', ['ACTIVE','ENABLED'])
            ->whereIn('role', ['TECHNICIAN','MAINTENANCE_ENGINEER'])
            ->firstOrFail();
        $request->update(['assigned_engineer_id' => $technician->id]);
        $this->workflow->advance($request, 'TECHNICIAN_ASSIGNMENT', $actor->id, $note ?: 'Technician assigned.');
        return $technician;
    }

    public function completeExecution(User $actor, ServiceRequest $request, string $notes): void
    {
        $this->assertCanAct($actor, $request, ['EXECUTION']);
        abort_unless((int) $request->assigned_engineer_id === (int) $actor->id, 403, 'Only the assigned technician can complete execution.');
        if ($request->work_order_id) {
            $workOrder = WorkOrder::query()->where('tenant_id', $request->tenant_id)->findOrFail($request->work_order_id);
            if ($workOrder->status !== 'COMPLETED') {
                $workOrder->update([
                    'status' => 'COMPLETED',
                    'completed_at' => now(),
                    'completed_by' => $actor->id,
                    'completion_notes' => $notes,
                ]);
            }
        }
        $this->workflow->advance($request, 'EXECUTION', $actor->id, $notes);
    }

    public function rework(User $actor, ServiceRequest $request, string $fromStage, ?string $note = null): void
    {
        $this->returnToStage($actor, $request, [$fromStage], 'EXECUTION', $note ?: 'Rework required.');
    }

    private function assertCanAct(User $actor, ServiceRequest $request, array $stages): ApprovalRequest
    {
        abort_unless((int) $request->tenant_id === (int) $actor->tenant_id, 404);
        abort_unless(in_array($request->workflow_stage, $stages, true), 422, 'Action is not available at the current workflow stage.');
        $visible = $this->scopes->apply(
            ServiceRequest::query()->where('tenant_id', $actor->tenant_id)->whereKey($request->id),
            $actor
        )->exists();
        abort_unless($visible, 403, 'Request is outside your access scope.');
        $step = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $request->id)
            ->where('action', $request->workflow_stage)
            ->where('status', 'PENDING')
            ->firstOrFail();
        $roles = $this->authorization->roleCodes($actor)->push(strtoupper((string) $actor->role));
        abort_unless($roles->filter()->contains(strtoupper((string) $step->approval_role)), 403, 'Your role cannot perform this workflow action.');
        return $step;
    }
}
