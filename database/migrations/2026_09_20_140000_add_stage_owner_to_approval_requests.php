<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table): void {
            $table->foreignId('assigned_user_id')->nullable()->after('approval_role')->constrained('users')->nullOnDelete();
            $table->string('routing_status',30)->default('ROLE_QUEUE')->after('assigned_user_id');
            $table->index(['tenant_id','assigned_user_id','status']);
        });
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id','assigned_user_id','status']);
            $table->dropColumn('routing_status');
            $table->dropConstrainedForeignId('assigned_user_id');
        });
    }
};
