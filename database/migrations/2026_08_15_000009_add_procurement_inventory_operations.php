<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('supplier_code'); $t->string('name'); $t->string('email')->nullable();
            $t->string('tax_no')->nullable(); $t->string('status')->default('ACTIVE'); $t->timestamps();
            $t->unique(['tenant_id','supplier_code']);
        });
        Schema::create('purchase_requisitions', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('requisition_no'); $t->date('requested_date'); $t->text('purpose')->nullable();
            $t->string('status')->default('DRAFT'); $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('approved_at')->nullable(); $t->timestamps();
            $t->unique(['tenant_id','requisition_no']);
        });
        Schema::create('purchase_requisition_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete(); $t->unsignedInteger('line_no');
            $t->foreignId('item_id')->constrained()->restrictOnDelete(); $t->decimal('quantity',19,4); $t->decimal('estimated_unit_price',19,2)->default(0);
            $t->timestamps(); $t->unique(['purchase_requisition_id','line_no']);
        });
        Schema::create('warehouses', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code'); $t->string('name'); $t->string('status')->default('ACTIVE'); $t->timestamps(); $t->unique(['tenant_id','code']);
        });
        Schema::create('inventory_transfers', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('item_id')->constrained()->restrictOnDelete();
            $t->string('transfer_no'); $t->string('from_warehouse_code'); $t->string('to_warehouse_code'); $t->decimal('quantity',19,4);
            $t->string('status')->default('POSTED'); $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
            $t->unique(['tenant_id','transfer_no']);
        });
        Schema::create('inventory_counts', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('item_id')->constrained()->restrictOnDelete();
            $t->string('count_no'); $t->string('warehouse_code'); $t->decimal('system_quantity',19,4); $t->decimal('counted_quantity',19,4);
            $t->decimal('variance',19,4); $t->string('status')->default('POSTED'); $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
            $t->unique(['tenant_id','count_no']);
        });
        Schema::table('purchase_orders', function (Blueprint $t) {
            $t->foreignId('supplier_id')->nullable()->after('approved_by')->constrained('suppliers')->nullOnDelete();
            $t->foreignId('purchase_requisition_id')->nullable()->after('supplier_id')->constrained('purchase_requisitions')->nullOnDelete();
        });
        Schema::create('supplier_invoices', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete(); $t->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $t->string('invoice_no'); $t->date('invoice_date'); $t->decimal('amount',19,2); $t->string('status')->default('DRAFT');
            $t->foreignId('financial_document_id')->nullable()->constrained('financial_documents')->nullOnDelete(); $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
            $t->unique(['tenant_id','invoice_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
        Schema::table('purchase_orders', function (Blueprint $t) { $t->dropConstrainedForeignId('purchase_requisition_id'); $t->dropConstrainedForeignId('supplier_id'); });
        foreach (['inventory_counts','inventory_transfers','warehouses','purchase_requisition_lines','purchase_requisitions','suppliers'] as $table) Schema::dropIfExists($table);
    }
};
