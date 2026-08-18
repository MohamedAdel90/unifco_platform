<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $t) {
            $t->string('mobile',32)->nullable();
            $t->string('commercial_registration',60)->nullable();
            $t->string('source',40)->nullable();
        });

        Schema::create('service_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('request_no',60);
            $t->string('company_name');
            $t->string('commercial_registration',60)->nullable();
            $t->string('email')->nullable();
            $t->string('mobile',32)->nullable();
            $t->string('service_category');
            $t->string('subject');
            $t->text('details');
            $t->string('site_city')->nullable();
            $t->string('priority',20)->default('NORMAL');
            $t->string('status',20)->default('OPEN');
            $t->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tenant_id','request_no']);
        });

        Schema::table('public_service_requests', function (Blueprint $t) {
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $t->foreignId('crm_opportunity_id')->nullable()->constrained('crm_opportunities')->nullOnDelete();
            $t->foreignId('crm_quotation_id')->nullable()->constrained('crm_quotations')->nullOnDelete();
            $t->foreignId('service_request_id')->nullable()->constrained('service_requests')->nullOnDelete();
            $t->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $t->timestamp('converted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('public_service_requests', function (Blueprint $t) {
            foreach (['tenant_id','organization_id','crm_lead_id','crm_opportunity_id','crm_quotation_id','service_request_id','work_order_id'] as $column) {
                $t->dropConstrainedForeignId($column);
            }
            $t->dropColumn('converted_at');
        });
        Schema::dropIfExists('service_requests');
        Schema::table('crm_leads', fn (Blueprint $t) => $t->dropColumn(['mobile','commercial_registration','source']));
    }
};
