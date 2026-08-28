<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('asset_custodies')) {
            Schema::create('asset_custodies', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('organization_id')->nullable(); $table->unsignedBigInteger('asset_id');
                $table->unsignedBigInteger('custodian_user_id')->nullable(); $table->string('custodian_name',160)->nullable(); $table->string('department',160)->nullable(); $table->string('branch',160)->nullable();
                $table->string('status',30)->default('ACTIVE'); $table->timestamp('assigned_at'); $table->timestamp('returned_at')->nullable(); $table->text('notes')->nullable();
                $table->unsignedBigInteger('assigned_by'); $table->unsignedBigInteger('returned_by')->nullable(); $table->timestamps();
                $table->index(['tenant_id','asset_id','status'],'asset_custody_scope_idx'); $table->index(['tenant_id','custodian_user_id','status'],'asset_custodian_idx');
            });
        }

        Schema::table('asset_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('from_custody_id')->nullable()->after('asset_id');
            $table->unsignedBigInteger('to_custodian_user_id')->nullable()->after('from_custody_id');
            $table->string('to_custodian_name',160)->nullable()->after('to_custodian_user_id');
            $table->string('to_department',160)->nullable()->after('to_custodian_name');
            $table->string('to_branch',160)->nullable()->after('to_department');
            $table->unsignedBigInteger('to_customer_site_id')->nullable()->after('to_branch');
            $table->string('status',30)->default('PENDING_APPROVAL')->after('transfer_date');
            $table->text('reason')->nullable()->after('status'); $table->text('request_notes')->nullable()->after('reason');
            $table->unsignedBigInteger('requested_by')->nullable()->after('request_notes'); $table->timestamp('requested_at')->nullable()->after('requested_by');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('requested_at'); $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at'); $table->timestamp('completed_at')->nullable()->after('review_notes');
            $table->index(['tenant_id','asset_id','status'],'asset_transfer_scope_idx'); $table->index(['tenant_id','status','requested_at'],'asset_transfer_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::table('asset_transfers', function (Blueprint $table) {
            $table->dropIndex('asset_transfer_scope_idx'); $table->dropIndex('asset_transfer_queue_idx');
            $table->dropColumn(['from_custody_id','to_custodian_user_id','to_custodian_name','to_department','to_branch','to_customer_site_id','status','reason','request_notes','requested_by','requested_at','reviewed_by','reviewed_at','review_notes','completed_at']);
        });
        Schema::dropIfExists('asset_custodies');
    }
};
