<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->string('gosi_status',30)->nullable()->after('gosi_no');
            $t->date('gosi_registered_on')->nullable()->after('gosi_status');
        });
        Schema::table('employment_contracts', function (Blueprint $t) {
            $t->string('qiwa_status',30)->nullable()->after('signed_on');
            $t->string('qiwa_contract_ref',120)->nullable()->after('qiwa_status');
            $t->date('qiwa_documented_on')->nullable()->after('qiwa_contract_ref');
        });
        Schema::create('hr_compliance_profiles', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('economic_activity',180)->nullable(); $t->decimal('qiwa_contract_target_pct',5,2)->default(90); $t->string('nitaqat_reported_band',40)->nullable();
            $t->string('wps_status',30)->default('NOT_REVIEWED'); $t->date('last_wps_period')->nullable(); $t->string('mudad_reference',120)->nullable();
            $t->date('last_gosi_reconciliation_on')->nullable(); $t->date('last_qiwa_reconciliation_on')->nullable(); $t->date('last_nitaqat_review_on')->nullable();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps(); $t->unique(['tenant_id','organization_id']);
        });
        Schema::create('hr_compliance_cases', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('case_no',60); $t->string('category',40); $t->string('severity',20); $t->string('title',220); $t->text('description');
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete(); $t->string('source_key',180)->nullable(); $t->date('due_on')->nullable();
            $t->string('status',30)->default('OPEN'); $t->text('remediation_notes')->nullable(); $t->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('resolved_at')->nullable(); $t->timestamps();
            $t->unique(['tenant_id','case_no']); $t->index(['tenant_id','category','status','severity']); $t->index(['tenant_id','source_key','status']);
        });
        Schema::create('hr_compliance_scan_runs', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained()->cascadeOnDelete(); $t->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $t->string('scan_no',60); $t->json('summary'); $t->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('completed_at'); $t->timestamps();
            $t->unique(['tenant_id','scan_no']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('hr_compliance_scan_runs'); Schema::dropIfExists('hr_compliance_cases'); Schema::dropIfExists('hr_compliance_profiles');
        Schema::table('employment_contracts',fn(Blueprint $t)=>$t->dropColumn(['qiwa_status','qiwa_contract_ref','qiwa_documented_on']));
        Schema::table('employees',fn(Blueprint $t)=>$t->dropColumn(['gosi_status','gosi_registered_on']));
    }
};
