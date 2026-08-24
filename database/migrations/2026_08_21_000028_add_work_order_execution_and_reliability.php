<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_order_checklist_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('maintenance_plan_task_id')->constrained('maintenance_plan_tasks')->cascadeOnDelete();
            $table->string('result_status',20)->nullable();
            $table->decimal('numeric_value',18,4)->nullable();
            $table->text('text_value')->nullable();
            $table->text('technician_notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['work_order_id','maintenance_plan_task_id'],'wo_checklist_work_order_task_uq');
        });

        Schema::create('work_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('maintenance_plan_task_id')->nullable()->constrained('maintenance_plan_tasks')->nullOnDelete();
            $table->string('attachment_type',30)->default('PHOTO');
            $table->string('title',255)->nullable();
            $table->string('file_path',500);
            $table->string('original_name',255);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->string('failure_code',120)->nullable();
            $table->string('failure_mode',180)->nullable();
            $table->string('failure_effect',180)->nullable();
            $table->string('failure_cause',180)->nullable();
            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->timestamp('failed_at');
            $table->timestamp('restored_at')->nullable();
            $table->unsignedInteger('downtime_minutes')->default(0);
            $table->decimal('meter_at_failure',18,4)->nullable();
            $table->string('severity',20)->default('MEDIUM');
            $table->string('status',20)->default('OPEN');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['asset_id','failed_at']);
        });

        if (! Schema::hasColumn('work_orders','execution_notes')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->text('execution_notes')->nullable();
                $table->text('completion_notes')->nullable();
                $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_failures');
        Schema::dropIfExists('work_order_attachments');
        Schema::dropIfExists('work_order_checklist_results');
        if (Schema::hasColumn('work_orders','execution_notes')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('started_by');
                $table->dropConstrainedForeignId('completed_by');
                $table->dropColumn(['execution_notes','completion_notes']);
            });
        }
    }
};
