<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('first_name',80)->nullable()->after('name');
            $table->string('middle_name',80)->nullable()->after('first_name');
            $table->string('last_name',80)->nullable()->after('middle_name');
            $table->string('official_email')->nullable()->after('email');
            $table->string('mobile_country_code',8)->default('+966')->after('mobile');
            $table->string('emergency_mobile_country_code',8)->default('+966')->after('emergency_contact_mobile');
            $table->unique(['tenant_id','official_email'],'employees_tenant_official_email_unique');
        });

        DB::table('employees')->orderBy('id')->get(['id','name'])->each(function ($employee) {
            $parts=preg_split('/\s+/',trim((string)$employee->name),-1,PREG_SPLIT_NO_EMPTY) ?: [];
            $first=array_shift($parts);
            $last=count($parts) ? array_pop($parts) : null;
            DB::table('employees')->where('id',$employee->id)->update([
                'first_name'=>$first ?: $employee->name,
                'middle_name'=>count($parts) ? implode(' ',$parts) : null,
                'last_name'=>$last,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_tenant_official_email_unique');
            $table->dropColumn(['first_name','middle_name','last_name','official_email','mobile_country_code','emergency_mobile_country_code']);
        });
    }
};
