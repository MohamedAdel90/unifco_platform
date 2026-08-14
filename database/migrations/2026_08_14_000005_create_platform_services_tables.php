<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type')->default('INFO');
            $t->string('title');
            $t->text('message')->nullable();
            $t->string('action_url')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['tenant_id','user_id','read_at']);
        });

        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('document_no');
            $t->string('title');
            $t->string('original_name');
            $t->string('storage_path');
            $t->string('mime_type')->nullable();
            $t->unsignedBigInteger('size_bytes')->default(0);
            $t->string('entity_type')->nullable();
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->string('status')->default('ACTIVE');
            $t->timestamps();
            $t->unique(['tenant_id','document_no']);
            $t->index(['tenant_id','entity_type','entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('platform_notifications');
    }
};