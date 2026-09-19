<?php

namespace App\Services;

use App\Models\{ApprovalRequest,Customer,FinancialDocument,ServiceRequest,User,WorkOrder};
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

        $this->ensureStageArtifacts($request);

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

    public function advance(ServiceRequest $request, string $completedStage, ?int $actorId = null, ?string $note = null): ?ApprovalRequest
    {
        $current = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $request->id)
            ->where('action', $completedStage)
            ->first();

        if ($current && ! in_array($current->status, ['APPROVED','COMPLETED'], true)) {
            $current->update([
                'status' => 'COMPLETED',
                'decided_by' => $actorId,
                'decision_note' => $note,
                'decided_at' => now(),
            ]);
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
                'status' => 'COMPLETED',
                'resolved_at' => $request->resolved_at ?: now(),
            ]);
            return null;
        }

        $metadata = (array) ($next->metadata ?? []);
        $next->update([
            'status' => 'PENDING',
            'due_at' => now()->addMinutes((int) $next->sla_minutes),
            'decided_by' => null,
            'decision_note' => null,
            'decided_at' => null,
        ]);
        $request->update([
            'workflow_stage' => $next->action,
            'assigned_department' => $metadata['department'] ?? null,
            'approval_state' => 'PENDING',
            'next_action' => $next->action,
            'current_stage_due_at' => $next->due_at,
        ]);

        $this->ensureStageArtifacts($request->fresh());

        return $next->fresh();
    }

    private function ensureStageArtifacts(ServiceRequest $request): void
    {
        if ($request->workflow_stage === 'EXECUTION' && ! $request->work_order_id && $request->asset_id) {
            $workOrder = WorkOrder::create([
                'tenant_id' => $request->tenant_id,
                'organization_id' => $request->organization_id,
                'work_order_no' => 'SR-'.$request->id.'-WO',
                'asset_id' => $request->asset_id,
                'service_contract_id' => $request->service_contract_id,
                'maintenance_type' => 'CORRECTIVE',
                'priority' => $request->priority === 'EMERGENCY' ? 'CRITICAL' : ($request->priority ?: 'NORMAL'),
                'status' => 'OPEN',
                'planned_start' => now(),
            ]);
            $request->update(['work_order_id' => $workOrder->id]);
        }

        if ($request->workflow_stage === 'FINANCE_REVIEW' && $request->eligibility === 'CHARGEABLE' && $request->customer_id) {
            $context = (array) ($request->workflow_context ?? []);
            if (! empty($context['invoice_id'])) return;
            $amount = $request->work_order_id ? (float) WorkOrder::whereKey($request->work_order_id)->value('total_cost') : (float) ($context['estimated_value'] ?? 0);
            if ($amount <= 0) return;
            $customer = Customer::find($request->customer_id);
            if (! $customer) return;
            $invoice = FinancialDocument::firstOrCreate([
                'tenant_id' => $request->tenant_id,
                'document_no' => 'INV-SR-'.$request->id,
            ], [
                'organization_id' => $request->organization_id,
                'customer_id' => $request->customer_id,
                'document_type' => 'AR_INVOICE',
                'counterparty_name' => $customer->name,
                'document_date' => today(),
                'due_date' => today()->addDays((int) ($context['payment_terms_days'] ?? 30)),
                'currency' => 'SAR',
                'amount' => $amount,
                'open_amount' => $amount,
                'control_account_code' => 'AR',
                'offset_account_code' => 'REV',
                'status' => 'DRAFT',
            ]);
            $context['invoice_id'] = $invoice->id;
            $context['invoice_no'] = $invoice->document_no;
            $request->update(['workflow_context' => $context]);
        }
    }

    public function returnTo(ServiceRequest $request, string $targetStage, ?int $actorId = null, ?string $note = null): ApprovalRequest
    {
        $steps = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $request->id)
            ->orderBy('step_order')
            ->get();
        $target = $steps->firstWhere('action', $targetStage);
        abort_unless($target, 422, 'The requested return stage is not part of this workflow.');

        foreach ($steps as $step) {
            if ((int) $step->step_order < (int) $target->step_order) continue;
            $step->update([
                'status' => $step->id === $target->id ? 'PENDING' : 'WAITING',
                'due_at' => $step->id === $target->id ? now()->addMinutes((int) $step->sla_minutes) : null,
                'decided_by' => null,
                'decision_note' => $step->id === $target->id ? $note : null,
                'decided_at' => null,
            ]);
        }

        $metadata = (array) ($target->metadata ?? []);
        $request->update([
            'workflow_stage' => $target->action,
            'assigned_department' => $metadata['department'] ?? null,
            'approval_state' => 'REWORK',
            'next_action' => $target->action,
            'current_stage_due_at' => now()->addMinutes((int) $target->sla_minutes),
            'status' => 'OPEN',
        ]);

        return $target->fresh();
    }

    public function returnToPrevious(ServiceRequest $request, string $currentStage, ?int $actorId = null, ?string $note = null): ApprovalRequest
    {
        $current = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $request->id)
            ->where('action', $currentStage)
            ->firstOrFail();
        $previous = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $request->id)
            ->where('step_order', '<', $current->step_order)
            ->orderByDesc('step_order')
            ->first();

        abort_unless($previous, 422, 'This workflow has no previous stage to return to.');
        return $this->returnTo($request, $previous->action, $actorId, $note);
    }

    public function reject(ServiceRequest $request, string $currentStage, ?int $actorId = null, ?string $note = null): void
    {
        $current = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $request->id)
            ->where('action', $currentStage)
            ->first();
        if ($current) {
            $current->update([
                'status' => 'REJECTED',
                'decided_by' => $actorId,
                'decision_note' => $note,
                'decided_at' => now(),
            ]);
            ApprovalRequest::query()
                ->where('tenant_id', $request->tenant_id)
                ->where('entity_type', ServiceRequest::class)
                ->where('entity_id', $request->id)
                ->where('step_order', '>', $current->step_order)
                ->whereIn('status', ['WAITING','PENDING'])
                ->update(['status' => 'CANCELLED', 'due_at' => null]);
        }
        $request->update([
            'status' => 'REJECTED',
            'workflow_stage' => 'REJECTED',
            'assigned_department' => null,
            'approval_state' => 'REJECTED',
            'next_action' => null,
            'current_stage_due_at' => null,
        ]);
    }

    private function slaFor(string $stage): int
    {
        return self::SLA[$stage] ?? 240;
    }
}
