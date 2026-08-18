<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $t) {
            $t->foreignId('service_contract_id')->nullable()->constrained('service_contracts')->nullOnDelete();
            $t->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $t->timestamp('responded_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->unsignedInteger('response_sla_minutes')->nullable();
            $t->unsignedInteger('resolution_sla_minutes')->nullable();
        });

        Schema::table('crm_quotations', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->timestamp('customer_approved_at')->nullable();
            $t->timestamp('customer_rejected_at')->nullable();
            $t->text('customer_decision_notes')->nullable();
        });

        Schema::create('maintenance_visit_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->foreignId('service_contract_id')->nullable()->constrained('service_contracts')->nullOnDelete();
            $t->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $t->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $t->string('report_no',60);
            $t->date('visit_date');
            $t->string('visit_type',30);
            $t->text('findings')->nullable();
            $t->text('work_performed')->nullable();
            $t->text('recommendations')->nullable();
            $t->string('technician_name')->nullable();
            $t->string('customer_acknowledgement')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','report_no']);
        });

        Schema::create('maintenance_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $t->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $t->foreignId('visit_report_id')->nullable()->constrained('maintenance_visit_reports')->nullOnDelete();
            $t->string('attachment_type',20); // BEFORE, AFTER, REPORT, OTHER
            $t->string('original_name');
            $t->string('storage_path');
            $t->string('mime_type',120)->nullable();
            $t->unsignedBigInteger('size_bytes')->default(0);
            $t->timestamps();
        });

        Schema::create('customer_portal_alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->string('alert_type',40);
            $t->string('title');
            $t->text('message')->nullable();
            $t->string('severity',20)->default('INFO');
            $t->date('due_date')->nullable();
            $t->string('source_type',80)->nullable();
            $t->unsignedBigInteger('source_id')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['customer_id','read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_alerts');
        Schema::dropIfExists('maintenance_attachments');
        Schema::dropIfExists('maintenance_visit_reports');
        Schema::table('crm_quotations', function (Blueprint $t) {
            $t->dropConstrainedForeignId('customer_id');
            $t->dropColumn(['customer_approved_at','customer_rejected_at','customer_decision_notes']);
        });
        Schema::table('service_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('service_contract_id');
            $t->dropConstrainedForeignId('asset_id');
            $t->dropColumn(['responded_at','resolved_at','response_sla_minutes','resolution_sla_minutes']);
        });
    }
};
