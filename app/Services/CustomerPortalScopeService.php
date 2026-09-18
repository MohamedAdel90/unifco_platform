<?php

namespace App\Services;

use App\Models\{AccessScope, User};
use Illuminate\Support\Facades\DB;

class CustomerPortalScopeService
{
    /**
     * Grants a customer-portal user an active CUSTOMER access scope tied to
     * their own customer record. This keeps the customer's data visible through
     * the RuntimeDataScope enforcement boundary (ScopeService::apply) instead of
     * falling into the fail-closed "no scopes" path that returns nothing.
     */
    public function grant(User $user, ?int $grantedBy = null): void
    {
        if (! $user->customer_id) {
            return;
        }

        $scope = AccessScope::firstOrCreate(
            ['tenant_id' => $user->tenant_id, 'scope_type' => 'CUSTOMER', 'scope_id' => $user->customer_id],
            ['name' => 'CUSTOMER #'.$user->customer_id, 'is_active' => true]
        );

        DB::table('user_scopes')->updateOrInsert(
            ['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'access_scope_id' => $scope->id],
            [
                'source' => 'USER', 'granted_by' => $grantedBy, 'expires_at' => null,
                'reason' => 'Customer portal access scope', 'created_at' => now(), 'updated_at' => now(),
            ]
        );
    }
}