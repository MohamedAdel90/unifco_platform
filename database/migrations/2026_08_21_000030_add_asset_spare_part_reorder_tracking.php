<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_spare_parts', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_spare_parts','preferred_warehouse_code')) $table->string('preferred_warehouse_code',50)->nullable();
            if (! Schema::hasColumn('asset_spare_parts','last_reorder_notified_at')) $table->timestamp('last_reorder_notified_at')->nullable();
            if (! Schema::hasColumn('asset_spare_parts','reorder_alert_status')) $table->string('reorder_alert_status',20)->default('OK');
        });
    }

    public function down(): void
    {
        Schema::table('asset_spare_parts', function (Blueprint $table) {
            foreach (['preferred_warehouse_code','last_reorder_notified_at','reorder_alert_status'] as $column) {
                if (Schema::hasColumn('asset_spare_parts',$column)) $table->dropColumn($column);
            }
        });
    }
};
