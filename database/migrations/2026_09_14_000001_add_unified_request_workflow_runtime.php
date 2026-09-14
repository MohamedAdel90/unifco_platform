<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('service_requests', 'request_subtype')) $table->string('request_subtype', 80)->nullable()->after('request_type');
            if (! Schema::hasColumn('service_requests', 'workflow_key')) $table->string('workflow_key', 100)->nullable()->after('workflow_stage');
            if (! Schema::hasColumn('service_requests', 'assigned_department')) $table->string('assigned_department', 80)->nullable()->after('workflow_key');
            if (! Schema::hasColumn('service_requests', 'approval_state')) $table->string('approval_state', 40)->nullable()->after('assigned_department');
            if (! Schema::hasColumn('service_requests', 'next_action')) $table->string('next_action', 120)->nullable()->after('approval_state');
            if (! Schema::hasColumn('service_requests', 'workflow_context')) $table->json('workflow_context')->nullable()->after('next_action');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            foreach (['workflow_context','next_action','approval_state','assigned_department','workflow_key','request_subtype'] as $column) {
                if (Schema::hasColumn('service_requests', $column)) $table->dropColumn($column);
            }
        });
    }
};
