<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('public_service_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('public_service_requests', 'conversion_attempts')) {
                $table->unsignedSmallInteger('conversion_attempts')->default(0)->after('converted_at');
            }
            if (! Schema::hasColumn('public_service_requests', 'last_conversion_attempt_at')) {
                $table->timestamp('last_conversion_attempt_at')->nullable()->after('conversion_attempts');
            }
            if (! Schema::hasColumn('public_service_requests', 'conversion_error')) {
                $table->text('conversion_error')->nullable()->after('last_conversion_attempt_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('public_service_requests', function (Blueprint $table): void {
            foreach (['conversion_attempts','last_conversion_attempt_at','conversion_error'] as $column) {
                if (Schema::hasColumn('public_service_requests', $column)) $table->dropColumn($column);
            }
        });
    }
};
