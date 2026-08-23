<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payroll_policies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('code',50);
            $t->string('name',120);
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->decimal('pension_employee_rate',6,4)->default(0.09);
            $t->decimal('pension_employer_rate',6,4)->default(0.09);
            $t->decimal('saned_employee_rate',6,4)->default(0.01);
            $t->decimal('saned_employer_rate',6,4)->default(0.01);
            $t->decimal('occupational_hazard_employer_rate',6,4)->default(0.02);
            $t->decimal('overtime_basic_premium_rate',6,4)->default(0.50);
            $t->decimal('standard_month_days',6,2)->default(30);
            $t->string('status',20)->default('ACTIVE');
            $t->timestamps();
            $t->unique(['tenant_id','code','effective_from'],'payroll_policy_version_unique');
        });

        Schema::table('payroll_runs', function (Blueprint $t) {
            $t->foreignId('payroll_policy_id')->nullable()->after('organization_id')->constrained('payroll_policies')->nullOnDelete();
            $t->decimal('gross_total',14,2)->default(0)->after('currency');
            $t->decimal('employee_deductions_total',14,2)->default(0)->after('gross_total');
            $t->decimal('employer_contributions_total',14,2)->default(0)->after('employee_deductions_total');
            $t->decimal('net_total',14,2)->default(0)->after('employer_contributions_total');
        });

        Schema::table('payroll_lines', function (Blueprint $t) {
            $t->decimal('housing_allowance',12,2)->default(0)->after('basic_pay');
            $t->decimal('transport_allowance',12,2)->default(0)->after('housing_allowance');
            $t->decimal('other_allowances',12,2)->default(0)->after('transport_allowance');
            $t->decimal('overtime_hours',10,2)->default(0)->after('other_allowances');
            $t->decimal('overtime_pay',12,2)->default(0)->after('overtime_hours');
            $t->decimal('unpaid_leave_days',10,2)->default(0)->after('overtime_pay');
            $t->decimal('unpaid_leave_deduction',12,2)->default(0)->after('unpaid_leave_days');
            $t->decimal('gosi_contributory_wage',12,2)->default(0)->after('unpaid_leave_deduction');
            $t->decimal('gosi_pension_employee',12,2)->default(0)->after('gosi_contributory_wage');
            $t->decimal('gosi_saned_employee',12,2)->default(0)->after('gosi_pension_employee');
            $t->decimal('gosi_pension_employer',12,2)->default(0)->after('gosi_saned_employee');
            $t->decimal('gosi_saned_employer',12,2)->default(0)->after('gosi_pension_employer');
            $t->decimal('gosi_hazard_employer',12,2)->default(0)->after('gosi_saned_employer');
            $t->decimal('employee_deductions_total',12,2)->default(0)->after('gosi_hazard_employer');
            $t->decimal('employer_contributions_total',12,2)->default(0)->after('employee_deductions_total');
            $t->string('calculation_status',20)->default('CALCULATED')->after('employer_contributions_total');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_lines', function (Blueprint $t) {
            $t->dropColumn(['housing_allowance','transport_allowance','other_allowances','overtime_hours','overtime_pay','unpaid_leave_days','unpaid_leave_deduction','gosi_contributory_wage','gosi_pension_employee','gosi_saned_employee','gosi_pension_employer','gosi_saned_employer','gosi_hazard_employer','employee_deductions_total','employer_contributions_total','calculation_status']);
        });
        Schema::table('payroll_runs', function (Blueprint $t) {
            $t->dropConstrainedForeignId('payroll_policy_id');
            $t->dropColumn(['gross_total','employee_deductions_total','employer_contributions_total','net_total']);
        });
        Schema::dropIfExists('payroll_policies');
    }
};
