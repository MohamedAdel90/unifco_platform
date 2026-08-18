<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public_service_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference_no')->unique();
            $t->string('request_type'); // QUOTATION | EMERGENCY_MAINTENANCE
            $t->string('service_category');
            $t->string('subject');
            $t->text('details');
            $t->string('site_city')->nullable();
            $t->string('urgency')->default('NORMAL');
            $t->string('company_name');
            $t->string('commercial_registration');
            $t->string('email');
            $t->string('mobile', 32);
            $t->string('status')->default('NEW');
            $t->timestamp('submitted_at');
            $t->timestamps();
            $t->index(['request_type','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_service_requests');
    }
};
