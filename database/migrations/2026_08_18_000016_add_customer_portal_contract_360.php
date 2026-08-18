<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->after('organization_id')->constrained('customers')->nullOnDelete();
        });

        Schema::table('assets', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->after('organization_id')->constrained('customers')->nullOnDelete();
        });

        Schema::table('financial_documents', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->after('organization_id')->constrained('customers')->nullOnDelete();
        });

        Schema::table('service_requests', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->after('organization_id')->constrained('customers')->nullOnDelete();
        });

        Schema::create('service_contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->string('contract_no',60);
            $t->string('title');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->decimal('contract_value',19,2)->default(0);
            $t->string('currency',3)->default('SAR');
            $t->string('billing_cycle',30)->default('MONTHLY');
            $t->text('scope')->nullable();
            $t->text('sla_summary')->nullable();
            $t->string('status',20)->default('ACTIVE');
            $t->timestamps();
            $t->unique(['tenant_id','contract_no']);
        });

        Schema::create('contract_assets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_contract_id')->constrained('service_contracts')->cascadeOnDelete();
            $t->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $t->date('covered_from')->nullable();
            $t->date('covered_until')->nullable();
            $t->string('coverage_level',40)->default('FULL');
            $t->timestamps();
            $t->unique(['service_contract_id','asset_id']);
        });

        Schema::table('maintenance_plans', function (Blueprint $t) {
            $t->foreignId('service_contract_id')->nullable()->constrained('service_contracts')->nullOnDelete();
        });

        Schema::table('work_orders', function (Blueprint $t) {
            $t->foreignId('service_contract_id')->nullable()->constrained('service_contracts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', fn (Blueprint $t) => $t->dropConstrainedForeignId('service_contract_id'));
        Schema::table('maintenance_plans', fn (Blueprint $t) => $t->dropConstrainedForeignId('service_contract_id'));
        Schema::dropIfExists('contract_assets');
        Schema::dropIfExists('service_contracts');
        Schema::table('service_requests', fn (Blueprint $t) => $t->dropConstrainedForeignId('customer_id'));
        Schema::table('financial_documents', fn (Blueprint $t) => $t->dropConstrainedForeignId('customer_id'));
        Schema::table('assets', fn (Blueprint $t) => $t->dropConstrainedForeignId('customer_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('customer_id'));
    }
};
