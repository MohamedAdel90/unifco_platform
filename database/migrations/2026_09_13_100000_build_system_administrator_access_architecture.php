<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    private const SYSTEM_ADMIN_PERMISSIONS = [
        'system.dashboard.view',
        'users.view', 'users.create', 'users.edit', 'users.activate', 'users.deactivate',
        'users.reset_password', 'users.force_logout', 'users.unlock',
        'roles.view', 'roles.manage', 'roles.assign',
        'scopes.view', 'scopes.manage',
        'master_data.manage', 'notifications.manage', 'integrations.manage', 'scheduled_jobs.manage',
        'security.events.view', 'sessions.view', 'sessions.manage', 'audit.view',
        'invitations.manage', 'impersonation.read_only', 'approval_authorities.manage',
        'homepage.manage', 'system.configuration.manage', 'system.files.manage',
        // Compatibility with the existing administration routes.
        'audit.read', 'security.permission.manage',
    ];

    private const SYSTEM_ADMIN_DENIES = [
        'maintenance.work_order.execute', 'maintenance.work_order.complete',
        'service_requests.approve', 'quotation.approve', 'contract.approve',
        'invoice.approve', 'procurement.po.approve', 'purchase.approve',
        'payroll.edit', 'finance.journal.post', 'workflow.approval.decide',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name_ar', 160)->nullable()->after('name');
            $table->string('name_en', 160)->nullable()->after('name_ar');
            $table->string('mobile', 40)->nullable()->after('email');
            $table->string('user_type', 20)->default('INTERNAL')->after('role');
            $table->index(['tenant_id', 'user_type', 'status']);
        });
        DB::table('users')->whereNull('name_en')->update(['name_en' => DB::raw('name')]);
        DB::table('users')->whereNotNull('customer_id')->update(['user_type' => 'EXTERNAL']);

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name_en', 120);
            $table->string('name_ar', 120)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->boolean('grants_business_authority')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 140)->unique();
            $table->string('module', 80);
            $table->string('action', 80);
            $table->string('risk_level', 20)->default('NORMAL');
            $table->boolean('is_business_authority')->default(false);
            $table->boolean('is_scope_aware')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['module', 'action']);
        });

        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->after('tenant_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->nullable()->after('role_id')->constrained('permissions')->cascadeOnDelete();
            $table->string('effect', 10)->default('ALLOW')->after('permission_code');
            $table->foreignId('granted_by')->nullable()->after('effect')->constrained('users')->nullOnDelete();
            $table->index(['role_id', 'permission_id', 'effect'], 'role_permission_effect_idx');
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
            $table->index(['tenant_id', 'user_id', 'revoked_at']);
        });

        Schema::create('access_scopes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type', 40);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('name', 160)->nullable();
            $table->json('constraints')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'scope_type', 'scope_id'], 'access_scope_unique');
            $table->index(['tenant_id', 'scope_type', 'is_active']);
        });

        Schema::create('user_scopes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_scope_id')->constrained('access_scopes')->cascadeOnDelete();
            $table->string('source', 20)->default('USER');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'access_scope_id']);
            $table->index(['tenant_id', 'user_id', 'expires_at']);
        });

        Schema::table('user_permission_overrides', function (Blueprint $table): void {
            $table->string('reason', 500)->nullable()->after('updated_by');
            $table->timestamp('expires_at')->nullable()->after('reason');
            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('user_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 120)->unique();
            $table->timestamp('login_at');
            $table->timestamp('last_activity_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 120)->nullable();
            $table->string('browser', 120)->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoke_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'user_id', 'status']);
            $table->index(['last_activity_at', 'status']);
        });

        Schema::create('user_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email', 190);
            $table->string('token_hash', 64)->unique();
            $table->string('status', 20)->default('PENDING');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'email', 'status']);
        });

        Schema::create('security_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('severity', 20)->default('INFO');
            $table->string('status', 20)->default('OPEN');
            $table->string('ip_address', 45)->nullable();
            $table->string('session_id', 120)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'severity', 'status']);
            $table->index(['event_type', 'occurred_at']);
        });

        Schema::create('approval_authorities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('approval_type', 100);
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->unsignedInteger('level')->default(1);
            $table->foreignId('access_scope_id')->nullable()->constrained('access_scopes')->nullOnDelete();
            $table->decimal('amount_limit', 19, 2)->nullable();
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'approval_type', 'level', 'is_active'], 'approval_authority_lookup_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('correlation_id');
            $table->string('session_id', 120)->nullable()->after('ip_address');
            $table->string('reason', 500)->nullable()->after('session_id');
            $table->json('metadata')->nullable()->after('reason');
            $table->char('previous_hash', 64)->nullable()->after('after_state');
            $table->char('entry_hash', 64)->nullable()->after('previous_hash')->unique();
            $table->index(['tenant_id', 'action', 'created_at']);
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
        });

        $this->backfillPermissionCatalog();
        $this->backfillRolesAndAssignments();
        // Stop the compatibility column from granting legacy business-authority
        // shortcuts to migrated administrators. Structured roles are authoritative.
        DB::table('users')->where('role', 'ADMIN')->update(['role' => 'SYSTEM_ADMIN']);
        $this->seedSystemAdministratorBaseline();
        $this->backfillScopes();
    }

    private function backfillPermissionCatalog(): void
    {
        $codes = DB::table('role_permissions')->pluck('permission_code')
            ->merge(self::SYSTEM_ADMIN_PERMISSIONS)
            ->merge(self::SYSTEM_ADMIN_DENIES)
            ->filter()->unique()->values();

        foreach ($codes as $code) {
            [$module, $action] = array_pad(explode('.', $code, 2), 2, 'access');
            $business = preg_match('/(approve|decide|post|pay|execute|complete|payroll)/i', $code) === 1;
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'module' => $module,
                'action' => $action,
                'risk_level' => $business ? 'HIGH' : 'NORMAL',
                'is_business_authority' => $business,
                'is_scope_aware' => ! in_array($module, ['system', 'users', 'roles', 'scopes', 'audit', 'security', 'sessions', 'invitations'], true),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function backfillRolesAndAssignments(): void
    {
        foreach (['SYSTEM_ADMIN','MANAGER','SUPERVISOR','TECHNICIAN','STOREKEEPER','CUSTOMER_ADMIN','CUSTOMER_SITE_MANAGER','CUSTOMER_FINANCE','CUSTOMER_VIEWER'] as $code) {
            DB::table('roles')->updateOrInsert(['tenant_id' => null, 'code' => $code], [
                'name_en' => Str::headline(strtolower($code)), 'name_ar' => $code === 'SYSTEM_ADMIN' ? 'مدير النظام' : null,
                'is_system_role' => $code === 'SYSTEM_ADMIN', 'grants_business_authority' => $code !== 'SYSTEM_ADMIN',
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $roleRows = DB::table('role_permissions')->select('tenant_id', 'role_code')
            ->union(DB::table('users')->select('tenant_id', 'role as role_code'))
            ->get();

        foreach ($roleRows as $row) {
            $legacyCode = strtoupper((string) $row->role_code);
            $code = $legacyCode === 'ADMIN' ? 'SYSTEM_ADMIN' : $legacyCode;
            $tenantId = $row->tenant_id;
            DB::table('roles')->updateOrInsert(['tenant_id' => $tenantId, 'code' => $code], [
                'name_en' => Str::headline(strtolower($code)),
                'name_ar' => $code === 'SYSTEM_ADMIN' ? 'مدير النظام' : null,
                'is_system_role' => $code === 'SYSTEM_ADMIN',
                'grants_business_authority' => $code !== 'SYSTEM_ADMIN',
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('role_permissions')->orderBy('id')->each(function ($row): void {
            $code = strtoupper((string) $row->role_code) === 'ADMIN' ? 'SYSTEM_ADMIN' : strtoupper((string) $row->role_code);
            $role = DB::table('roles')->where('code', $code)
                ->where(fn ($query) => $row->tenant_id === null ? $query->whereNull('tenant_id') : $query->where('tenant_id', $row->tenant_id))
                ->first();
            $permissionId = DB::table('permissions')->where('code', $row->permission_code)->value('id');
            if ($role && $permissionId) DB::table('role_permissions')->where('id', $row->id)->update(['role_id' => $role->id, 'permission_id' => $permissionId]);
        });

        DB::table('users')->orderBy('id')->each(function ($user): void {
            $legacyCode = strtoupper((string) $user->role);
            $code = $legacyCode === 'ADMIN' ? 'SYSTEM_ADMIN' : $legacyCode;
            if ($legacyCode === 'CUSTOMER') {
                $portal = strtoupper((string) ($user->customer_portal_role ?: 'CUSTOMER_ADMIN'));
                $code = match ($portal) {
                    'SITE_MANAGER' => 'CUSTOMER_SITE_MANAGER',
                    'FINANCE' => 'CUSTOMER_FINANCE',
                    'VIEWER' => 'CUSTOMER_VIEWER',
                    default => 'CUSTOMER_ADMIN',
                };
                DB::table('roles')->updateOrInsert(['tenant_id' => $user->tenant_id, 'code' => $code], [
                    'name_en' => Str::headline(strtolower($code)), 'is_system_role' => false,
                    'grants_business_authority' => true, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $roleId = DB::table('roles')->where('tenant_id', $user->tenant_id)->where('code', $code)->value('id')
                ?: DB::table('roles')->whereNull('tenant_id')->where('code', $code)->value('id');
            if ($roleId) DB::table('user_roles')->updateOrInsert(['user_id' => $user->id, 'role_id' => $roleId], [
                'tenant_id' => $user->tenant_id, 'is_primary' => true, 'granted_at' => now(),
                'reason' => 'Automatic migration from users.role', 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    private function seedSystemAdministratorBaseline(): void
    {
        $roles = DB::table('roles')->where('code', 'SYSTEM_ADMIN')->get();
        foreach ($roles as $role) {
            // Start from a strict system-authority-only baseline. Business permissions
            // are absent (default deny), so a separate business role can grant them.
            DB::table('role_permissions')->where('role_id', $role->id)->delete();
            foreach (self::SYSTEM_ADMIN_PERMISSIONS as $code) $this->setRolePermission($role, $code, 'ALLOW');
        }
    }

    private function setRolePermission(object $role, string $code, string $effect): void
    {
        $permissionId = DB::table('permissions')->where('code', $code)->value('id');
        DB::table('role_permissions')->updateOrInsert(
            ['tenant_id' => $role->tenant_id, 'role_code' => $role->code, 'permission_code' => $code],
            ['role_id' => $role->id, 'permission_id' => $permissionId, 'effect' => $effect, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function backfillScopes(): void
    {
        DB::table('tenants')->orderBy('id')->each(function ($tenant): void {
            $globalId = DB::table('access_scopes')->insertGetId([
                'tenant_id' => $tenant->id, 'scope_type' => 'GLOBAL', 'scope_id' => null,
                'name' => 'Global', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('users')->where('tenant_id', $tenant->id)->whereNull('customer_id')->orderBy('id')->each(function ($user) use ($globalId): void {
                DB::table('user_scopes')->updateOrInsert(['user_id' => $user->id, 'access_scope_id' => $globalId], [
                    'tenant_id' => $user->tenant_id, 'source' => 'MIGRATION', 'reason' => 'Preserve existing internal access',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        });

        if (Schema::hasTable('customer_portal_user_scopes')) {
            DB::table('customer_portal_user_scopes')->orderBy('id')->each(function ($legacy): void {
                $user = DB::table('users')->where('id', $legacy->user_id)->first();
                if (! $user) return;
                $scopeKey = ['tenant_id' => $user->tenant_id, 'scope_type' => strtoupper($legacy->scope_type), 'scope_id' => $legacy->scope_id];
                DB::table('access_scopes')->updateOrInsert($scopeKey, [
                    'name' => strtoupper($legacy->scope_type).' #'.$legacy->scope_id, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $scopeId = DB::table('access_scopes')->where($scopeKey)->value('id');
                DB::table('user_scopes')->updateOrInsert(['user_id' => $user->id, 'access_scope_id' => $scopeId], [
                    'tenant_id' => $user->tenant_id, 'source' => 'MIGRATION', 'reason' => 'Migrated from customer portal scope',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'action', 'created_at']);
            $table->dropIndex(['tenant_id', 'entity_type', 'entity_id']);
            $table->dropUnique(['entry_hash']);
            $table->dropColumn(['ip_address', 'session_id', 'reason', 'metadata', 'previous_hash', 'entry_hash']);
        });
        Schema::dropIfExists('approval_authorities');
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('user_invitations');
        Schema::dropIfExists('user_sessions');
        Schema::table('user_permission_overrides', fn (Blueprint $table) => $table->dropColumn(['reason', 'expires_at']));
        Schema::dropIfExists('user_scopes');
        Schema::dropIfExists('access_scopes');
        Schema::dropIfExists('user_roles');
        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->dropForeign(['role_id']); $table->dropForeign(['permission_id']); $table->dropForeign(['granted_by']);
            $table->dropColumn(['role_id', 'permission_id', 'effect', 'granted_by']);
        });
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'user_type', 'status']);
            $table->dropColumn(['name_ar', 'name_en', 'mobile', 'user_type']);
        });
    }
};
