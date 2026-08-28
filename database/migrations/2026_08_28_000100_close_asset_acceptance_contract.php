<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('purchase_date')->nullable()->after('manufacture_date');
            $table->string('po_number',120)->nullable()->after('purchase_date');
            $table->decimal('purchase_value',18,2)->nullable()->after('po_number');
            $table->text('warranty_terms')->nullable()->after('warranty_provider');
            $table->date('replacement_target_date')->nullable()->after('expected_replacement_date');
            $table->decimal('replacement_cost_estimate',18,2)->nullable()->after('replacement_target_date');

            $table->unsignedTinyInteger('impact_safety')->nullable()->after('criticality');
            $table->unsignedTinyInteger('impact_operation')->nullable()->after('impact_safety');
            $table->unsignedTinyInteger('impact_financial')->nullable()->after('impact_operation');
            $table->unsignedTinyInteger('impact_customer')->nullable()->after('impact_financial');
            $table->unsignedTinyInteger('impact_environmental')->nullable()->after('impact_customer');
            $table->unsignedTinyInteger('probability_failure')->nullable()->after('impact_environmental');
            $table->unsignedTinyInteger('probability_condition')->nullable()->after('probability_failure');
            $table->unsignedTinyInteger('probability_age')->nullable()->after('probability_condition');
            $table->decimal('criticality_matrix_score',6,2)->nullable()->after('probability_age');
            $table->string('criticality_class',1)->nullable()->after('criticality_matrix_score');
            $table->index(['tenant_id','criticality_class'],'asset_criticality_class_idx');
        });

        Schema::table('asset_part_installations', function (Blueprint $table) {
            $table->string('installed_part_number',120)->nullable()->after('item_id');
            $table->string('installed_serial_number',120)->nullable()->after('installed_part_number');
            $table->string('installed_manufacturer',160)->nullable()->after('installed_serial_number');
            $table->date('warranty_start')->nullable()->after('installed_at');
            $table->date('warranty_end')->nullable()->after('warranty_start');
            $table->string('component_status',30)->default('INSTALLED')->after('warranty_end');
            $table->timestamp('removed_at')->nullable()->after('component_status');
            $table->index(['asset_id','component_status'],'asset_component_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('asset_part_installations', function (Blueprint $table) {
            $table->dropIndex('asset_component_status_idx');
            $table->dropColumn(['installed_part_number','installed_serial_number','installed_manufacturer','warranty_start','warranty_end','component_status','removed_at']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('asset_criticality_class_idx');
            $table->dropColumn(['purchase_date','po_number','purchase_value','warranty_terms','replacement_target_date','replacement_cost_estimate','impact_safety','impact_operation','impact_financial','impact_customer','impact_environmental','probability_failure','probability_condition','probability_age','criticality_matrix_score','criticality_class']);
        });
    }
};
