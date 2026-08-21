<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_service_requests', function (Blueprint $table) {
            $table->string('responsible_person', 180)->nullable()->after('company_name');
            $table->string('site_address', 500)->nullable()->after('site_city');
            $table->decimal('latitude', 10, 7)->nullable()->after('site_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->date('requested_date')->nullable()->after('urgency');
            $table->time('requested_time')->nullable()->after('requested_date');
            $table->string('equipment_image_path', 500)->nullable()->after('requested_time');
            $table->json('supporting_image_paths')->nullable()->after('equipment_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('public_service_requests', function (Blueprint $table) {
            $table->dropColumn([
                'responsible_person',
                'site_address',
                'latitude',
                'longitude',
                'requested_date',
                'requested_time',
                'equipment_image_path',
                'supporting_image_paths',
            ]);
        });
    }
};
