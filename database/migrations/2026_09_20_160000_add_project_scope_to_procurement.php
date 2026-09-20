<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->after('organization_id')->constrained('projects')->nullOnDelete();
            $table->index(['tenant_id','project_id','status']);
        });
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->after('organization_id')->constrained('projects')->nullOnDelete();
            $table->index(['tenant_id','project_id','status']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id','project_id','status']);
            $table->dropConstrainedForeignId('project_id');
        });
        Schema::table('purchase_requisitions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id','project_id','status']);
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
