<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->timestamp('last_login_at')->nullable()->after('status');
            $t->boolean('force_password_change')->default(false)->after('last_login_at');
            $t->string('mfa_status',24)->default('NOT_CONFIGURED')->after('force_password_change');
            $t->timestamp('locked_at')->nullable()->after('mfa_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['last_login_at','force_password_change','mfa_status','locked_at']);
        });
    }
};
