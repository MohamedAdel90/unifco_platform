<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_asset_submissions', function (Blueprint $table) {
            $table->string('maintenance_strategy',30)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_asset_submissions', function (Blueprint $table) {
            $table->string('maintenance_strategy',30)->default('PREVENTIVE')->nullable(false)->change();
        });
    }
};
