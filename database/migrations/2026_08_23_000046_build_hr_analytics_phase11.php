<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_workforce_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedSmallInteger('plan_year');
            $t->string('department',160)->default('ALL');
            $t->unsignedInteger('target_headcount')->default(0);
            $t->decimal('budgeted_monthly_cost',16,2)->default(0);
            $t->decimal('target_saudi_pct',5,2)->nullable();
            $t->text('notes')->nullable();
            $t->string('status',20)->default('ACTIVE');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tenant_id','organization_id','plan_year','department'],'hr_workforce_plan_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_workforce_plans');
    }
};
