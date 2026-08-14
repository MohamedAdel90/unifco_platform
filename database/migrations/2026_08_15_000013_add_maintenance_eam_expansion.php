<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('parent_asset_id')->nullable()->after('organization_id')->constrained('assets')->nullOnDelete();
            $table->string('location_code',80)->nullable();
            $table->string('serial_no',120)->nullable();
            $table->string('warranty_expiry',10)->nullable();
            $table->decimal('salvage_value',18,2)->default(0);
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->decimal('accumulated_depreciation',18,2)->default(0);
            $table->decimal('net_book_value',18,2)->default(0);
            $table->decimal('meter_value',18,4)->default(0);
            $table->timestamp('disposed_at')->nullable();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('maintenance_plan_id')->nullable()->after('asset_id');
            $table->decimal('labor_hours',12,2)->default(0);
            $table->decimal('labor_cost',18,2)->default(0);
            $table->decimal('material_cost',18,2)->default(0);
            $table->decimal('external_cost',18,2)->default(0);
            $table->decimal('total_cost',18,2)->default(0);
            $table->unsignedInteger('downtime_minutes')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_code')->nullable();
        });

        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id'); $table->foreignId('organization_id');
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('plan_no',50); $table->string('name',160); $table->string('frequency_type',20);
            $table->unsignedInteger('frequency_value')->default(1); $table->date('next_due_date')->nullable();
            $table->decimal('next_due_meter',18,4)->nullable(); $table->string('priority',20)->default('NORMAL');
            $table->string('status',20)->default('ACTIVE'); $table->timestamps();
            $table->unique(['tenant_id','plan_no']);
        });

        Schema::create('asset_meter_readings', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id'); $table->foreignId('organization_id');
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->decimal('reading',18,4); $table->date('reading_date'); $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });

        Schema::create('maintenance_materials', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id'); $table->foreignId('organization_id');
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items'); $table->string('warehouse_code',50);
            $table->decimal('quantity',18,4); $table->decimal('unit_cost',18,4)->default(0); $table->decimal('total_cost',18,2)->default(0); $table->timestamps();
        });

        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id'); $table->foreignId('organization_id');
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('from_location',80)->nullable(); $table->string('to_location',80); $table->date('transfer_date');
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });

        Schema::create('asset_depreciation_entries', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id'); $table->foreignId('organization_id');
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('posting_date'); $table->decimal('amount',18,2); $table->decimal('accumulated_after',18,2); $table->decimal('nbv_after',18,2); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_entries'); Schema::dropIfExists('asset_transfers');
        Schema::dropIfExists('maintenance_materials'); Schema::dropIfExists('asset_meter_readings'); Schema::dropIfExists('maintenance_plans');
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['maintenance_plan_id','labor_hours','labor_cost','material_cost','external_cost','total_cost','downtime_minutes','started_at','completed_at','failure_code']);
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['parent_asset_id']);
            $table->dropColumn(['parent_asset_id','location_code','serial_no','warranty_expiry','salvage_value','useful_life_months','accumulated_depreciation','net_book_value','meter_value','disposed_at']);
        });
    }
};