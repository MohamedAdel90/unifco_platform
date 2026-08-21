<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('contract_manager_name',180)->nullable()->after('contact_name');
            $table->string('contract_manager_title',180)->nullable()->after('contract_manager_name');
            $table->string('project_name',255)->nullable()->after('contract_manager_title');
            $table->string('logo_path',500)->nullable()->after('project_name');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['contract_manager_name','contract_manager_title','project_name','logo_path']);
        });
    }
};
