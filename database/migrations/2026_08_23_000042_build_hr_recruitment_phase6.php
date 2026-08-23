<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manpower_requisitions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('job_position_id')->nullable()->constrained('job_positions')->nullOnDelete();
            $t->string('requisition_no',60);
            $t->string('title',160);
            $t->string('department',120)->nullable();
            $t->unsignedSmallInteger('headcount')->default(1);
            $t->string('employment_type',40)->default('FULL_TIME');
            $t->string('work_location',160)->nullable();
            $t->decimal('budget_min',12,2)->default(0);
            $t->decimal('budget_max',12,2)->default(0);
            $t->date('needed_by')->nullable();
            $t->text('justification')->nullable();
            $t->string('status',30)->default('DRAFT');
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','requisition_no']);
        });

        Schema::create('recruitment_vacancies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('manpower_requisition_id')->constrained('manpower_requisitions')->cascadeOnDelete();
            $t->string('vacancy_no',60);
            $t->string('title',160);
            $t->text('description')->nullable();
            $t->date('opens_on')->nullable();
            $t->date('closes_on')->nullable();
            $t->string('status',30)->default('OPEN');
            $t->timestamps();
            $t->unique(['tenant_id','vacancy_no']);
        });

        Schema::create('recruitment_candidates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('recruitment_vacancy_id')->constrained('recruitment_vacancies')->cascadeOnDelete();
            $t->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->string('candidate_no',60);
            $t->string('name',160);
            $t->string('email')->nullable();
            $t->string('mobile',40)->nullable();
            $t->string('nationality',80)->nullable();
            $t->string('source',60)->nullable();
            $t->string('stage',40)->default('APPLIED');
            $t->decimal('expected_salary',12,2)->default(0);
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','candidate_no']);
        });

        Schema::create('candidate_interviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('recruitment_candidate_id')->constrained('recruitment_candidates')->cascadeOnDelete();
            $t->string('interview_type',40)->default('HR');
            $t->timestamp('scheduled_at');
            $t->foreignId('interviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->decimal('score',5,2)->nullable();
            $t->string('decision',30)->default('PENDING');
            $t->text('feedback')->nullable();
            $t->timestamps();
        });

        Schema::create('employment_offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('recruitment_candidate_id')->constrained('recruitment_candidates')->cascadeOnDelete();
            $t->string('offer_no',60);
            $t->decimal('basic_salary',12,2)->default(0);
            $t->decimal('housing_allowance',12,2)->default(0);
            $t->decimal('transport_allowance',12,2)->default(0);
            $t->decimal('other_allowances',12,2)->default(0);
            $t->string('currency',3)->default('SAR');
            $t->date('proposed_start_date');
            $t->unsignedSmallInteger('probation_days')->default(90);
            $t->string('status',30)->default('DRAFT');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','offer_no']);
        });

        Schema::create('employee_onboarding_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('category',50);
            $t->string('task_name',180);
            $t->date('due_on')->nullable();
            $t->string('status',30)->default('PENDING');
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['tenant_id','employee_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_onboarding_tasks');
        Schema::dropIfExists('employment_offers');
        Schema::dropIfExists('candidate_interviews');
        Schema::dropIfExists('recruitment_candidates');
        Schema::dropIfExists('recruitment_vacancies');
        Schema::dropIfExists('manpower_requisitions');
    }
};
