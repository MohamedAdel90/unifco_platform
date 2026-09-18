<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('operational_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->string('code', 80);
            $table->string('name_en');
            $table->string('name_ar');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['tenant_id','code']);
        });

        Schema::create('user_operational_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operational_domain_id')->constrained()->cascadeOnDelete();
            $table->enum('assignment_type', ['PRIMARY','BACKUP','ESCALATION'])->default('PRIMARY');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id','operational_domain_id','assignment_type'], 'user_domain_assignment_unique');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('operational_domain_id')->nullable()->after('asset_category_template_id')->constrained('operational_domains')->nullOnDelete();
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('operational_domain_id')->nullable()->after('asset_id')->constrained('operational_domains')->nullOnDelete();
            $table->foreignId('operations_manager_id')->nullable()->after('assigned_engineer_id')->constrained('users')->nullOnDelete();
            $table->string('operations_routing_status', 30)->default('UNASSIGNED')->after('operations_manager_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operational_domain_id');
            $table->dropConstrainedForeignId('operations_manager_id');
            $table->dropColumn('operations_routing_status');
        });
        Schema::table('assets', fn (Blueprint $table) => $table->dropConstrainedForeignId('operational_domain_id'));
        Schema::dropIfExists('user_operational_domains');
        Schema::dropIfExists('operational_domains');
    }
};
