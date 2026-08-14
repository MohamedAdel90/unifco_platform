<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->string('receipt_no'); $t->string('warehouse_code');
            $t->date('receipt_date'); $t->string('status')->default('POSTED'); $t->timestamps();
            $t->unique(['tenant_id','receipt_no']);
        });
        Schema::create('goods_receipt_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('purchase_order_line_id')->constrained()->restrictOnDelete();
            $t->foreignId('item_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity',19,4); $t->decimal('unit_cost',19,2); $t->timestamps();
        });
        Schema::create('processed_events', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $t->uuid('event_id'); $t->string('consumer'); $t->timestamp('processed_at')->useCurrent();
            $t->unique(['event_id','consumer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_events');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
    }
};
