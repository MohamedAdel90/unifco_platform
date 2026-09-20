<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('approval_authorities', function (Blueprint $table): void {
            $table->decimal('amount_from',19,2)->nullable()->after('access_scope_id');
            $table->decimal('amount_to',19,2)->nullable()->after('amount_from');
        });
    }

    public function down(): void
    {
        Schema::table('approval_authorities', function (Blueprint $table): void {
            $table->dropColumn(['amount_from','amount_to']);
        });
    }
};
