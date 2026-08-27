<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users','status')) return;

        // Legacy bootstrap created users.status as an integer while the application,
        // seeders and session middleware use explicit lifecycle strings.
        DB::statement("ALTER TABLE users MODIFY status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE'");
        DB::table('users')->whereIn('status',['1','true','TRUE'])->update(['status'=>'ACTIVE']);
        DB::table('users')->whereIn('status',['0','false','FALSE',''])->update(['status'=>'INACTIVE']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users','status')) return;

        DB::table('users')->where('status','ACTIVE')->update(['status'=>'1']);
        DB::table('users')->where('status','!=','1')->update(['status'=>'0']);
        DB::statement('ALTER TABLE users MODIFY status TINYINT(1) UNSIGNED NOT NULL DEFAULT 1');
    }
};
