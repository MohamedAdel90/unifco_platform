<?php

use App\Models\{ApprovalRequest,ProjectUserAssignment,ServiceRequest,User};
use App\Services\AuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function (): void {
            // Repair the single UAT consultation assigned to an engineer by the
            // former selector although its SITE_VISIT approval requires TECHNICIAN.
            $request = ServiceRequest::withoutGlobalScopes()
                ->where('request_no', 'SR-UNC-926000034')
                ->where('customer_id', 15)->where('project_id', 4)
                ->where('workflow_key', 'TECHNICAL_CONSULTATION')
                ->where('workflow_stage', 'SITE_VISIT')
                ->where('assigned_engineer_id', 6)
                ->lockForUpdate()->first();
            if (! $request) return;

            $technician = User::withoutGlobalScopes()
                ->where('tenant_id', $request->tenant_id)->where('id', 18)
                ->where('email', 'technician@unifco.local')
                ->where('role', 'TECHNICIAN')->where('status', 'ACTIVE')->first();
            if (! $technician || ! ProjectUserAssignment::withoutGlobalScopes()
                ->where('tenant_id', $request->tenant_id)->where('project_id', $request->project_id)
                ->where('user_id', $technician->id)->where('project_role', 'TECHNICIAN')
                ->where('status', 'ACTIVE')
                ->where(fn ($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', today()))
                ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', today()))
                ->exists()) return;

            $approval = ApprovalRequest::withoutGlobalScopes()
                ->where('tenant_id', $request->tenant_id)
                ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])
                ->where('entity_id', $request->id)->where('action', 'SITE_VISIT')
                ->where('approval_role', 'TECHNICIAN')
                ->where('assigned_user_id', 6)->where('status', 'PENDING')
                ->lockForUpdate()->first();
            if (! $approval) return;

            $beforeRequest = ['assigned_engineer_id' => $request->assigned_engineer_id];
            $beforeApproval = ['assigned_user_id' => $approval->assigned_user_id, 'routing_status' => $approval->routing_status];
            $request->update(['assigned_engineer_id' => $technician->id]);
            $approval->update(['assigned_user_id' => $technician->id, 'routing_status' => 'ASSIGNED']);
            $audit = app(AuditService::class);
            $reason = 'UAT 034 role-mismatched site visit assignment corrected to the active project technician.';
            $audit->record('SERVICE_REQUEST_TECHNICIAN_ASSIGNMENT_CORRECTED', $request, $beforeRequest,
                ['assigned_engineer_id' => $technician->id], reason: $reason);
            $audit->record('WORKFLOW_STAGE_OWNER_CORRECTED', $approval, $beforeApproval,
                ['assigned_user_id' => $technician->id, 'routing_status' => 'ASSIGNED'], reason: $reason);
        });
    }

    public function down(): void
    {
        // A decided or progressed approval must never be silently rewound.
    }
};
