<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code');
            $t->string('name');
            $t->time('starts_at')->default('08:00:00');
            $t->time('ends_at')->default('17:00:00');
            $t->unsignedSmallInteger('break_minutes')->default(60);
            $t->unsignedSmallInteger('grace_minutes')->default(10);
            $t->decimal('daily_hours',5,2)->default(8);
            $t->json('working_days')->nullable();
            $t->boolean('ramadan_mode')->default(false);
            $t->decimal('ramadan_daily_hours',5,2)->default(6);
            $t->string('status')->default('ACTIVE');
            $t->timestamps();
            $t->unique(['tenant_id','code']);
        });

        Schema::table('employees', function (Blueprint $t) {
            $t->foreignId('work_schedule_id')->nullable()->after('job_position_id')->constrained('work_schedules')->nullOnDelete();
        });

        Schema::table('attendance_entries', function (Blueprint $t) {
            $t->foreignId('work_schedule_id')->nullable()->after('employee_id')->constrained('work_schedules')->nullOnDelete();
            $t->time('check_in_at')->nullable()->after('work_date');
            $t->time('check_out_at')->nullable()->after('check_in_at');
            $t->unsignedSmallInteger('late_minutes')->default(0)->after('overtime_hours');
            $t->unsignedSmallInteger('early_leave_minutes')->default(0)->after('late_minutes');
            $t->string('attendance_type')->default('PRESENT')->after('early_leave_minutes');
            $t->string('source')->default('MANUAL')->after('attendance_type');
            $t->text('notes')->nullable()->after('source');
        });

        Schema::create('leave_policies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code');
            $t->string('name');
            $t->string('leave_type');
            $t->decimal('annual_entitlement_days',8,2)->default(0);
            $t->string('accrual_method')->default('MONTHLY');
            $t->decimal('carry_forward_limit_days',8,2)->default(0);
            $t->boolean('requires_approval')->default(true);
            $t->boolean('paid')->default(true);
            $t->string('status')->default('ACTIVE');
            $t->timestamps();
            $t->unique(['tenant_id','code']);
        });

        Schema::create('employee_leave_balances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('leave_policy_id')->constrained('leave_policies')->cascadeOnDelete();
            $t->unsignedSmallInteger('balance_year');
            $t->decimal('opening_days',8,2)->default(0);
            $t->decimal('accrued_days',8,2)->default(0);
            $t->decimal('used_days',8,2)->default(0);
            $t->decimal('adjusted_days',8,2)->default(0);
            $t->decimal('carried_forward_days',8,2)->default(0);
            $t->timestamps();
            $t->unique(['tenant_id','employee_id','leave_policy_id','balance_year'],'leave_balance_unique');
        });

        Schema::table('leave_requests', function (Blueprint $t) {
            $t->foreignId('leave_policy_id')->nullable()->after('employee_id')->constrained('leave_policies')->nullOnDelete();
            $t->string('day_portion')->default('FULL_DAY')->after('days');
            $t->text('decision_notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('leave_policy_id');
            $t->dropColumn(['day_portion','decision_notes']);
        });
        Schema::dropIfExists('employee_leave_balances');
        Schema::dropIfExists('leave_policies');
        Schema::table('attendance_entries', function (Blueprint $t) {
            $t->dropConstrainedForeignId('work_schedule_id');
            $t->dropColumn(['check_in_at','check_out_at','late_minutes','early_leave_minutes','attendance_type','source','notes']);
        });
        Schema::table('employees', fn (Blueprint $t) => $t->dropConstrainedForeignId('work_schedule_id'));
        Schema::dropIfExists('work_schedules');
    }
};
