<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('asset_category_templates')) {
            Schema::create('asset_category_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->string('category',100);
                $table->string('asset_type',120);
                $table->string('name',160);
                $table->json('specification_schema')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['tenant_id','category','asset_type'],'asset_template_identity_unique');
                $table->index(['tenant_id','active']);
            });
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->string('customer_asset_code',100)->nullable()->after('asset_code');
            $table->string('asset_type',120)->nullable()->after('asset_subcategory');
            $table->string('ownership_type',30)->default('CUSTOMER_OWNED')->after('criticality');
            $table->string('building',120)->nullable()->after('location_code');
            $table->string('floor',80)->nullable()->after('building');
            $table->string('zone',120)->nullable()->after('floor');
            $table->string('room',120)->nullable()->after('zone');
            $table->string('physical_location',255)->nullable()->after('room');
            $table->decimal('latitude',10,7)->nullable()->after('physical_location');
            $table->decimal('longitude',10,7)->nullable()->after('latitude');
            $table->date('warranty_start')->nullable()->after('installation_date');
            $table->string('warranty_provider',160)->nullable()->after('warranty_expiry');
            $table->string('maintenance_strategy',30)->nullable()->after('operational_status');
            $table->json('technical_specifications')->nullable()->after('maintenance_strategy');
            $table->unsignedTinyInteger('data_completeness_score')->default(0)->after('verification_status');
            $table->unsignedBigInteger('verified_by')->nullable()->after('data_completeness_score');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('verification_notes')->nullable()->after('verified_at');
            $table->index(['tenant_id','customer_id','serial_no'],'asset_customer_serial_idx');
            $table->index(['tenant_id','customer_id','customer_asset_code'],'asset_customer_code_idx');
            $table->index(['tenant_id','verification_status'],'asset_verification_idx');
        });

        if (!Schema::hasTable('asset_documents')) {
            Schema::create('asset_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->unsignedBigInteger('asset_id');
                $table->string('document_type',50);
                $table->string('title',180);
                $table->string('path',500);
                $table->string('original_name',255);
                $table->string('mime_type',120)->nullable();
                $table->string('version',30)->nullable();
                $table->date('issued_at')->nullable();
                $table->date('expires_at')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->index(['tenant_id','asset_id','document_type']);
                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_documents');
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('asset_customer_serial_idx');
            $table->dropIndex('asset_customer_code_idx');
            $table->dropIndex('asset_verification_idx');
            $table->dropColumn([
                'customer_asset_code','asset_type','ownership_type','building','floor','zone','room','physical_location','latitude','longitude',
                'warranty_start','warranty_provider','maintenance_strategy','technical_specifications','data_completeness_score','verified_by','verified_at','verification_notes'
            ]);
        });
        Schema::dropIfExists('asset_category_templates');
    }
};
