<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $t) {
            $t->timestamp('customer_accepted_at')->nullable();
            $t->timestamp('customer_rejected_at')->nullable();
            $t->text('customer_acceptance_notes')->nullable();
        });

        Schema::create('report_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('report_code',80);
            $t->string('frequency',20)->default('WEEKLY');
            $t->string('delivery_channel',20)->default('IN_APP');
            $t->string('recipient')->nullable();
            $t->timestamp('last_delivered_at')->nullable();
            $t->timestamp('next_delivery_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['is_active','next_delivery_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_subscriptions');
        Schema::table('work_orders', fn (Blueprint $t) => $t->dropColumn(['customer_accepted_at','customer_rejected_at','customer_acceptance_notes']));
    }
};
