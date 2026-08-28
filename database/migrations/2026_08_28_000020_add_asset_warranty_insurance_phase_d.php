<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_coverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('coverage_type', 30); // WARRANTY | INSURANCE
            $table->string('provider', 180);
            $table->string('reference_no', 120)->nullable();
            $table->date('starts_at');
            $table->date('expires_at');
            $table->decimal('coverage_amount', 18, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->text('scope')->nullable();
            $table->string('status', 30)->default('ACTIVE');
            $table->foreignId('renewed_from_id')->nullable()->constrained('asset_coverages')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id','asset_id','coverage_type','status']);
            $table->index(['tenant_id','expires_at','status']);
        });

        Schema::create('asset_coverage_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_coverage_id')->constrained('asset_coverages')->cascadeOnDelete();
            $table->string('claim_no', 120)->nullable();
            $table->date('claim_date');
            $table->decimal('claimed_amount', 18, 2)->nullable();
            $table->decimal('approved_amount', 18, 2)->nullable();
            $table->string('status', 30)->default('SUBMITTED');
            $table->text('description');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','asset_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_coverage_claims');
        Schema::dropIfExists('asset_coverages');
    }
};
