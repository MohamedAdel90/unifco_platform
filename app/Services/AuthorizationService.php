<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthorizationService
{
    public function allows(User $user, string $permission): bool
    {
        if ($user->status !== 'ACTIVE') return false;
        if ($user->role === 'ADMIN') return true;

        return DB::table('role_permissions')
            ->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $user->tenant_id))
            ->where('role_code', $user->role)
            ->where('permission_code', $permission)
            ->exists();
    }

    public function authorize(User $user, string $permission): void
    {
        abort_unless($this->allows($user, $permission), 403, 'Permission denied.');
    }
}
