<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('request_type', 40)->nullable()->after('request_no');
            $table->string('workflow_stage', 60)->default('NEW')->after('status');
            $table->string('eligibility', 40)->nullable()->after('workflow_stage');
            $table->unsignedBigInteger('customer_site_id')->nullable()->after('customer_id');
            $table->unsignedBigInteger('assigned_engineer_id')->nullable()->after('asset_id');
            $table->boolean('procurement_required')->default(false)->after('assigned_engineer_id');
            $table->unsignedBigInteger('quotation_id')->nullable()->after('work_order_id');
            $table->timestamp('workflow_started_at')->nullable()->after('quotation_id');
            $table->timestamp('current_stage_due_at')->nullable()->after('workflow_started_at');
            $table->index(['customer_id','request_type','workflow_stage'], 'service_requests_customer_workflow_idx');
        });

        Schema::table('approval_requests', function (Blueprint $table) {
            $table->string('workflow_key', 80)->nullable()->after('action');
            $table->string('approval_role', 80)->nullable()->after('workflow_key');
            $table->unsignedInteger('step_order')->default(1)->after('approval_role');
            $table->unsignedInteger('sla_minutes')->nullable()->after('step_order');
            $table->timestamp('due_at')->nullable()->after('sla_minutes');
            $table->timestamp('reminded_at')->nullable()->after('due_at');
            $table->timestamp('escalated_at')->nullable()->after('reminded_at');
            $table->json('metadata')->nullable()->after('escalated_at');
            $table->index(['status','approval_role','due_at'], 'approval_requests_inbox_idx');
        });

        Schema::table('crm_quotations', function (Blueprint $table) {
            $table->unsignedInteger('revision_no')->default(0)->after('quotation_no');
            $table->unsignedBigInteger('parent_quotation_id')->nullable()->after('revision_no');
            $table->decimal('cost_amount', 18, 2)->default(0)->after('amount');
            $table->decimal('margin_pct', 7, 2)->nullable()->after('cost_amount');
            $table->unsignedInteger('payment_terms_days')->nullable()->after('margin_pct');
            $table->string('risk_level', 20)->default('NORMAL')->after('payment_terms_days');
        });

        Schema::create('customer_activity_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->string('event_type', 60);
            $table->string('reference_type', 120)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('visibility', 20)->default('BOTH');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['customer_id','created_at'], 'customer_activity_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_activity_events');
        Schema::table('crm_quotations', function (Blueprint $table) {
            $table->dropColumn(['revision_no','parent_quotation_id','cost_amount','margin_pct','payment_terms_days','risk_level']);
        });
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropIndex('approval_requests_inbox_idx');
            $table->dropColumn(['workflow_key','approval_role','step_order','sla_minutes','due_at','reminded_at','escalated_at','metadata']);
        });
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex('service_requests_customer_workflow_idx');
            $table->dropColumn(['request_type','workflow_stage','eligibility','customer_site_id','assigned_engineer_id','procurement_required','quotation_id','workflow_started_at','current_stage_due_at']);
        });
    }
};
