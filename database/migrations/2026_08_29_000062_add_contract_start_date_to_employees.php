<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'contract_start_date')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->date('contract_start_date')->nullable()->after('probation_end_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'contract_start_date')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('contract_start_date');
            });
        }
    }
};
