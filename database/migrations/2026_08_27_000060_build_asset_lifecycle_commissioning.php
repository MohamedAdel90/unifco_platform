<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('asset_locations')) {
            Schema::create('asset_locations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('customer_site_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('location_type',30);
                $table->string('code',80);
                $table->string('name',160);
                $table->string('description',255)->nullable();
                $table->decimal('latitude',10,7)->nullable();
                $table->decimal('longitude',10,7)->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['tenant_id','customer_site_id','code'],'asset_location_code_unique');
                $table->index(['tenant_id','customer_id','customer_site_id','parent_id']);
            });
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedBigInteger('asset_location_id')->nullable()->after('customer_site_id');
            $table->string('commissioning_status',30)->default('NOT_STARTED')->after('commission_date');
            $table->unsignedBigInteger('commissioning_requested_by')->nullable()->after('commissioning_status');
            $table->timestamp('commissioning_requested_at')->nullable()->after('commissioning_requested_by');
            $table->unsignedBigInteger('commissioning_approved_by')->nullable()->after('commissioning_requested_at');
            $table->timestamp('commissioning_approved_at')->nullable()->after('commissioning_approved_by');
            $table->text('commissioning_notes')->nullable()->after('commissioning_approved_at');
            $table->index(['tenant_id','commissioning_status']);
            $table->index(['tenant_id','asset_location_id']);
        });

        Schema::create('asset_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->string('event_type',50);
            $table->string('from_status',40)->nullable();
            $table->string('to_status',40)->nullable();
            $table->string('title',180);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->index(['tenant_id','asset_id','performed_at']);
            $table->index(['tenant_id','event_type']);
        });

        Schema::create('asset_commissioning_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->string('status',30)->default('DRAFT');
            $table->date('inspection_date')->nullable();
            $table->string('inspection_result',30)->nullable();
            $table->json('checklist')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','asset_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_commissioning_records');
        Schema::dropIfExists('asset_lifecycle_events');
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['tenant_id','commissioning_status']);
            $table->dropIndex(['tenant_id','asset_location_id']);
            $table->dropColumn(['asset_location_id','commissioning_status','commissioning_requested_by','commissioning_requested_at','commissioning_approved_by','commissioning_approved_at','commissioning_notes']);
        });
        Schema::dropIfExists('asset_locations');
    }
};
