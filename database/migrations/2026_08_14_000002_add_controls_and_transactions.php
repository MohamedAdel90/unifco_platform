<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $t) {
            $t->foreignId('created_by')->nullable()->after('organization_id')->constrained('users')->nullOnDelete();
            $t->foreignId('posted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
        Schema::table('purchase_orders', function (Blueprint $t) {
            $t->foreignId('created_by')->nullable()->after('organization_id')->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('role_code'); $t->string('permission_code'); $t->timestamps();
            $t->unique(['tenant_id','role_code','permission_code']);
        });
        Schema::create('approval_requests', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('entity_type'); $t->unsignedBigInteger('entity_id'); $t->string('action');
            $t->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status')->default('PENDING'); $t->text('decision_note')->nullable();
            $t->timestamp('decided_at')->nullable(); $t->timestamps();
            $t->index(['tenant_id','entity_type','entity_id','status']);
        });
        Schema::create('purchase_order_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('line_no'); $t->foreignId('item_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity',19,4); $t->decimal('unit_price',19,2); $t->timestamps();
            $t->unique(['purchase_order_id','line_no']);
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('item_id')->constrained()->restrictOnDelete(); $t->string('warehouse_code');
            $t->string('movement_type'); $t->decimal('quantity',19,4); $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable(); $t->uuid('correlation_id')->nullable();
            $t->string('idempotency_key'); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->unique(['tenant_id','idempotency_key']);
        });
        Schema::create('outbox_events', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('event_type'); $t->string('aggregate_type'); $t->unsignedBigInteger('aggregate_id');
            $t->uuid('correlation_id')->nullable(); $t->json('payload'); $t->timestamp('published_at')->nullable();
            $t->unsignedInteger('attempts')->default(0); $t->text('last_error')->nullable(); $t->timestamps();
            $t->index(['published_at','created_at']);
        });
    }

    public function down(): void
    {
        foreach (['outbox_events','stock_movements','purchase_order_lines','approval_requests','role_permissions'] as $table) Schema::dropIfExists($table);
        Schema::table('purchase_orders', fn (Blueprint $t) => $t->dropConstrainedForeignId('approved_by'));
        Schema::table('purchase_orders', fn (Blueprint $t) => $t->dropConstrainedForeignId('created_by'));
        Schema::table('journals', fn (Blueprint $t) => $t->dropConstrainedForeignId('posted_by'));
        Schema::table('journals', fn (Blueprint $t) => $t->dropConstrainedForeignId('created_by'));
    }
};
