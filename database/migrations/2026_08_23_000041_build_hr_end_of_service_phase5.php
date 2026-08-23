<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('end_of_service_policies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code',50);
            $t->string('name',140);
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->decimal('first_five_years_month_factor',6,4)->default(0.5000);
            $t->decimal('after_five_years_month_factor',6,4)->default(1.0000);
            $t->decimal('resignation_two_to_five_multiplier',6,4)->default(0.3333);
            $t->decimal('resignation_five_to_ten_multiplier',6,4)->default(0.6667);
            $t->decimal('resignation_ten_plus_multiplier',6,4)->default(1.0000);
            $t->decimal('standard_month_days',6,2)->default(30);
            $t->boolean('include_housing_allowance')->default(true);
            $t->boolean('include_transport_allowance')->default(true);
            $t->boolean('include_other_allowances')->default(true);
            $t->string('status',20)->default('ACTIVE');
            $t->timestamps();
            $t->unique(['tenant_id','code','effective_from'],'eos_policy_version_unique');
        });

        Schema::create('final_settlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('end_of_service_policy_id')->nullable()->constrained('end_of_service_policies')->nullOnDelete();
            $t->string('settlement_no',60);
            $t->string('termination_reason',50);
            $t->date('service_start_date');
            $t->date('last_working_day');
            $t->decimal('service_years',10,6)->default(0);
            $t->decimal('last_wage_basis',14,2)->default(0);
            $t->decimal('eos_base_award',14,2)->default(0);
            $t->decimal('eos_multiplier',8,4)->default(1);
            $t->decimal('eos_award',14,2)->default(0);
            $t->decimal('unused_leave_days',10,2)->default(0);
            $t->decimal('leave_daily_rate',14,4)->default(0);
            $t->decimal('leave_encashment',14,2)->default(0);
            $t->decimal('unpaid_salary',14,2)->default(0);
            $t->decimal('other_earnings',14,2)->default(0);
            $t->decimal('notice_compensation',14,2)->default(0);
            $t->decimal('employee_debt',14,2)->default(0);
            $t->decimal('advance_recovery',14,2)->default(0);
            $t->decimal('other_deductions',14,2)->default(0);
            $t->decimal('gross_entitlements',14,2)->default(0);
            $t->decimal('total_deductions',14,2)->default(0);
            $t->decimal('net_settlement',14,2)->default(0);
            $t->string('status',30)->default('DRAFT');
            $t->text('notes')->nullable();
            $t->json('calculation_snapshot')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('submitted_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','settlement_no']);
            $t->index(['tenant_id','employee_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_settlements');
        Schema::dropIfExists('end_of_service_policies');
    }
};
