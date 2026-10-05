<?php

namespace App\Services;

use App\Models\{ApprovalRequest,Asset,ChartAccount,Customer,FinancialDocument,ServiceRequest,User,WorkOrder};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceRequestWorkflowService
{
    public const SLA = [
        'TRIAGE' => 60,
        'EMERGENCY_DISPATCH' => 10,
        'SALES_REVIEW' => 120,
        'OPERATIONS_REVIEW' => 120,
        'PROJECT_MANAGER_REVIEW' => 120,
        'MAINTENANCE_MANAGER_REVIEW' => 120,
        'TECHNICAL_ASSESSMENT' => 180,
        'TECHNICIAN_ASSIGNMENT' => 60,
        'EXECUTION' => 1440,
        'TECHNICAL_REVIEW' => 120,
        'TEAM_AND_SCHEDULE' => 240,
        'SITE_VISIT' => 1440,
        'TECHNICAL_REPORT' => 480,
        'PRICING' => 240,
        'PRICING_PROCUREMENT' => 240,
        'PROCUREMENT_HANDOFF' => 240,
        'CONTRACT_REVIEW' => 240,
        'OPERATIONS_FEASIBILITY' => 240,
        'INTERNAL_APPROVAL' => 240,
        'FINANCE_REVIEW' => 120,
        'EXECUTIVE_APPROVAL' => 240,
        'QUALITY_VERIFICATION' => 120,
        'HSE_CLEARANCE' => 120,
        'CUSTOMER_ACCEPTANCE' => 1440,
        'CUSTOMER_DECISION' => 2880,
        'CUSTOMER_DELIVERY' => 1440,
        'PO_OR_CONTRACT' => 2880,
        'ONBOARDING' => 1440,
        'CLOSURE' => 120,
        'CSAT' => 2880,
        'COMPLETED' => 1,
    ];

    public function __construct(
        private ServiceRequestWorkflowTemplateRegistry $templates,
        private RequestStageOwnerService $owners,
        private AuditService $audit,
    ) {}

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
        $before = $this->state($request);

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

        $this->ensureStageArtifacts($request->fresh());
        $this->audit->record(
            'SERVICE_REQUEST_WORKFLOW_STARTED',
            $request->fresh(),
            $before,
            $this->state($request->fresh()),
            reason: 'Workflow initialized',
            metadata: ['workflow_key' => $workflowKey, 'next_stage' => $first['stage']]
        );

        if (! $requester) return collect();

        $created = collect($steps)->values()->map(function (array $step, int $index) use ($request, $requester, $context, $workflowKey) {
            $stage = $step['stage'];
            $sla = $this->slaFor($stage);
            $status = $index === 0 ? 'PENDING' : 'WAITING';
            $owner = $this->owners->resolve($request,(string)$step['role'],$stage);

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
                'assigned_user_id' => $owner['user_id'],
                'routing_status' => $owner['status'],
                'step_order' => $index + 1,
                'sla_minutes' => $sla,
                'status' => $status,
                'due_at' => $status === 'PENDING' ? now()->addMinutes($sla) : null,
                'metadata' => $context + ['department' => $step['department'], 'stage' => $stage],
            ]);
        });

        $this->owners->refresh($request->fresh());
        return $created;
    }

    public function advance(ServiceRequest $request, string $completedStage, ?int $actorId = null, ?string $note = null): ?ApprovalRequest
    {
        $request->refresh();
        if ($completedStage === 'CUSTOMER_ACCEPTANCE') {
            $this->repairMissingMaintenanceClosureStages($request);
        }
        $before = $this->state($request);
        $current = ApprovalRequest::withoutGlobalScopes()
            ->where('tenant_id', $request->tenant_id)
            ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])
            ->where('entity_id', $request->id)
            ->where('action', $completedStage)
            ->first();

        $next = ApprovalRequest::withoutGlobalScopes()
            ->where('tenant_id', $request->tenant_id)
            ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])
            ->where('entity_id', $request->id)
            ->where('step_order', '>', (int) ($current?->step_order ?? 0))
            ->orderBy('step_order')
            ->first();

        // Validate before recording the approval: a failed transition must not
        // leave a completed approval attached to an unchanged request stage.
        if ($next?->action === 'EXECUTION' && $request->asset_id) {
            $this->assertExecutionAssetOwnership($request);
        }

        if ($current && ! in_array($current->status, ['APPROVED','COMPLETED'], true)) {
            $current->update([
                'status' => 'COMPLETED',
                'decided_by' => $actorId,
                'decision_note' => $note,
                'decided_at' => now(),
            ]);
        }

        if (! $next) {
            if ($completedStage === 'CUSTOMER_ACCEPTANCE') {
                throw ValidationException::withMessages([
                    'workflow_stage' => 'Customer acceptance cannot complete a maintenance request without its closing approvals.',
                ]);
            }
            $request->update([
                'workflow_stage' => 'COMPLETED',
                'assigned_department' => null,
                'approval_state' => 'COMPLETED',
                'next_action' => null,
                'current_stage_due_at' => null,
                'status' => 'COMPLETED',
                'resolved_at' => $request->resolved_at ?: now(),
            ]);
            $request->refresh();
            $this->recordTransition($request, $before, $completedStage, 'COMPLETED', $actorId, $note);
            return null;
        }

        $metadata = (array) ($next->metadata ?? []);
        $owner = $this->owners->resolve($request->fresh(),(string)$next->approval_role,(string)$next->action);
        $next->update([
            'status' => 'PENDING',
            'assigned_user_id' => $owner['user_id'],
            'routing_status' => $owner['status'],
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
        $request->refresh();
        $this->recordTransition($request, $before, $completedStage, (string) $next->action, $actorId, $note);

        return $next->fresh();
    }

    /** Restore the closing stages omitted from older maintenance approval chains. */
    public function repairMissingMaintenanceClosureStages(ServiceRequest $request): bool
    {
        if (! in_array($request->workflow_key, [
            ServiceRequestWorkflowTemplateRegistry::MAINTENANCE,
            ServiceRequestWorkflowTemplateRegistry::EMERGENCY_MAINTENANCE,
        ], true)) return false;

        $last = ApprovalRequest::withoutGlobalScopes()->where('tenant_id', $request->tenant_id)
            ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])->where('entity_id', $request->id)
            ->orderByDesc('step_order')->first();
        // Some older customer-acceptance actions marked the request complete
        // without deciding its already-persisted approval. Resume that accepted
        // order through the existing chain instead of appending duplicate steps.
        if ($last?->action !== 'CUSTOMER_ACCEPTANCE'
            && $request->status === 'COMPLETED' && $request->workflow_stage === 'COMPLETED'
            && $request->work_order_id
            && WorkOrder::whereKey($request->work_order_id)->whereNotNull('customer_accepted_at')->exists()) {
            $acceptance = ApprovalRequest::withoutGlobalScopes()->where('tenant_id', $request->tenant_id)
                ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])->where('entity_id', $request->id)
                ->where('action', 'CUSTOMER_ACCEPTANCE')->where('status', 'PENDING')->first();
            if ($acceptance && ApprovalRequest::withoutGlobalScopes()->where('tenant_id', $request->tenant_id)
                ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])->where('entity_id', $request->id)
                ->where('step_order', '>', $acceptance->step_order)->exists()) {
                DB::transaction(function () use ($request) {
                    $request->update(['status' => 'OPEN', 'workflow_stage' => 'CUSTOMER_ACCEPTANCE',
                        'assigned_department' => 'CUSTOMER', 'approval_state' => 'PENDING',
                        'next_action' => 'CUSTOMER_ACCEPTANCE', 'resolved_at' => null]);
                    $this->advance($request, 'CUSTOMER_ACCEPTANCE', null,
                        'Reconciled previously recorded customer acceptance.');
                });
                return true;
            }
        }
        if ($last?->action !== 'CUSTOMER_ACCEPTANCE') return false;

        $context = (array) ($request->workflow_context ?? []);
        $context['chargeable'] = $request->eligibility === 'CHARGEABLE';
        $steps = collect($this->templates->template($request->workflow_key, $context));
        $acceptanceIndex = $steps->search(fn (array $step) => $step['stage'] === 'CUSTOMER_ACCEPTANCE');
        if ($acceptanceIndex === false) return false;
        $tail = $steps->slice($acceptanceIndex + 1)->values();
        if ($tail->isEmpty()) return false;

        foreach ($tail as $index => $step) {
            ApprovalRequest::firstOrCreate([
                'tenant_id' => $request->tenant_id,
                'entity_type' => ServiceRequest::class,
                'entity_id' => $request->id,
                'action' => $step['stage'],
            ], [
                'organization_id' => $request->organization_id,
                'requested_by' => $last->requested_by,
                'workflow_key' => 'SERVICE_REQUEST_'.$request->workflow_key,
                'approval_role' => $step['role'],
                'step_order' => $last->step_order + $index + 1,
                'sla_minutes' => $this->slaFor($step['stage']),
                'status' => 'WAITING',
                'metadata' => $context + ['department' => $step['department'], 'stage' => $step['stage']],
            ]);
        }

        // A legacy chain may already have completed at customer acceptance.
        // Reopen only when the work was accepted and no closing stage existed.
        if ($request->workflow_stage === 'COMPLETED' && $request->status === 'COMPLETED'
            && $last->status === 'COMPLETED' && $request->work_order_id
            && WorkOrder::whereKey($request->work_order_id)->whereNotNull('customer_accepted_at')->exists()) {
            $next = ApprovalRequest::withoutGlobalScopes()->where('tenant_id', $request->tenant_id)
                ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])->where('entity_id', $request->id)
                ->where('step_order', '>', $last->step_order)->orderBy('step_order')->firstOrFail();
            $owner = $this->owners->resolve($request, (string) $next->approval_role, (string) $next->action);
            $due = now()->addMinutes((int) $next->sla_minutes);
            $next->update(['status' => 'PENDING', 'assigned_user_id' => $owner['user_id'],
                'routing_status' => $owner['status'], 'due_at' => $due]);
            $request->update(['workflow_stage' => $next->action,
                'assigned_department' => data_get($next->metadata, 'department'),
                'approval_state' => 'PENDING', 'next_action' => $next->action,
                'current_stage_due_at' => $due, 'status' => 'OPEN', 'resolved_at' => null]);
            $this->ensureStageArtifacts($request->fresh());
        }

        return true;
    }

    private function ensureStageArtifacts(ServiceRequest $request): void
    {
        if ($request->workflow_stage === 'EXECUTION' && ! $request->work_order_id && $request->asset_id) {
            $this->assertExecutionAssetOwnership($request);
            $reference = str_starts_with((string) $request->request_no, 'SR-')
                ? substr((string) $request->request_no, 3)
                : (string) $request->request_no;
            $workOrder = WorkOrder::firstOrCreate([
                'tenant_id' => $request->tenant_id,
                'work_order_no' => 'WO-'.$reference,
            ], [
                'organization_id' => $request->organization_id,
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
                'control_account_code' => ChartAccount::where('tenant_id',$request->tenant_id)
                    ->where('code','AR')->where('status','ACTIVE')->where('posting_allowed',true)->exists() ? 'AR' : '1200',
                'offset_account_code' => ChartAccount::where('tenant_id',$request->tenant_id)
                    ->where('code','REV')->where('status','ACTIVE')->where('posting_allowed',true)->exists() ? 'REV' : '4100',
                'status' => 'DRAFT',
            ]);
            $context['invoice_id'] = $invoice->id;
            $context['invoice_no'] = $invoice->document_no;
            $request->update(['workflow_context' => $context]);
        }
    }

    private function assertExecutionAssetOwnership(ServiceRequest $request): void
    {
        if (! Asset::query()->whereKey($request->asset_id)
            ->where('tenant_id', $request->tenant_id)
            ->where('customer_id', $request->customer_id)->exists()) {
            throw ValidationException::withMessages([
                'asset_id' => 'The request asset must belong to the request customer before a work order can be created.',
            ]);
        }

        $reference = str_starts_with((string) $request->request_no, 'SR-')
            ? substr((string) $request->request_no, 3) : (string) $request->request_no;
        $existing = $request->work_order_id
            ? WorkOrder::where('tenant_id', $request->tenant_id)->find($request->work_order_id)
            : WorkOrder::where('tenant_id', $request->tenant_id)->where('work_order_no', 'WO-'.$reference)->first();
        if (($request->work_order_id && ! $existing)
            || ($existing && (int) $existing->asset_id !== (int) $request->asset_id)) {
            throw ValidationException::withMessages([
                'work_order_id' => 'The request work order and customer asset must be reconciled before execution.',
            ]);
        }
    }

    public function returnTo(ServiceRequest $request, string $targetStage, ?int $actorId = null, ?string $note = null): ApprovalRequest
    {
        $request->refresh();
        $before = $this->state($request);
        $fromStage = (string) $request->workflow_stage;
        $steps = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])
            ->where('entity_id', $request->id)
            ->orderBy('step_order')
            ->get();
        $target = $steps->firstWhere('action', $targetStage);
        abort_unless($target, 422, 'The requested return stage is not part of this workflow.');

        foreach ($steps as $step) {
            if ((int) $step->step_order < (int) $target->step_order) continue;
            $owner = $this->owners->resolve($request->fresh(),(string)$step->approval_role,(string)$step->action);
            $step->update([
                'status' => $step->id === $target->id ? 'PENDING' : 'WAITING',
                'assigned_user_id' => $owner['user_id'],
                'routing_status' => $owner['status'],
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
        $request->refresh();
        $this->recordTransition($request, $before, $fromStage, (string) $target->action, $actorId, $note, ['transition_type' => 'RETURN']);

        return $target->fresh();
    }

    public function returnToPrevious(ServiceRequest $request, string $currentStage, ?int $actorId = null, ?string $note = null): ApprovalRequest
    {
        $current = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])
            ->where('entity_id', $request->id)
            ->where('action', $currentStage)
            ->firstOrFail();
        $previous = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])
            ->where('entity_id', $request->id)
            ->where('step_order', '<', $current->step_order)
            ->orderByDesc('step_order')
            ->first();

        abort_unless($previous, 422, 'This workflow has no previous stage to return to.');
        return $this->returnTo($request, $previous->action, $actorId, $note);
    }

    public function reject(ServiceRequest $request, string $currentStage, ?int $actorId = null, ?string $note = null): void
    {
        $request->refresh();
        $before = $this->state($request);
        $current = ApprovalRequest::query()
            ->where('tenant_id', $request->tenant_id)
            ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])
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
                ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])
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
        $request->refresh();
        $this->recordTransition($request, $before, $currentStage, 'REJECTED', $actorId, $note, ['transition_type' => 'REJECT']);
    }

    private function recordTransition(ServiceRequest $request, array $before, string $from, string $to, ?int $actorId, ?string $note, array $metadata = []): void
    {
        $this->audit->record(
            'SERVICE_REQUEST_WORKFLOW_TRANSITION',
            $request,
            $before,
            $this->state($request),
            reason: $note,
            metadata: $metadata + [
                'actor_id' => $actorId,
                'previous_stage' => $from,
                'next_stage' => $to,
                'work_order_id' => $request->work_order_id,
                'quotation_id' => $request->quotation_id,
            ]
        );
    }

    private function state(ServiceRequest $request): array
    {
        return [
            'status' => $request->status,
            'workflow_key' => $request->workflow_key,
            'workflow_stage' => $request->workflow_stage,
            'approval_state' => $request->approval_state,
            'next_action' => $request->next_action,
            'assigned_department' => $request->assigned_department,
            'assigned_engineer_id' => $request->assigned_engineer_id,
            'service_contract_id' => $request->service_contract_id,
            'eligibility' => $request->eligibility,
            'work_order_id' => $request->work_order_id,
            'quotation_id' => $request->quotation_id,
        ];
    }

    private function slaFor(string $stage): int
    {
        return self::SLA[$stage] ?? 240;
    }
}
