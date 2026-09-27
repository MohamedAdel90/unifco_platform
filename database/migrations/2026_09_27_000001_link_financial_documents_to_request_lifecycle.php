<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('financial_documents', function (Blueprint $table) {
            $table->foreignId('service_request_id')->nullable()->after('project_id')->constrained('service_requests')->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->after('service_request_id')->constrained('work_orders')->nullOnDelete();
            $table->foreignId('crm_quotation_id')->nullable()->after('work_order_id')->constrained('crm_quotations')->nullOnDelete();
            $table->index(['tenant_id', 'service_request_id', 'document_type'], 'fin_docs_request_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('financial_documents', function (Blueprint $table) {
            $table->dropIndex('fin_docs_request_type_idx');
            $table->dropConstrainedForeignId('crm_quotation_id');
            $table->dropConstrainedForeignId('work_order_id');
            $table->dropConstrainedForeignId('service_request_id');
        });
    }
};
