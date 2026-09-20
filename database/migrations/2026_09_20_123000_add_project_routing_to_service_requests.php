<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->after('service_contract_id')->constrained('projects')->nullOnDelete();
            $table->foreignId('project_manager_id')->nullable()->after('operations_manager_id')->constrained('users')->nullOnDelete();
            $table->string('project_routing_status',30)->default('UNASSIGNED')->after('project_manager_id');
            $table->index(['tenant_id','project_id','status']);
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id','project_id','status']);
            $table->dropConstrainedForeignId('project_manager_id');
            $table->dropColumn('project_routing_status');
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
