<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_spare_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('manufacturer_part_no',120)->nullable();
            $table->string('alternative_part_no',120)->nullable();
            $table->decimal('recommended_quantity',18,4)->default(1);
            $table->decimal('min_stock',18,4)->default(0);
            $table->decimal('max_stock',18,4)->default(0);
            $table->decimal('reorder_level',18,4)->default(0);
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->string('preferred_supplier',180)->nullable();
            $table->boolean('critical_spare')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['asset_id','item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_spare_parts');
    }
};
