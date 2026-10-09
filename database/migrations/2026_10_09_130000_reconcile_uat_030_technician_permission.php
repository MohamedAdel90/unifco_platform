<?php

use App\Models\{ApprovalRequest,Employee,ProjectUserAssignment,ServiceRequest,User,WorkOrder,WorkOrderAssignment};
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function (): void {
            $request = ServiceRequest::withoutGlobalScopes()
                ->whereKey(32)->where('request_no', 'SR-UNUM-926000030')
                ->where('workflow_stage', 'EXECUTION')->where('status', 'OPEN')
                ->where('assigned_engineer_id', 18)->where('work_order_id', 19)
                ->whereNotNull('project_id')->lockForUpdate()->first();
            if (! $request) return;

            $order = WorkOrder::withoutGlobalScopes()
                ->whereKey(19)->where('tenant_id', $request->tenant_id)
                ->where('status', 'OPEN')->first();
            $technician = User::withoutGlobalScopes()
                ->whereKey(18)->where('tenant_id', $request->tenant_id)
                ->where('email', 'technician@unifco.local')
                ->where('role', 'TECHNICIAN')->where('status', 'ACTIVE')
                ->whereNull('locked_at')->first();
            if (! $order || ! $technician) return;

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
            if (! $member || ! $approval) return;

            // The UAT role's existing ALLOW grant was seeded without the role_id
            // required by structured user_roles. Preserve any explicit DENY.
            $roleId = DB::table('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('user_roles.tenant_id', $request->tenant_id)
                ->where('user_roles.user_id', $technician->id)
                ->whereNull('user_roles.revoked_at')
                ->where('roles.code', 'TECHNICIAN')->where('roles.is_active', true)
                ->value('roles.id');
            $grant = DB::table('role_permissions')
                ->where('tenant_id', $request->tenant_id)
                ->where('role_code', 'TECHNICIAN')
                ->where('permission_code', 'maintenance.work_order.manage')
                ->where('effect', 'ALLOW')->first();
            $denied = DB::table('role_permissions')
                ->where('tenant_id', $request->tenant_id)
                ->where('permission_code', 'maintenance.work_order.manage')
                ->where('effect', 'DENY')
                ->whereIn('role_id', DB::table('user_roles')
                    ->where('user_id', $technician->id)->whereNull('revoked_at')->pluck('role_id'))
                ->exists();
            if ($roleId && $grant && ! $denied && ! $grant->role_id) {
                DB::table('role_permissions')->where('id', $grant->id)
                    ->whereNull('role_id')->update(['role_id' => $roleId, 'updated_at' => now()]);
            }

            // Reuse the dedicated test employee identity if deployment never
            // ran the optional workflow seeder. Do not replace another link.
            if (! $technician->employee_id) {
                $employee = Employee::withoutGlobalScopes()->firstOrCreate(
                    ['tenant_id' => $request->tenant_id, 'employee_no' => 'WF-TECH-001'],
                    ['organization_id' => $request->organization_id,
                     'name' => 'Workflow Technician', 'email' => 'technician@unifco.local',
                     'hire_date' => today(), 'status' => 'ACTIVE']
                );
                if ($employee->status !== 'ACTIVE'
                    || $employee->email !== 'technician@unifco.local') return;
                $technician->update(['employee_id' => $employee->id]);
            }
            $employee = Employee::withoutGlobalScopes()
                ->whereKey($technician->employee_id)->where('tenant_id', $request->tenant_id)
                ->where('status', 'ACTIVE')->first();
            if (! $employee) return;

            WorkOrderAssignment::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $request->tenant_id, 'work_order_id' => $order->id,
                 'employee_id' => $employee->id],
                ['organization_id' => $request->organization_id,
                 'scheduled_start' => now(), 'dispatch_status' => 'DISPATCHED',
                 'dispatched_at' => now(),
                 'dispatcher_notes' => 'UAT 030: reconcile workflow technician dispatch']
            );
        });
    }

    public function down(): void
    {
        // Do not silently revoke an active permission or dispatch on rollback.
    }
};
