<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthorizationService
{
    public function __construct(private ScopeService $scopes) {}

    public function roleCodes(User $user): Collection
    {
        if (Schema::hasTable('user_roles') && DB::table('user_roles')->where('user_id', $user->id)->whereNull('revoked_at')->exists()) {
            return DB::table('user_roles')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('user_roles.tenant_id', $user->tenant_id)->where('user_roles.user_id', $user->id)
                ->whereNull('user_roles.revoked_at')->where('roles.is_active', true)
                ->pluck('roles.code')->map(fn ($code) => strtoupper((string) $code))->unique()->values();
        }
        return collect([strtoupper((string) $user->role)]);
    }

    public function effectivePermissions(User $user): Collection
    {
        if ($user->status !== 'ACTIVE' || $user->locked_at) return collect();
        $rows = $this->permissionRows($user, $this->roleCodes($user));
        $denied = $rows->where('effect', 'DENY')->pluck('permission_code');
        $allowed = $rows->where('effect', 'ALLOW')->pluck('permission_code')->reject(fn ($code) => $denied->contains($code));

        if (Schema::hasTable('user_permission_overrides')) {
            $overrides = DB::table('user_permission_overrides')->where('tenant_id', $user->tenant_id)->where('user_id', $user->id)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->get();
            $overrideDenies = $overrides->where('allowed', false)->pluck('permission_code');
            $allowed = $allowed->merge($overrides->where('allowed', true)->pluck('permission_code'))
                ->reject(fn ($code) => $denied->contains($code) || $overrideDenies->contains($code));
        }
        return $allowed->unique()->sort()->values();
    }

    public function allows(User $user, string $permission, Model|array|null $resource = null): bool
    {
        if ($user->status !== 'ACTIVE' || $user->locked_at) return false;
        $roles = $this->roleCodes($user);
        // Temporary bridge for records written by legacy integrations before user_roles sync.
        if ($roles->count() === 1 && $roles->first() === 'ADMIN' && ! $this->hasStructuredAssignment($user)) return true;

        $rows = $this->permissionRows($user, $roles, $permission);
        if ($rows->contains(fn ($row) => strtoupper((string) $row->effect) === 'DENY')) return false;

        $override = Schema::hasTable('user_permission_overrides')
            ? DB::table('user_permission_overrides')->where('tenant_id', $user->tenant_id)->where('user_id', $user->id)
                ->where('permission_code', $permission)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->first()
            : null;
        if ($override && ! $override->allowed) return false;

        $allowed = ($override && $override->allowed)
            || $rows->contains(fn ($row) => strtoupper((string) $row->effect) === 'ALLOW');
        return $allowed && ($resource === null || $this->scopes->allows($user, $resource));
    }

    public function authorize(User $user, string $permission, Model|array|null $resource = null): void
    {
        abort_unless($this->allows($user, $permission, $resource), 403, 'Permission or scope denied.');
    }

    private function hasStructuredAssignment(User $user): bool
    {
        return Schema::hasTable('user_roles') && DB::table('user_roles')->where('user_id', $user->id)->whereNull('revoked_at')->exists();
    }

    private function permissionRows(User $user, Collection $roles, ?string $permission = null): Collection
    {
        $query = DB::table('role_permissions')->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $user->tenant_id));
        if (Schema::hasColumn('role_permissions', 'role_id') && $this->hasStructuredAssignment($user)) {
            $query->whereIn('role_id', DB::table('user_roles')->where('user_id', $user->id)->whereNull('revoked_at')->pluck('role_id'));
        } else {
            $query->whereIn('role_code', $roles);
        }
        if ($permission !== null) $query->where('permission_code', $permission);
        return $query->get(['permission_code', Schema::hasColumn('role_permissions', 'effect') ? 'effect' : DB::raw("'ALLOW' as effect")]);
    }
}
