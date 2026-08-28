<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('manufacturer_asset_number',120)->nullable()->after('serial_no');
            $table->string('room_code',80)->nullable()->after('room');
            $table->string('contract_reference',120)->nullable()->after('warranty_provider');
            $table->string('sla_reference',120)->nullable()->after('contract_reference');
            $table->string('coverage_type',80)->nullable()->after('sla_reference');
            $table->decimal('operating_hours',14,2)->nullable()->after('meter_value');
            $table->string('meter_unit',30)->nullable()->after('operating_hours');
            $table->decimal('design_capacity',14,3)->nullable()->after('meter_unit');
            $table->decimal('current_load',14,3)->nullable()->after('design_capacity');
            $table->string('failure_impact',30)->nullable()->after('current_load');
            $table->string('pm_template',160)->nullable()->after('maintenance_strategy');
            $table->string('pm_frequency',80)->nullable()->after('pm_template');
            $table->date('last_pm')->nullable()->after('pm_frequency');
            $table->date('next_pm')->nullable()->after('last_pm');
            $table->date('last_inspection')->nullable()->after('next_pm');
            $table->date('next_inspection')->nullable()->after('last_inspection');
            $table->index(['tenant_id','manufacturer_asset_number'],'asset_mfr_number_idx');
            $table->index(['tenant_id','contract_reference'],'asset_contract_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('asset_mfr_number_idx');
            $table->dropIndex('asset_contract_ref_idx');
            $table->dropColumn(['manufacturer_asset_number','room_code','contract_reference','sla_reference','coverage_type','operating_hours','meter_unit','design_capacity','current_load','failure_impact','pm_template','pm_frequency','last_pm','next_pm','last_inspection','next_inspection']);
        });
    }
};
