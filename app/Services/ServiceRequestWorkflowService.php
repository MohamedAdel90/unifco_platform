<?php

namespace App\Services;

use App\Models\{ApprovalRequest,ServiceRequest,User};
use Illuminate\Support\Collection;

class ServiceRequestWorkflowService
{
    public const SLA = [
        'TRIAGE' => 60,
        'EMERGENCY_DISPATCH' => 10,
        'SALES_REVIEW' => 120,
        'OPERATIONS_REVIEW' => 120,
        'PROJECT_MANAGER_REVIEW' => 120,
        'TECHNICIAN_ASSIGNMENT' => 60,
        'EXECUTION' => 1440,
        'TECHNICAL_REVIEW' => 120,
        'TEAM_AND_SCHEDULE' => 240,
        'SITE_VISIT' => 1440,
        'TECHNICAL_REPORT' => 480,
        'PRICING' => 240,
        'PRICING_PROCUREMENT' => 240,
        'CONTRACT_REVIEW' => 240,
        'OPERATIONS_FEASIBILITY' => 240,
        'INTERNAL_APPROVAL' => 240,
        'FINANCE_REVIEW' => 120,
        'EXECUTIVE_APPROVAL' => 240,
        'QUALITY_VERIFICATION' => 120,
        'HSE_VERIFICATION' => 120,
        'CUSTOMER_ACCEPTANCE' => 1440,
        'CUSTOMER_DECISION' => 2880,
        'CUSTOMER_DELIVERY' => 1440,
        'PO_OR_CONTRACT' => 2880,
        'ONBOARDING' => 1440,
        'CLOSURE' => 120,
        'CSAT' => 2880,
        'COMPLETED' => 1,
    ];

    public function __construct(private ServiceRequestWorkflowTemplateRegistry $templates) {}

    public function start(ServiceRequest $request, array $context = []): Collection
    {
        $context += [
            'chargeable' => $request->eligibility === 'CHARGEABLE',
            'procurement_required' => (bool) $request->procurement_required,
            'risk_level' => 'NORMAL',
            'quality_required' => false,
            'hse_required' => false,
        ];

        $workflowKey = $this->templates->keyFor($request);
        $steps = $this->templates->template($workflowKey, $context);
        $first = $steps[0] ?? ['stage' => 'TRIAGE', 'department' => 'OPERATIONS'];

        $requester = User::where('tenant_id', $request->tenant_id)
            ->where('role', '!=', 'CUSTOMER')
            ->whereIn('status', ['ACTIVE','ENABLED'])
            ->orderByRaw("CASE WHEN role='ADMIN' THEN 0 ELSE 1 END")
            ->first();

        $request->update([
            'workflow_key' => $workflowKey,
            'workflow_stage' => $first['stage'],
            'assigned_department' => $first['department'],
            'approval_state' => 'PENDING',
            'next_action' => $first['stage'],
            'workflow_context' => $context,
            'workflow_started_at' => $request->workflow_started_at ?: now(),
            'current_stage_due_at' => now()->addMinutes($this->slaFor($first['stage'])),
        ]);

        if (! $requester) return collect();

        return collect($steps)->values()->map(function (array $step, int $index) use ($request, $requester, $context, $workflowKey) {
            $stage = $step['stage'];
            $sla = $this->slaFor($stage);
            $status = $index === 0 ? 'PENDING' : 'WAITING';

            return ApprovalRequest::firstOrCreate([
                'tenant_id' => $request->tenant_id,
                'entity_type' => ServiceRequest::class,
                'entity_id' => $request->id,
                'action' => $stage,
            ], [
                'organization_id' => $request->organization_id,
                'requested_by' => $requester->id,
                'workflow_key' => 'SERVICE_REQUEST_'.$workflowKey,
                'approval_role' => $step['role'],
                'step_order' => $index + 1,
                'sla_minutes' => $sla,
                'status' => $status,
                'due_at' => $status === 'PENDING' ? now()->addMinutes($sla) : null,
                'metadata' => $context + ['department' => $step['department'], 'stage' => $stage],
            ]);
        });
    }

    public function advance(ServiceRequest $request, string $completedStage): ?ApprovalRequest
    {
        $current = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $request->id)
            ->where('action', $completedStage)
            ->first();

        if ($current && ! in_array($current->status, ['APPROVED','COMPLETED'], true)) {
            $current->update(['status' => 'COMPLETED', 'decided_at' => now()]);
        }

        $next = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $request->id)
            ->where('step_order', '>', (int) ($current?->step_order ?? 0))
            ->orderBy('step_order')
            ->first();

        if (! $next) {
            $request->update([
                'workflow_stage' => 'COMPLETED',
                'assigned_department' => null,
                'approval_state' => 'COMPLETED',
                'next_action' => null,
                'current_stage_due_at' => null,
            ]);
            return null;
        }

        $metadata = (array) ($next->metadata ?? []);
        $next->update(['status' => 'PENDING', 'due_at' => now()->addMinutes((int) $next->sla_minutes)]);
        $request->update([
            'workflow_stage' => $next->action,
            'assigned_department' => $metadata['department'] ?? null,
            'approval_state' => 'PENDING',
            'next_action' => $next->action,
            'current_stage_due_at' => $next->due_at,
        ]);

        return $next->fresh();
    }

    private function slaFor(string $stage): int
    {
        return self::SLA[$stage] ?? 240;
    }
}
