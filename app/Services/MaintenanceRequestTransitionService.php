<?php

namespace App\Services;

use App\Models\{ApprovalRequest,ProjectUserAssignment,ServiceRequest,User,WorkOrder};

class MaintenanceRequestTransitionService
{
    public function __construct(
        private AuthorizationService $authorization,
        private ScopeService $scopes,
        private ServiceRequestWorkflowService $workflow,
        private RequestStageOwnerService $owners,
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
            ->where(function($q){
                $q->whereIn('role',['TECHNICIAN','MAINTENANCE_ENGINEER'])
                  ->orWhereHas('activeRoles',fn($r)=>$r->whereIn('roles.code',['TECHNICIAN','MAINTENANCE_ENGINEER']));
            })
            ->firstOrFail();

        if($request->project_id){
            $isProjectMember=ProjectUserAssignment::query()
                ->where('tenant_id',$actor->tenant_id)
                ->where('project_id',$request->project_id)
                ->where('user_id',$technician->id)
                ->where('status','ACTIVE')
                ->whereIn('project_role',['TECHNICIAN','MAINTENANCE_ENGINEER'])
                ->where(fn($q)=>$q->whereNull('starts_on')->orWhere('starts_on','<=',today()))
                ->where(fn($q)=>$q->whereNull('ends_on')->orWhere('ends_on','>=',today()))
                ->exists();
            abort_unless($isProjectMember,422,'The selected technician is not an active technical member of this project.');
        }

        $request->update(['assigned_engineer_id' => $technician->id]);
        $this->owners->refresh($request->fresh());
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
        abort_if($step->routing_status==='NEEDS_ASSIGNMENT',422,'This workflow stage has no resolved owner yet.');
        if($step->assigned_user_id){
            abort_unless((int)$step->assigned_user_id===(int)$actor->id,403,'This workflow stage is assigned to another user.');
        }
        return $step;
    }
}
