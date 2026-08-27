<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->string('source_channel',40)->nullable()->after('source');
            $table->string('source_detail',160)->nullable()->after('source_channel');
            $table->string('lifecycle_stage',30)->default('LEAD')->after('status');
            $table->string('service_interest',120)->nullable()->after('lifecycle_stage');
            $table->string('city',100)->nullable()->after('service_interest');
            $table->text('inquiry_notes')->nullable()->after('city');
            $table->timestamp('first_touch_at')->nullable()->after('inquiry_notes');
            $table->unsignedBigInteger('first_touch_user_id')->nullable()->after('first_touch_at');
            $table->timestamp('qualified_at')->nullable()->after('first_touch_user_id');
            $table->unsignedBigInteger('qualified_by')->nullable()->after('qualified_at');
            $table->unsignedBigInteger('converted_customer_id')->nullable()->after('qualified_by');
            $table->timestamp('converted_at')->nullable()->after('converted_customer_id');
            $table->unsignedBigInteger('converted_by')->nullable()->after('converted_at');
            $table->index(['source_channel','lifecycle_stage']);
            $table->index('converted_customer_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('acquisition_source',40)->nullable()->after('onboarding_status');
            $table->unsignedBigInteger('origin_lead_id')->nullable()->after('acquisition_source');
            $table->timestamp('first_touch_at')->nullable()->after('origin_lead_id');
            $table->unsignedBigInteger('converted_by')->nullable()->after('first_touch_at');
            $table->timestamp('converted_at')->nullable()->after('converted_by');
            $table->index('origin_lead_id');
            $table->index('acquisition_source');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['origin_lead_id']);
            $table->dropIndex(['acquisition_source']);
            $table->dropColumn(['acquisition_source','origin_lead_id','first_touch_at','converted_by','converted_at']);
        });
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropIndex(['source_channel','lifecycle_stage']);
            $table->dropIndex(['converted_customer_id']);
            $table->dropColumn([
                'source_channel','source_detail','lifecycle_stage','service_interest','city','inquiry_notes','first_touch_at',
                'first_touch_user_id','qualified_at','qualified_by','converted_customer_id','converted_at','converted_by'
            ]);
        });
    }
};
