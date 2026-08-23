<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->string('mobile',40)->nullable();
            $t->string('nationality',80)->nullable();
            $t->string('gender',20)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('marital_status',30)->nullable();
            $t->string('national_id',50)->nullable();
            $t->string('iqama_no',50)->nullable();
            $t->date('iqama_expiry')->nullable();
            $t->string('passport_no',50)->nullable();
            $t->date('passport_expiry')->nullable();
            $t->string('gosi_no',60)->nullable();
            $t->string('bank_name',120)->nullable();
            $t->string('iban',50)->nullable();
            $t->string('emergency_contact_name',160)->nullable();
            $t->string('emergency_contact_mobile',40)->nullable();
            $t->string('address_line',255)->nullable();
            $t->string('city',100)->nullable();
            $t->string('country',2)->default('SA');
            $t->string('employment_type',40)->nullable();
            $t->string('contract_type',40)->nullable();
            $t->date('probation_end_date')->nullable();
            $t->date('contract_end_date')->nullable();
            $t->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->decimal('basic_salary',19,2)->default(0);
            $t->decimal('housing_allowance',19,2)->default(0);
            $t->decimal('transport_allowance',19,2)->default(0);
            $t->decimal('other_allowances',19,2)->default(0);
            $t->string('work_location',160)->nullable();
            $t->text('notes')->nullable();
        });

        Schema::create('employment_contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('contract_no',80);
            $t->string('contract_type',40);
            $t->date('starts_on');
            $t->date('ends_on')->nullable();
            $t->date('probation_ends_on')->nullable();
            $t->decimal('basic_salary',19,2)->default(0);
            $t->decimal('housing_allowance',19,2)->default(0);
            $t->decimal('transport_allowance',19,2)->default(0);
            $t->decimal('other_allowances',19,2)->default(0);
            $t->string('currency',3)->default('SAR');
            $t->string('status',30)->default('ACTIVE');
            $t->date('signed_on')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tenant_id','contract_no']);
            $t->index(['tenant_id','employee_id','status']);
        });

        Schema::create('employee_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('document_type',60);
            $t->string('document_no',100)->nullable();
            $t->date('issued_on')->nullable();
            $t->date('expires_on')->nullable();
            $t->string('file_path')->nullable();
            $t->string('status',30)->default('VALID');
            $t->timestamps();
            $t->index(['tenant_id','employee_id','document_type']);
            $t->index(['tenant_id','expires_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employment_contracts');
        Schema::table('employees', function (Blueprint $t) {
            $t->dropConstrainedForeignId('manager_employee_id');
            $t->dropColumn([
                'mobile','nationality','gender','date_of_birth','marital_status','national_id','iqama_no','iqama_expiry','passport_no','passport_expiry','gosi_no','bank_name','iban','emergency_contact_name','emergency_contact_mobile','address_line','city','country','employment_type','contract_type','probation_end_date','contract_end_date','basic_salary','housing_allowance','transport_allowance','other_allowances','work_location','notes'
            ]);
        });
    }
};
