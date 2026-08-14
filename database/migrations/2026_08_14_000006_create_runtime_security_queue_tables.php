<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_tokens',function(Blueprint $t){
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name'); $t->string('token_hash',64)->unique(); $t->json('abilities')->nullable(); $t->timestamp('last_used_at')->nullable(); $t->timestamp('expires_at')->nullable(); $t->timestamp('revoked_at')->nullable(); $t->timestamps();
            $t->index(['tenant_id','user_id','revoked_at']);
        });
        Schema::create('jobs',function(Blueprint $t){ $t->bigIncrements('id'); $t->string('queue')->index(); $t->longText('payload'); $t->unsignedTinyInteger('attempts'); $t->unsignedInteger('reserved_at')->nullable(); $t->unsignedInteger('available_at'); $t->unsignedInteger('created_at'); });
        Schema::create('job_batches',function(Blueprint $t){ $t->string('id')->primary(); $t->string('name'); $t->integer('total_jobs'); $t->integer('pending_jobs'); $t->integer('failed_jobs'); $t->longText('failed_job_ids'); $t->mediumText('options')->nullable(); $t->integer('cancelled_at')->nullable(); $t->integer('created_at'); $t->integer('finished_at')->nullable(); });
        Schema::create('failed_jobs',function(Blueprint $t){ $t->id(); $t->string('uuid')->unique(); $t->text('connection'); $t->text('queue'); $t->longText('payload'); $t->longText('exception'); $t->timestamp('failed_at')->useCurrent(); });
    }
    public function down(): void { Schema::dropIfExists('failed_jobs'); Schema::dropIfExists('job_batches'); Schema::dropIfExists('jobs'); Schema::dropIfExists('api_tokens'); }
};
