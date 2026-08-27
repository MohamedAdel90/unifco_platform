<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_portal_action_requests', function (Blueprint $table) {
            $table->string('assigned_role',60)->nullable()->after('action_type');
            $table->string('priority',20)->default('NORMAL')->after('assigned_role');
            $table->timestamp('due_at')->nullable()->after('submitted_at');
            $table->index(['assigned_role','status','due_at'],'customer_action_role_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customer_portal_action_requests', function (Blueprint $table) {
            $table->dropIndex('customer_action_role_status_due_idx');
            $table->dropColumn(['assigned_role','priority','due_at']);
        });
    }
};
