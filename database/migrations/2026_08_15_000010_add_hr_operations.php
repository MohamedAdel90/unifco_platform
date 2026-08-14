<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_positions', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code'); $t->string('title'); $t->string('department')->nullable();
            $t->string('status')->default('ACTIVE'); $t->timestamps();
            $t->unique(['tenant_id','code']);
        });
        Schema::table('employees', function (Blueprint $t) { $t->foreignId('job_position_id')->nullable()->constrained('job_positions')->nullOnDelete(); });
        Schema::create('attendance_entries', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->date('work_date'); $t->decimal('worked_hours',6,2)->default(0); $t->decimal('overtime_hours',6,2)->default(0); $t->string('status')->default('RECORDED'); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
            $t->unique(['tenant_id','employee_id','work_date']);
        });
        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('leave_type'); $t->date('starts_on'); $t->date('ends_on'); $t->decimal('days',8,2); $t->text('reason')->nullable();
            $t->string('status')->default('PENDING'); $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('decided_at')->nullable(); $t->timestamps();
        });
        Schema::create('payroll_runs', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('payroll_no'); $t->date('period_start'); $t->date('period_end'); $t->date('posting_date'); $t->string('currency',3)->default('USD');
            $t->string('status')->default('DRAFT'); $t->foreignId('created_by')->constrained('users')->cascadeOnDelete(); $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $t->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete(); $t->timestamps();
            $t->unique(['tenant_id','payroll_no']);
        });
        Schema::create('payroll_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('payroll_run_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->restrictOnDelete();
            $t->decimal('basic_pay',19,2)->default(0); $t->decimal('allowances',19,2)->default(0); $t->decimal('deductions',19,2)->default(0); $t->decimal('net_pay',19,2)->default(0); $t->timestamps();
            $t->unique(['payroll_run_id','employee_id']);
        });
    }

    public function down(): void
    {
        foreach (['payroll_lines','payroll_runs','leave_requests','attendance_entries'] as $table) Schema::dropIfExists($table);
        Schema::table('employees', fn (Blueprint $t) => $t->dropConstrainedForeignId('job_position_id'));
        Schema::dropIfExists('job_positions');
    }
};