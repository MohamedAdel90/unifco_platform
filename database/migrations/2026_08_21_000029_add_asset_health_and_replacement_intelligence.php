<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets','health_score')) $table->unsignedTinyInteger('health_score')->nullable();
            if (! Schema::hasColumn('assets','health_band')) $table->string('health_band',30)->nullable();
            if (! Schema::hasColumn('assets','remaining_life_months')) $table->integer('remaining_life_months')->nullable();
            if (! Schema::hasColumn('assets','replacement_recommendation')) $table->string('replacement_recommendation',40)->nullable();
            if (! Schema::hasColumn('assets','replacement_reason')) $table->text('replacement_reason')->nullable();
            if (! Schema::hasColumn('assets','last_health_calculated_at')) $table->timestamp('last_health_calculated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            foreach (['health_score','health_band','remaining_life_months','replacement_recommendation','replacement_reason','last_health_calculated_at'] as $column) {
                if (Schema::hasColumn('assets',$column)) $table->dropColumn($column);
            }
        });
    }
};
