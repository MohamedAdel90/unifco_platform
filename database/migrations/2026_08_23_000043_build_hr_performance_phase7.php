<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('performance_cycles', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code',50); $t->string('name',140); $t->date('starts_on'); $t->date('ends_on'); $t->string('status',20)->default('OPEN'); $t->timestamps();
            $t->unique(['tenant_id','code']);
        });
        Schema::create('employee_goals', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete(); $t->foreignId('performance_cycle_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title',180); $t->text('description')->nullable(); $t->decimal('weight',6,2)->default(0); $t->decimal('target_value',14,2)->nullable(); $t->decimal('actual_value',14,2)->nullable(); $t->string('unit',40)->nullable();
            $t->date('due_on')->nullable(); $t->string('status',20)->default('OPEN'); $t->decimal('score',6,2)->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
            $t->index(['tenant_id','employee_id','status']);
        });
        Schema::create('performance_reviews', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete(); $t->foreignId('performance_cycle_id')->nullable()->constrained()->nullOnDelete();
            $t->string('review_type',30)->default('ANNUAL'); $t->date('review_date'); $t->decimal('goal_score',6,2)->default(0); $t->decimal('competency_score',6,2)->default(0); $t->decimal('overall_score',6,2)->default(0);
            $t->string('rating',30)->nullable(); $t->text('strengths')->nullable(); $t->text('development_areas')->nullable(); $t->text('manager_comments')->nullable(); $t->text('employee_comments')->nullable(); $t->string('status',30)->default('DRAFT');
            $t->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete(); $t->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('submitted_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('approved_at')->nullable(); $t->timestamps();
            $t->index(['tenant_id','employee_id','status']);
        });
        Schema::create('probation_reviews', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete(); $t->date('review_date'); $t->date('probation_end_date')->nullable();
            $t->decimal('performance_score',6,2)->default(0); $t->decimal('attendance_score',6,2)->default(0); $t->decimal('conduct_score',6,2)->default(0); $t->decimal('overall_score',6,2)->default(0);
            $t->string('recommendation',30); $t->text('comments')->nullable(); $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('employee_development_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('item_type',30); $t->string('title',180); $t->text('description')->nullable(); $t->date('target_date')->nullable(); $t->string('status',20)->default('PLANNED'); $t->decimal('estimated_cost',14,2)->default(0); $t->string('provider',140)->nullable(); $t->timestamps();
        });
        Schema::create('employee_skills', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('skill_name',140); $t->string('skill_type',30)->default('SKILL'); $t->string('proficiency',30)->nullable(); $t->string('certificate_no',100)->nullable(); $t->date('issued_on')->nullable(); $t->date('expires_on')->nullable(); $t->string('issuer',140)->nullable(); $t->string('status',20)->default('VALID'); $t->timestamps();
            $t->index(['tenant_id','employee_id','skill_type']);
        });
        Schema::create('career_actions', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete(); $t->string('action_type',30);
            $t->foreignId('from_job_position_id')->nullable()->constrained('job_positions')->nullOnDelete(); $t->foreignId('to_job_position_id')->nullable()->constrained('job_positions')->nullOnDelete();
            $t->decimal('old_basic_salary',14,2)->default(0); $t->decimal('new_basic_salary',14,2)->default(0); $t->date('effective_on'); $t->text('reason')->nullable(); $t->string('status',30)->default('PENDING_APPROVAL');
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('approved_at')->nullable(); $t->timestamps();
            $t->index(['tenant_id','employee_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_actions'); Schema::dropIfExists('employee_skills'); Schema::dropIfExists('employee_development_items'); Schema::dropIfExists('probation_reviews'); Schema::dropIfExists('performance_reviews'); Schema::dropIfExists('employee_goals'); Schema::dropIfExists('performance_cycles');
    }
};
