<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('public_service_requests', function (Blueprint $table) {
            $table->string('request_subtype', 60)->nullable()->after('request_intent');
            $table->unsignedBigInteger('ticket_serial')->nullable()->unique()->after('reference_no');
            $table->string('site_name', 180)->nullable()->after('details');
            $table->string('site_area', 120)->nullable()->after('site_name');
            $table->string('equipment_brand', 120)->nullable()->after('asset_type');
            $table->string('equipment_model', 120)->nullable()->after('equipment_brand');
            $table->string('service_other', 180)->nullable()->after('service_category');
            $table->json('equipment_photo_paths')->nullable()->after('equipment_image_path');
            $table->json('problem_photo_paths')->nullable()->after('equipment_photo_paths');
            $table->json('previous_report_paths')->nullable()->after('supporting_document_paths');
        });

        Schema::create('public_request_counters', function (Blueprint $table) {
            $table->string('key', 40)->primary();
            $table->unsignedBigInteger('last_number');
            $table->timestamps();
        });

        DB::table('public_request_counters')->insert([
            'key' => 'public_request_ticket',
            'last_number' => 926000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('public_request_counters');
        Schema::table('public_service_requests', function (Blueprint $table) {
            $table->dropUnique(['ticket_serial']);
            $table->dropColumn([
                'request_subtype','ticket_serial','site_name','site_area','equipment_brand','equipment_model',
                'service_other','equipment_photo_paths','problem_photo_paths','previous_report_paths',
            ]);
        });
    }
};
