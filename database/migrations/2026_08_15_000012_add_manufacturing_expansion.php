<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_centers', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('tenant_id'); $t->unsignedBigInteger('organization_id');
            $t->string('code',40); $t->string('name',120); $t->decimal('hourly_rate',14,2)->default(0); $t->string('status',20)->default('ACTIVE'); $t->timestamps();
            $t->unique(['tenant_id','code']);
        });
        Schema::create('boms', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('tenant_id'); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('product_item_id');
            $t->string('bom_no',50); $t->unsignedInteger('version')->default(1); $t->string('status',20)->default('ACTIVE'); $t->timestamps();
            $t->unique(['tenant_id','bom_no','version']);
        });
        Schema::create('bom_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('bom_id')->constrained()->cascadeOnDelete(); $t->unsignedInteger('line_no'); $t->unsignedBigInteger('component_item_id');
            $t->decimal('quantity_per',14,4); $t->decimal('standard_unit_cost',14,4)->default(0); $t->timestamps();
        });
        Schema::create('routings', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('tenant_id'); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('product_item_id');
            $t->string('routing_no',50); $t->string('status',20)->default('ACTIVE'); $t->timestamps(); $t->unique(['tenant_id','routing_no']);
        });
        Schema::create('routing_operations', function (Blueprint $t) {
            $t->id(); $t->foreignId('routing_id')->constrained()->cascadeOnDelete(); $t->unsignedInteger('sequence'); $t->unsignedBigInteger('work_center_id');
            $t->string('operation_name',120); $t->decimal('standard_hours',12,4)->default(0); $t->timestamps();
        });
        Schema::table('production_orders', function (Blueprint $t) {
            $t->unsignedBigInteger('item_id')->nullable(); $t->unsignedBigInteger('bom_id')->nullable(); $t->unsignedBigInteger('routing_id')->nullable(); $t->string('warehouse_code',40)->nullable();
            $t->decimal('standard_cost',16,2)->default(0); $t->decimal('actual_cost',16,2)->default(0); $t->decimal('cost_variance',16,2)->default(0);
        });
        Schema::create('production_materials', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('tenant_id'); $t->foreignId('production_order_id')->constrained()->cascadeOnDelete(); $t->unsignedBigInteger('item_id');
            $t->decimal('planned_quantity',14,4); $t->decimal('issued_quantity',14,4)->default(0); $t->decimal('unit_cost',14,4)->default(0); $t->timestamps();
        });
        Schema::create('production_confirmations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('tenant_id'); $t->foreignId('production_order_id')->constrained()->cascadeOnDelete(); $t->unsignedBigInteger('work_center_id')->nullable();
            $t->decimal('hours',12,4)->default(0); $t->decimal('good_quantity',14,4)->default(0); $t->decimal('scrap_quantity',14,4)->default(0); $t->unsignedBigInteger('created_by'); $t->timestamps();
        });
        Schema::create('quality_inspections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('tenant_id'); $t->foreignId('production_order_id')->constrained()->cascadeOnDelete(); $t->string('result',20); $t->text('notes')->nullable(); $t->unsignedBigInteger('inspected_by'); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_inspections'); Schema::dropIfExists('production_confirmations'); Schema::dropIfExists('production_materials');
        Schema::table('production_orders', fn(Blueprint $t) => $t->dropColumn(['item_id','bom_id','routing_id','warehouse_code','standard_cost','actual_cost','cost_variance']));
        Schema::dropIfExists('routing_operations'); Schema::dropIfExists('routings'); Schema::dropIfExists('bom_lines'); Schema::dropIfExists('boms'); Schema::dropIfExists('work_centers');
    }
};