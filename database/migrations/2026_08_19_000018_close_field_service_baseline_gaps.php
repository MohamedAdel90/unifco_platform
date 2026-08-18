<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('employee_id')->nullable()->after('customer_id')->constrained('employees')->nullOnDelete();
        });

        Schema::create('work_order_assignments', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete(); $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->timestamp('scheduled_start'); $t->timestamp('scheduled_end')->nullable(); $t->string('dispatch_status',30)->default('SCHEDULED');
            $t->timestamp('dispatched_at')->nullable(); $t->timestamp('accepted_at')->nullable(); $t->timestamp('arrived_at')->nullable(); $t->text('dispatcher_notes')->nullable(); $t->timestamps();
            $t->unique(['work_order_id','employee_id']); $t->index(['employee_id','scheduled_start']);
        });

        Schema::create('inspection_templates', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('template_no',60); $t->string('name'); $t->json('checklist'); $t->string('status',20)->default('ACTIVE'); $t->timestamps(); $t->unique(['tenant_id','template_no']);
        });

        Schema::create('inspections', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete(); $t->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $t->foreignId('inspection_template_id')->nullable()->constrained('inspection_templates')->nullOnDelete(); $t->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->string('inspection_no',60); $t->string('status',20)->default('DRAFT'); $t->json('responses')->nullable(); $t->text('findings')->nullable(); $t->text('recommendations')->nullable(); $t->timestamp('completed_at')->nullable(); $t->timestamps(); $t->unique(['tenant_id','inspection_no']);
        });

        Schema::create('ai_interactions', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete(); $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->text('query'); $t->text('response'); $t->json('citations')->nullable(); $t->json('recommended_actions')->nullable(); $t->string('result',20)->default('ANSWERED'); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_interactions'); Schema::dropIfExists('inspections'); Schema::dropIfExists('inspection_templates'); Schema::dropIfExists('work_order_assignments');
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('employee_id'));
    }
};
