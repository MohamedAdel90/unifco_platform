<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable()->after('created_by');
            $table->timestamp('next_follow_up_at')->nullable()->after('assigned_to');
            $table->string('duplicate_review_status',20)->default('CLEAR')->after('next_follow_up_at');
            $table->unsignedBigInteger('duplicate_customer_id')->nullable()->after('duplicate_review_status');
            $table->unsignedBigInteger('duplicate_lead_id')->nullable()->after('duplicate_customer_id');
            $table->string('conversion_approval_status',20)->default('NOT_REQUIRED')->after('duplicate_lead_id');
            $table->unsignedBigInteger('conversion_requested_by')->nullable()->after('conversion_approval_status');
            $table->timestamp('conversion_requested_at')->nullable()->after('conversion_requested_by');
            $table->unsignedBigInteger('conversion_approved_by')->nullable()->after('conversion_requested_at');
            $table->timestamp('conversion_approved_at')->nullable()->after('conversion_approved_by');
            $table->text('conversion_review_notes')->nullable()->after('conversion_approved_at');
            $table->index(['assigned_to','next_follow_up_at']);
            $table->index('conversion_approval_status');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('onboarding_review_status',20)->default('PENDING')->after('onboarding_status');
            $table->unsignedBigInteger('onboarding_reviewed_by')->nullable()->after('onboarding_review_status');
            $table->timestamp('onboarding_reviewed_at')->nullable()->after('onboarding_reviewed_by');
            $table->text('onboarding_review_notes')->nullable()->after('onboarding_reviewed_at');
            $table->index('onboarding_review_status');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['onboarding_review_status']);
            $table->dropColumn(['onboarding_review_status','onboarding_reviewed_by','onboarding_reviewed_at','onboarding_review_notes']);
        });
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropIndex(['assigned_to','next_follow_up_at']);
            $table->dropIndex(['conversion_approval_status']);
            $table->dropColumn([
                'assigned_to','next_follow_up_at','duplicate_review_status','duplicate_customer_id','duplicate_lead_id','conversion_approval_status',
                'conversion_requested_by','conversion_requested_at','conversion_approved_by','conversion_approved_at','conversion_review_notes'
            ]);
        });
    }
};
