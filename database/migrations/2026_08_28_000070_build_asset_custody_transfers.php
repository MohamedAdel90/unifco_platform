<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_custodies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('custodian_user_id')->nullable();
            $table->string('custodian_name',160)->nullable();
            $table->string('department',160)->nullable();
            $table->string('branch',160)->nullable();
            $table->string('status',30)->default('ACTIVE');
            $table->timestamp('assigned_at');
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->unsignedBigInteger('returned_by')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','asset_id','status'],'asset_custody_scope_idx');
            $table->index(['tenant_id','custodian_user_id','status'],'asset_custodian_idx');
        });

        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('from_custody_id')->nullable();
            $table->unsignedBigInteger('to_custodian_user_id')->nullable();
            $table->string('to_custodian_name',160)->nullable();
            $table->string('to_department',160)->nullable();
            $table->string('to_branch',160)->nullable();
            $table->unsignedBigInteger('to_customer_site_id')->nullable();
            $table->string('status',30)->default('PENDING_APPROVAL');
            $table->text('reason');
            $table->text('request_notes')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->timestamp('requested_at');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','asset_id','status'],'asset_transfer_scope_idx');
            $table->index(['tenant_id','status','requested_at'],'asset_transfer_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_transfers');
        Schema::dropIfExists('asset_custodies');
    }
};
