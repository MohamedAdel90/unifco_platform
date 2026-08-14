<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $t) { $t->id(); $t->foreignId('tenant_id'); $t->foreignId('organization_id')->nullable(); $t->string('lead_no'); $t->string('name'); $t->string('company')->nullable(); $t->string('email')->nullable(); $t->string('status')->default('NEW'); $t->foreignId('created_by')->nullable(); $t->timestamps(); $t->unique(['tenant_id','lead_no']); });
        Schema::create('crm_opportunities', function (Blueprint $t) { $t->id(); $t->foreignId('tenant_id'); $t->foreignId('organization_id')->nullable(); $t->foreignId('lead_id')->nullable(); $t->foreignId('customer_id')->nullable(); $t->string('opportunity_no'); $t->string('name'); $t->string('stage')->default('QUALIFICATION'); $t->decimal('expected_value',18,2)->default(0); $t->unsignedTinyInteger('probability')->default(0); $t->date('expected_close')->nullable(); $t->string('status')->default('OPEN'); $t->foreignId('created_by')->nullable(); $t->timestamps(); $t->unique(['tenant_id','opportunity_no']); });
        Schema::create('crm_quotations', function (Blueprint $t) { $t->id(); $t->foreignId('tenant_id'); $t->foreignId('organization_id')->nullable(); $t->foreignId('opportunity_id'); $t->string('quotation_no'); $t->date('quotation_date'); $t->string('currency',3)->default('USD'); $t->decimal('amount',18,2); $t->string('status')->default('DRAFT'); $t->foreignId('created_by')->nullable(); $t->timestamps(); $t->unique(['tenant_id','quotation_no']); });
        Schema::create('project_tasks', function (Blueprint $t) { $t->id(); $t->foreignId('tenant_id'); $t->foreignId('project_id')->constrained()->cascadeOnDelete(); $t->string('wbs_code'); $t->string('name'); $t->date('planned_start')->nullable(); $t->date('planned_finish')->nullable(); $t->decimal('budget',18,2)->default(0); $t->string('status')->default('PLANNED'); $t->timestamps(); $t->unique(['project_id','wbs_code']); });
        Schema::create('project_resource_assignments', function (Blueprint $t) { $t->id(); $t->foreignId('tenant_id'); $t->foreignId('project_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete(); $t->string('role')->nullable(); $t->decimal('planned_hours',10,2)->default(0); $t->timestamps(); $t->unique(['project_id','employee_id']); });
        Schema::create('project_timesheets', function (Blueprint $t) { $t->id(); $t->foreignId('tenant_id'); $t->foreignId('project_id')->constrained()->cascadeOnDelete(); $t->foreignId('project_task_id')->nullable()->constrained('project_tasks')->nullOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete(); $t->date('work_date'); $t->decimal('hours',8,2); $t->decimal('hourly_cost',18,2)->default(0); $t->string('status')->default('POSTED'); $t->foreignId('created_by')->nullable(); $t->timestamps(); });
        Schema::table('projects', function (Blueprint $t) { $t->foreignId('opportunity_id')->nullable(); $t->decimal('actual_cost',18,2)->default(0); });
    }
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $t) { $t->dropColumn(['opportunity_id','actual_cost']); });
        Schema::dropIfExists('project_timesheets'); Schema::dropIfExists('project_resource_assignments'); Schema::dropIfExists('project_tasks'); Schema::dropIfExists('crm_quotations'); Schema::dropIfExists('crm_opportunities'); Schema::dropIfExists('crm_leads');
    }
};