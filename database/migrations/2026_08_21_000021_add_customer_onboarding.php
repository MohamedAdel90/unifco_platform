<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('onboarding_status',30)->default('DRAFT')->after('status');
            $table->string('commercial_registration',60)->nullable()->after('name');
            $table->string('vat_number',60)->nullable()->after('commercial_registration');
            $table->string('industry',120)->nullable()->after('vat_number');
            $table->string('country',120)->nullable()->default('Saudi Arabia')->after('city');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_portal_role',40)->nullable()->after('role');
        });
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id(); $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name',180); $table->string('job_title',180)->nullable();
            $table->string('contact_type',40)->default('PRIMARY'); $table->string('email',255)->nullable();
            $table->string('mobile',40)->nullable(); $table->boolean('is_primary')->default(false); $table->timestamps();
        });
        Schema::create('customer_sites', function (Blueprint $table) {
            $table->id(); $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('site_code',60); $table->string('name',180); $table->string('city',120)->nullable();
            $table->string('address',500)->nullable(); $table->decimal('latitude',10,7)->nullable(); $table->decimal('longitude',10,7)->nullable();
            $table->string('contact_name',180)->nullable(); $table->string('contact_mobile',40)->nullable(); $table->string('status',30)->default('ACTIVE');
            $table->timestamps(); $table->unique(['customer_id','site_code']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('customer_sites'); Schema::dropIfExists('customer_contacts');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('customer_portal_role'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn(['onboarding_status','commercial_registration','vat_number','industry','country']));
    }
};
