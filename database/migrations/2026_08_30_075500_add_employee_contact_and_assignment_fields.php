<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('saudi_city', 100)->nullable()->after('country');
            $table->string('saudi_region', 100)->nullable()->after('saudi_city');
            $table->string('birth_contact_country_code', 8)->nullable()->after('saudi_region');
            $table->string('birth_contact_mobile', 30)->nullable()->after('birth_contact_country_code');
            $table->string('project_name', 160)->nullable()->after('work_location');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['saudi_city','saudi_region','birth_contact_country_code','birth_contact_mobile','project_name']);
        });
    }
};
