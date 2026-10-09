<?php

use App\Models\{ApprovalRequest,Asset,Employee,ProjectUserAssignment,ServiceRequest,User,WorkOrder,WorkOrderAssignment};
use App\Services\AuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function (): void {
            // Reconcile only the observed UAT request while it is still awaiting
            // the same technician. Never dispatch a progressed or reassigned job.
            $request = ServiceRequest::withoutGlobalScopes()
                ->whereKey(32)->where('request_no', 'SR-UNUM-926000030')
                ->where('workflow_stage', 'EXECUTION')->where('status', 'OPEN')
                ->where('assigned_engineer_id', 18)->where('work_order_id', 19)
                ->whereNotNull('project_id')->lockForUpdate()->first();
            if (! $request) return;

            $order = WorkOrder::withoutGlobalScopes()
                ->where('tenant_id', $request->tenant_id)->whereKey(19)
                ->where('asset_id', $request->asset_id)->where('status', 'OPEN')->first();
            $asset = Asset::withoutGlobalScopes()
                ->where('tenant_id', $request->tenant_id)
                ->where('customer_id', $request->customer_id)->whereKey($request->asset_id)->first();
            $technician = User::withoutGlobalScopes()
                ->where('tenant_id', $request->tenant_id)->whereKey(18)
                ->where('email', 'technician@unifco.local')
                ->where('role', 'TECHNICIAN')->where('status', 'ACTIVE')
                ->whereNotNull('employee_id')->first();
            if (! $order || ! $asset || ! $technician) return;

            $employee = Employee::withoutGlobalScopes()
                ->where('tenant_id', $request->tenant_id)->whereKey($technician->employee_id)
                ->where('status', 'ACTIVE')->first();
            $member = ProjectUserAssignment::withoutGlobalScopes()
                ->where('tenant_id', $request->tenant_id)
                ->where('project_id', $request->project_id)->where('user_id', $technician->id)
                ->where('project_role', 'TECHNICIAN')->where('status', 'ACTIVE')
                ->where(fn ($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', today()))
                ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', today()))->exists();
            $approval = ApprovalRequest::withoutGlobalScopes()
                ->where('tenant_id', $request->tenant_id)
                ->whereIn('entity_type', [ServiceRequest::class, 'service_request'])
                ->where('entity_id', $request->id)->where('action', 'EXECUTION')
                ->where('status', 'PENDING')->where('assigned_user_id', $technician->id)->exists();
            if (! $employee || ! $member || ! $approval) return;

            $assignment = WorkOrderAssignment::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $request->tenant_id, 'work_order_id' => $order->id,
                 'employee_id' => $employee->id],
                ['organization_id' => $request->organization_id, 'scheduled_start' => now(),
                 'dispatch_status' => 'DISPATCHED', 'dispatched_at' => now(),
                 'dispatcher_notes' => 'UAT 030: reconcile the existing workflow technician assignment']
            );
            if ($assignment->wasRecentlyCreated) {
                app(AuditService::class)->record(
                    'WORK_ORDER_DISPATCH_RECONCILED', $assignment, [], $assignment->toArray(),
                    reason: 'UAT 030 execution owner had no matching field assignment.'
                );
            }
        });
    }

    public function down(): void
    {
        // Never silently remove a technician's active dispatch or audit evidence.
    }
};
