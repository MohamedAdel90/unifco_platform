<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_integrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name', 160);
            $table->string('category', 60)->default('PLATFORM');
            $table->string('provider', 120)->nullable();
            $table->string('status', 20)->default('UNKNOWN');
            $table->boolean('is_enabled')->default(true);
            $table->string('endpoint_host', 255)->nullable();
            $table->unsignedInteger('response_ms')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status', 'is_enabled']);
        });

        $permissionCodes = ['login_activity.view', 'integrations.view', 'scope.audit.view'];
        foreach ($permissionCodes as $code) {
            [$module, $action] = explode('.', $code, 2);
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'module' => $module,
                'action' => $action,
                'risk_level' => 'NORMAL',
                'is_business_authority' => false,
                'is_scope_aware' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $permissionId = DB::table('permissions')->where('code', $code)->value('id');
            foreach (DB::table('roles')->where('code', 'SYSTEM_ADMIN')->get() as $role) {
                DB::table('role_permissions')->updateOrInsert(
                    ['tenant_id' => $role->tenant_id, 'role_code' => $role->code, 'permission_code' => $code],
                    ['role_id' => $role->id, 'permission_id' => $permissionId, 'effect' => 'ALLOW', 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $defaults = [
            ['DATABASE', 'Database', 'CORE'],
            ['MAIL', 'Email Provider', 'COMMUNICATION'],
            ['QUEUE', 'Queue Worker', 'AUTOMATION'],
            ['SCHEDULER', 'Task Scheduler', 'AUTOMATION'],
            ['STORAGE', 'File Storage', 'CORE'],
            ['API_ACCESS', 'API Access', 'INTEGRATION'],
        ];
        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach ($defaults as [$code, $name, $category]) {
                DB::table('system_integrations')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'code' => $code],
                    ['name' => $name, 'category' => $category, 'status' => 'UNKNOWN', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionCodes = ['login_activity.view', 'integrations.view', 'scope.audit.view'];
        DB::table('role_permissions')->whereIn('permission_code', $permissionCodes)->delete();
        DB::table('permissions')->whereIn('code', $permissionCodes)->delete();
        Schema::dropIfExists('system_integrations');
    }
};
