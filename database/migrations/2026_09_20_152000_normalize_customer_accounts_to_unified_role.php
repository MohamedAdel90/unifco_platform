<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if(!Schema::hasTable('users')) return;

        DB::table('users')
            ->whereNotNull('customer_id')
            ->whereIn('role',['CUSTOMER_ADMIN','CUSTOMER_SITE_MANAGER','CUSTOMER_FINANCE','CUSTOMER_VIEWER'])
            ->update([
                'role'=>'CUSTOMER',
                'user_type'=>'EXTERNAL',
                'customer_portal_role'=>'CUSTOMER',
                'updated_at'=>now(),
            ]);
    }

    public function down(): void
    {
        // Unified customer accounts are intentionally not split back into
        // legacy personas because that would be lossy and ambiguous.
    }
};
