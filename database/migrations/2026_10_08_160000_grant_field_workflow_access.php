<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const GRANTS = [
        'TECHNICAL_SUPERVISOR' => ['dashboard.view', 'workflow.approval.read', 'workflow.approval.decide'],
        'TECHNICIAN' => ['dashboard.view', 'workflow.approval.read'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) return;

        foreach (self::GRANTS as $roleCode => $permissionCodes) {
            $role = DB::table('roles')->whereNull('tenant_id')->where('code', $roleCode)->first();
            if (! $role) continue;

            foreach ($permissionCodes as $permissionCode) {
                $permission = DB::table('permissions')->where('code', $permissionCode)->first();
                if (! $permission) continue;

                DB::table('role_permissions')->updateOrInsert(
                    ['tenant_id' => null, 'role_code' => $roleCode, 'permission_code' => $permissionCode],
                    [
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                        'effect' => 'ALLOW',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        foreach (self::GRANTS as $roleCode => $permissionCodes) {
            DB::table('role_permissions')->whereNull('tenant_id')->where('role_code', $roleCode)
                ->whereIn('permission_code', $permissionCodes)->delete();
        }
    }
};
