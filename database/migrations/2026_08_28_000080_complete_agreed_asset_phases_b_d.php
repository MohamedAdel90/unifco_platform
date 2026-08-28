<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_inspection_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->date('inspection_date');
            $table->string('inspection_type',80)->default('GENERAL');
            $table->string('condition_status',30);
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['asset_id','inspection_date'],'asset_insp_asset_date_idx');
        });

        Schema::create('customer_asset_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->string('source',30)->default('MANUAL');
            $table->string('import_batch',60)->nullable();
            $table->string('name',180);
            $table->string('customer_asset_code',120)->nullable();
            $table->string('serial_no',120)->nullable();
            $table->string('asset_category',120);
            $table->string('asset_type',120)->nullable();
            $table->string('manufacturer',180)->nullable();
            $table->string('model_no',180)->nullable();
            $table->string('criticality',20)->default('MEDIUM');
            $table->string('ownership_type',30)->default('CUSTOMER_OWNED');
            $table->string('physical_location',255)->nullable();
            $table->json('technical_specifications')->nullable();
            $table->string('status',30)->default('PENDING_VERIFICATION');
            $table->text('verification_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id','customer_id','status'],'cust_asset_sub_scope_idx');
            $table->index(['customer_id','serial_no'],'cust_asset_sub_serial_idx');
        });

        Schema::create('customer_asset_submission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_asset_submission_id')->constrained('customer_asset_submissions')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('event_type',50);
            $table->string('from_status',30)->nullable();
            $table->string('to_status',30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->index(['customer_asset_submission_id','performed_at'],'cust_asset_sub_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_asset_submission_events');
        Schema::dropIfExists('customer_asset_submissions');
        Schema::dropIfExists('asset_inspection_records');
    }
};
