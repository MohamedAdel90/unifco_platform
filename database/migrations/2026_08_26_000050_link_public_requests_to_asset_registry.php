<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('public_service_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('public_service_requests', 'asset_id')) {
                $table->foreignId('asset_id')->nullable()->after('organization_id')->constrained('assets')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('public_service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('public_service_requests', 'asset_id')) {
                $table->dropConstrainedForeignId('asset_id');
            }
        });
    }
};
