<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_trips', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('trip_no',50);
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('trip_type',30)->default('LOCAL');
            $t->string('purpose',500);
            $t->string('destination_city',120);
            $t->string('destination_country',80)->default('SA');
            $t->date('starts_on'); $t->date('ends_on');
            $t->decimal('per_diem_rate',12,2)->default(0); $t->decimal('per_diem_days',8,2)->default(0); $t->decimal('per_diem_total',14,2)->default(0);
            $t->decimal('requested_advance',14,2)->default(0); $t->decimal('approved_advance',14,2)->default(0);
            $t->string('advance_status',25)->default('NONE');
            $t->string('travel_method',40)->nullable(); $t->boolean('hotel_required')->default(false); $t->boolean('transport_required')->default(false);
            $t->string('status',30)->default('PENDING');
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('approved_at')->nullable();
            $t->text('completion_notes')->nullable();
            $t->decimal('settlement_total',14,2)->default(0); $t->decimal('company_payable',14,2)->default(0); $t->decimal('employee_refund_due',14,2)->default(0);
            $t->timestamp('settled_at')->nullable(); $t->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tenant_id','trip_no']);
            $t->index(['tenant_id','status']); $t->index(['employee_id','starts_on']);
        });

        Schema::create('business_trip_expenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('business_trip_id')->constrained()->cascadeOnDelete();
            $t->date('expense_date'); $t->string('category',30); $t->string('description',500);
            $t->decimal('amount',14,2); $t->string('currency',3)->default('SAR'); $t->string('receipt_ref',255)->nullable();
            $t->string('status',20)->default('SUBMITTED');
            $t->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->index(['business_trip_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_trip_expenses');
        Schema::dropIfExists('business_trips');
    }
};
