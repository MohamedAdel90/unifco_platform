<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('customers')
            ->where('customer_code', 'TEST-CUST-001')
            ->update([
                'customer_code' => '100',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('customers')
            ->where('customer_code', '100')
            ->where('email', 'maintenance.test@unifco.local')
            ->update([
                'customer_code' => 'TEST-CUST-001',
                'updated_at' => now(),
            ]);
    }
};
