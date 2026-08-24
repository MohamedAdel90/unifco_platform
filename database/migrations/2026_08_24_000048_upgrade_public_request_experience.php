<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('public_service_requests', function (Blueprint $table) {
            $table->string('request_intent', 40)->nullable()->after('request_type');
            $table->string('service_family', 80)->nullable()->after('service_category');
            $table->string('asset_type', 120)->nullable()->after('service_family');
            $table->string('contact_role', 120)->nullable()->after('responsible_person');
            $table->json('supporting_document_paths')->nullable()->after('supporting_image_paths');
        });
    }

    public function down(): void
    {
        Schema::table('public_service_requests', function (Blueprint $table) {
            $table->dropColumn(['request_intent','service_family','asset_type','contact_role','supporting_document_paths']);
        });
    }
};
