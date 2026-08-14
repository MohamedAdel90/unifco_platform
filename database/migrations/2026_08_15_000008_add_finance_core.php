<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chart_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code'); $t->string('name');
            $t->string('type'); // ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE
            $t->string('normal_balance')->default('DEBIT');
            $t->boolean('posting_allowed')->default(true);
            $t->string('status')->default('ACTIVE');
            $t->timestamps();
            $t->unique(['tenant_id','code']);
        });

        Schema::create('fiscal_periods', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code'); $t->date('starts_on'); $t->date('ends_on');
            $t->string('status')->default('OPEN');
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('closed_at')->nullable(); $t->timestamps();
            $t->unique(['tenant_id','code']);
        });

        Schema::create('financial_documents', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('document_no'); $t->string('document_type'); // AP_INVOICE, AR_INVOICE
            $t->string('counterparty_name'); $t->date('document_date'); $t->date('due_date')->nullable();
            $t->string('currency',3)->default('USD'); $t->decimal('amount',19,2);
            $t->string('control_account_code'); $t->string('offset_account_code');
            $t->string('status')->default('DRAFT');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->timestamp('posted_at')->nullable(); $t->decimal('open_amount',19,2)->default(0);
            $t->timestamps(); $t->unique(['tenant_id','document_no']);
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('financial_document_id')->constrained()->restrictOnDelete();
            $t->string('payment_no'); $t->date('payment_date'); $t->decimal('amount',19,2);
            $t->string('cash_account_code'); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $t->timestamps(); $t->unique(['tenant_id','payment_no']);
        });
    }

    public function down(): void
    {
        foreach (['payments','financial_documents','fiscal_periods','chart_accounts'] as $table) Schema::dropIfExists($table);
    }
};
