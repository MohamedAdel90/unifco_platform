<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets','customer_site_id')) $table->foreignId('customer_site_id')->nullable()->after('customer_id')->constrained('customer_sites')->nullOnDelete();
            if (! Schema::hasColumn('assets','asset_category')) $table->string('asset_category',120)->nullable();
            if (! Schema::hasColumn('assets','asset_subcategory')) $table->string('asset_subcategory',120)->nullable();
            if (! Schema::hasColumn('assets','manufacturer')) $table->string('manufacturer',180)->nullable();
            if (! Schema::hasColumn('assets','model_no')) $table->string('model_no',180)->nullable();
            if (! Schema::hasColumn('assets','criticality')) $table->string('criticality',20)->default('MEDIUM');
            if (! Schema::hasColumn('assets','lifecycle_status')) $table->string('lifecycle_status',30)->default('ACTIVE');
            if (! Schema::hasColumn('assets','operational_status')) $table->string('operational_status',30)->default('RUNNING');
            if (! Schema::hasColumn('assets','manufacture_date')) $table->date('manufacture_date')->nullable();
            if (! Schema::hasColumn('assets','installation_date')) $table->date('installation_date')->nullable();
            if (! Schema::hasColumn('assets','replacement_value')) $table->decimal('replacement_value',14,2)->nullable();
            if (! Schema::hasColumn('assets','expected_replacement_date')) $table->date('expected_replacement_date')->nullable();
            if (! Schema::hasColumn('assets','supplier_name')) $table->string('supplier_name',180)->nullable();
            if (! Schema::hasColumn('assets','installer_name')) $table->string('installer_name',180)->nullable();
            if (! Schema::hasColumn('assets','qr_token')) $table->string('qr_token',80)->nullable()->unique();
            if (! Schema::hasColumn('assets','verification_status')) $table->string('verification_status',30)->default('DRAFT');
        });

        Schema::create('asset_contract_assignments', function (Blueprint $table) {
            $table->id(); $table->foreignId('asset_id')->constrained()->cascadeOnDelete(); $table->foreignId('service_contract_id')->constrained()->cascadeOnDelete();
            $table->date('coverage_start'); $table->date('coverage_end')->nullable(); $table->string('scope_type',40)->default('FULL_MAINTENANCE');
            $table->boolean('labour_included')->default(true); $table->boolean('parts_included')->default(false); $table->boolean('emergency_included')->default(true);
            $table->unsignedInteger('response_minutes')->nullable(); $table->unsignedInteger('resolution_minutes')->nullable(); $table->text('exclusions')->nullable();
            $table->string('status',30)->default('ACTIVE'); $table->timestamps();
        });

        Schema::create('asset_specifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('asset_id')->constrained()->cascadeOnDelete(); $table->string('spec_key',120); $table->string('spec_label',180); $table->text('spec_value')->nullable(); $table->string('unit',40)->nullable(); $table->timestamps();
            $table->unique(['asset_id','spec_key']);
        });

        Schema::create('asset_documents', function (Blueprint $table) {
            $table->id(); $table->foreignId('asset_id')->constrained()->cascadeOnDelete(); $table->string('document_type',60); $table->string('title',255); $table->string('file_path',500); $table->string('original_name',255); $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });

        Schema::create('asset_status_history', function (Blueprint $table) {
            $table->id(); $table->foreignId('asset_id')->constrained()->cascadeOnDelete(); $table->string('lifecycle_status',30)->nullable(); $table->string('operational_status',30)->nullable(); $table->text('note')->nullable(); $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('changed_at'); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_status_history'); Schema::dropIfExists('asset_documents'); Schema::dropIfExists('asset_specifications'); Schema::dropIfExists('asset_contract_assignments');
    }
};
