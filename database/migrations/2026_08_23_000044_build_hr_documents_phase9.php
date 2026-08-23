<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_document_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code',80); $t->string('name',160); $t->string('document_type',40); $t->string('language',5)->default('EN');
            $t->string('subject',220); $t->text('body_template'); $t->boolean('include_salary')->default(false); $t->string('status',20)->default('ACTIVE');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
            $t->unique(['tenant_id','code']); $t->index(['tenant_id','document_type','language','status']);
        });

        Schema::create('hr_service_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('request_no',60); $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('request_type',40); $t->string('language',5)->default('EN'); $t->string('recipient_name',180)->nullable(); $t->string('purpose',500)->nullable();
            $t->json('requested_changes')->nullable(); $t->text('notes')->nullable(); $t->string('status',30)->default('PENDING');
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('decided_at')->nullable(); $t->text('decision_notes')->nullable();
            $t->foreignId('template_id')->nullable()->constrained('hr_document_templates')->nullOnDelete();
            $t->string('document_no',80)->nullable(); $t->string('verification_token',80)->nullable(); $t->json('snapshot')->nullable();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('issued_at')->nullable(); $t->timestamps();
            $t->unique(['tenant_id','request_no']); $t->unique('verification_token'); $t->index(['tenant_id','employee_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_service_requests');
        Schema::dropIfExists('hr_document_templates');
    }
};
