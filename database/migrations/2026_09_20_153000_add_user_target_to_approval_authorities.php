<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('approval_authorities', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('role_id')->constrained('users')->nullOnDelete();
            $table->index(['tenant_id','user_id','approval_type','is_active'],'approval_authority_user_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('approval_authorities', function (Blueprint $table): void {
            $table->dropIndex('approval_authority_user_lookup_idx');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
