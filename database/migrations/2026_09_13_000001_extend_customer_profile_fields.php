<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name_ar', 180)->nullable()->after('name');
            $table->string('customer_type', 40)->nullable()->after('industry');
            $table->string('website')->nullable()->after('email');
            $table->string('contact_title', 180)->nullable()->after('contact_name');
            $table->string('alternate_phone', 40)->nullable()->after('phone');
            $table->text('notes')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar',
                'customer_type',
                'website',
                'contact_title',
                'alternate_phone',
                'notes',
            ]);
        });
    }
};
