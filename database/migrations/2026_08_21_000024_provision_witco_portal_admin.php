<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $customer = DB::table('customers')
            ->whereRaw('LOWER(name) = ?', ['witco'])
            ->first();

        if (! $customer) {
            $customer = DB::table('customers')->where('id', 5)->first();
        }

        if (! $customer) {
            return;
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'portal@witco.local'],
            [
                'tenant_id' => $customer->tenant_id,
                'organization_id' => $customer->organization_id,
                'customer_id' => $customer->id,
                'employee_id' => null,
                'name' => 'WITCO Portal Administrator',
                'password' => '$2y$12$Oi0s3INM9aSdTjB4v.DTXuR.w/Dnm6E7Jflpn3F5JxAke5GzmKK0O',
                'role' => 'CUSTOMER',
                'customer_portal_role' => 'CUSTOMER_ADMIN',
                'status' => 'ACTIVE',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'portal@witco.local')->delete();
    }
};
