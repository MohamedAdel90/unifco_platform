<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_portal_user_scopes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type', 30); // SITE | CONTRACT | ASSET
            $table->unsignedBigInteger('scope_id');
            $table->timestamps();
            $table->unique(['user_id','scope_type','scope_id'],'customer_portal_scope_unique');
            $table->index(['scope_type','scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_user_scopes');
    }
};
