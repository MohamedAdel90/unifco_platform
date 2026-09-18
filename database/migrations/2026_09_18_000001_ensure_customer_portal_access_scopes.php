<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Best-practice alignment with the access-control model: every CUSTOMER-portal
     * user must hold a CUSTOMER access scope for their own customer record.
     *
     * The RuntimeDataScope global scope (ScopeEnforcementServiceProvider) funnels
     * operational queries through ScopeService::apply, which fail-closes (returns
     * nothing) for governed users without user_scopes rows. Customer-portal data
     * lookup then returns 404 for any customer account created outside the
     * new provisioning path. This backfills the CUSTOMER scope so customer
     * logins keep their own portal data visible end-to-end.
     */
    public function up(): void
    {
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasTable('access_scopes')
            || ! Schema::hasTable('user_scopes')
        ) {
            return;
        }

        DB::transaction(function (): void {
            $users = DB::table('users')
                ->where('role', 'CUSTOMER')
                ->whereNotNull('customer_id')
                ->get(['id', 'tenant_id', 'customer_id']);

            $now = now();

            foreach ($users as $user) {
                if (! $user->customer_id) {
                    continue;
                }

                $scopeId = DB::table('access_scopes')
                    ->where('tenant_id', $user->tenant_id)
                    ->where('scope_type', 'CUSTOMER')
                    ->where('scope_id', $user->customer_id)
                    ->value('id');

                if (! $scopeId) {
                    $scopeId = DB::table('access_scopes')->insertGetId([
                        'tenant_id' => $user->tenant_id,
                        'scope_type' => 'CUSTOMER',
                        'scope_id' => $user->customer_id,
                        'name' => 'CUSTOMER #'.$user->customer_id,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('user_scopes')->updateOrInsert(
                    ['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'access_scope_id' => $scopeId],
                    [
                        'source' => 'MIGRATION', 'granted_by' => null, 'expires_at' => null,
                        'reason' => 'Backfill customer portal access scope', 'created_at' => $now, 'updated_at' => $now,
                    ]
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_scopes')) {
            return;
        }

        DB::table('user_scopes')->where('reason', 'Backfill customer portal access scope')->delete();
    }
};