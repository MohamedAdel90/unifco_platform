<?php

namespace App\Services;

use App\Models\{ApprovalRequest,ServiceRequest,User};
use Illuminate\Support\Collection;

class ServiceRequestWorkflowService
{
    public const SLA = [
        'TECHNICAL_REVIEW' => 120,
        'MAINTENANCE_MANAGER' => 120,
        'PROCUREMENT' => 240,
        'TENDERS_CONTRACTS' => 240,
        'FINANCE' => 120,
        'PROJECT_MANAGER' => 120,
        'CEO' => 240,
    ];

    public function start(ServiceRequest $request, array $context = []): Collection
    {
        $type = strtoupper((string) ($request->request_type ?: 'MAINTENANCE'));
        $value = (float) ($context['estimated_value'] ?? 0);
        $margin = array_key_exists('margin_pct', $context) && $context['margin_pct'] !== null ? (float) $context['margin_pct'] : null;
        $paymentDays = (int) ($context['payment_terms_days'] ?? 0);
        $risk = strtoupper((string) ($context['risk_level'] ?? 'NORMAL'));
        $procurement = (bool) ($context['procurement_required'] ?? $request->procurement_required);
        $inContract = ($request->eligibility === 'IN_CONTRACT');
        $emergency = strtoupper((string) $request->priority) === 'EMERGENCY';

        $steps = [['TECHNICAL_REVIEW','MAINTENANCE_ENGINEER']];

        if ($type === 'MAINTENANCE') {
            if (! $inContract || $procurement || $risk !== 'NORMAL') $steps[] = ['MAINTENANCE_MANAGER','MAINTENANCE_MANAGER'];
            if ($procurement) $steps[] = ['PROCUREMENT_COST_VALIDATION','PROCUREMENT'];
            if (! $inContract) $steps[] = ['COMMERCIAL_PREPARATION','TENDERS_CONTRACTS'];
        } elseif ($type === 'CONSULTATION') {
            $steps[] = ['TECHNICAL_SCOPE_APPROVAL','MAINTENANCE_MANAGER'];
            if (($context['paid'] ?? false) === true) $steps[] = ['COMMERCIAL_PREPARATION','TENDERS_CONTRACTS'];
        } else {
            $steps[] = ['TECHNICAL_SCOPE_APPROVAL','MAINTENANCE_MANAGER'];
            if ($procurement) $steps[] = ['PROCUREMENT_COST_VALIDATION','PROCUREMENT'];
            $steps[] = ['COMMERCIAL_PREPARATION','TENDERS_CONTRACTS'];
            if ($paymentDays > 30) $steps[] = ['FINANCIAL_TERMS_APPROVAL','FINANCE'];
            if ($value > 25000 || $risk === 'HIGH') $steps[] = ['EXECUTION_FEASIBILITY','PROJECT_MANAGER'];
            if ($value > 250000 || ($margin !== null && $margin < 10) || $risk === 'HIGH' || $paymentDays > 90) $steps[] = ['EXECUTIVE_APPROVAL','CEO'];
        }

        if ($emergency && $type === 'MAINTENANCE') {
            $steps = [['TECHNICAL_REVIEW','MAINTENANCE_ENGINEER']];
        }

        $requester = User::where('tenant_id', $request->tenant_id)
            ->where('role', '!=', 'CUSTOMER')
            ->whereIn('status', ['ACTIVE','ENABLED'])
            ->orderByRaw("CASE WHEN role='ADMIN' THEN 0 ELSE 1 END")
            ->first();

        $request->update([
            'workflow_stage' => 'TECHNICAL_REVIEW',
            'workflow_started_at' => $request->workflow_started_at ?: now(),
            'current_stage_due_at' => now()->addMinutes(self::SLA['TECHNICAL_REVIEW']),
        ]);

        if (! $requester) return collect();

        return collect($steps)->values()->map(function (array $step, int $index) use ($request, $requester, $context) {
            [$action, $role] = $step;
            $slaKey = match ($role) {
                'MAINTENANCE_ENGINEER' => 'TECHNICAL_REVIEW',
                'MAINTENANCE_MANAGER' => 'MAINTENANCE_MANAGER',
                'PROCUREMENT' => 'PROCUREMENT',
                'TENDERS_CONTRACTS' => 'TENDERS_CONTRACTS',
                'FINANCE' => 'FINANCE',
                'PROJECT_MANAGER' => 'PROJECT_MANAGER',
                'CEO' => 'CEO',
                default => 'TECHNICAL_REVIEW',
            };
            $sla = self::SLA[$slaKey];
            $status = $index === 0 ? 'PENDING' : 'WAITING';

            return ApprovalRequest::firstOrCreate([
                'tenant_id' => $request->tenant_id,
                'entity_type' => ServiceRequest::class,
                'entity_id' => $request->id,
                'action' => $action,
            ], [
                'organization_id' => $request->organization_id,
                'requested_by' => $requester->id,
                'workflow_key' => 'SERVICE_REQUEST_'.$request->request_type,
                'approval_role' => $role,
                'step_order' => $index + 1,
                'sla_minutes' => $sla,
                'status' => $status,
                'due_at' => $status === 'PENDING' ? now()->addMinutes($sla) : null,
                'metadata' => $context,
            ]);
        });
    }
}
