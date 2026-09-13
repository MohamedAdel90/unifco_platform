<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const ROLE = 'OPERATIONS_MANAGER';

    private const ALLOW = [
        'operations.dashboard.view',
        'crm.customer.read',
        'maintenance.work_order.read',
        'maintenance.work_order.manage',
        'maintenance.work_order.assign',
        'eam.asset.read',
        'inventory.stock.read',
        'projects.project.read',
        'reporting.executive.read',
        'service_requests.read',
        'service_requests.assign',
        'service_requests.escalate',
        'field.operations.read',
    ];

    private const DENY = [
        'system.dashboard.view',
        'users.view', 'users.create', 'users.edit', 'roles.manage', 'scopes.manage',
        'security.permission.manage', 'audit.read',
        'finance.journal.post', 'invoice.approve', 'procurement.po.approve', 'purchase.approve',
        'contract.approve', 'quotation.approve', 'payroll.edit',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('role_permissions')) return;

        DB::table('roles')->updateOrInsert(
            ['tenant_id' => null, 'code' => self::ROLE],
            [
                'name_en' => 'Operations Manager',
                'name_ar' => 'مدير العمليات',
                'description' => 'Operational command role for scoped service delivery, work orders, sites and escalations. No system, finance or contractual authority by default.',
                'is_system_role' => false,
                'grants_business_authority' => true,
                'requires_approval' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $roleId = DB::table('roles')->whereNull('tenant_id')->where('code', self::ROLE)->value('id');

        foreach (array_unique(array_merge(self::ALLOW, self::DENY)) as $code) {
            [$module, $action] = array_pad(explode('.', $code, 2), 2, 'access');
            $business = preg_match('/(approve|post|payroll)/i', $code) === 1;
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'module' => $module,
                'action' => $action,
                'risk_level' => $business ? 'HIGH' : 'NORMAL',
                'is_business_authority' => $business,
                'is_scope_aware' => !in_array($module, ['system','users','roles','scopes','security','audit'], true),
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (self::ALLOW as $code) $this->grant($roleId, $code, 'ALLOW');
        foreach (self::DENY as $code) $this->grant($roleId, $code, 'DENY');
    }

    private function grant(int $roleId, string $code, string $effect): void
    {
        $permissionId = DB::table('permissions')->where('code', $code)->value('id');
        $values = [
            'tenant_id' => null,
            'role_code' => self::ROLE,
            'permission_code' => $code,
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'effect' => $effect,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('role_permissions', 'created_at')) $values['created_at'] = now();

        DB::table('role_permissions')->updateOrInsert(
            ['tenant_id' => null, 'role_code' => self::ROLE, 'permission_code' => $code],
            $values
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('role_permissions')) return;
        $roleId = DB::table('roles')->whereNull('tenant_id')->where('code', self::ROLE)->value('id');
        DB::table('role_permissions')->where('role_code', self::ROLE)->orWhere('role_id', $roleId)->delete();
        DB::table('roles')->whereNull('tenant_id')->where('code', self::ROLE)->delete();
    }
};
