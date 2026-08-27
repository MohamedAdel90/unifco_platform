<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_documents','tenant_id')) $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            if (!Schema::hasColumn('asset_documents','organization_id')) $table->unsignedBigInteger('organization_id')->nullable()->after('tenant_id');
            if (!Schema::hasColumn('asset_documents','path')) $table->string('path',500)->nullable()->after('title');
            if (!Schema::hasColumn('asset_documents','mime_type')) $table->string('mime_type',120)->nullable()->after('original_name');
            if (!Schema::hasColumn('asset_documents','version')) $table->string('version',30)->nullable()->after('mime_type');
            if (!Schema::hasColumn('asset_documents','issued_at')) $table->date('issued_at')->nullable()->after('version');
            if (!Schema::hasColumn('asset_documents','expires_at')) $table->date('expires_at')->nullable()->after('issued_at');
        });

        DB::statement('UPDATE asset_documents d JOIN assets a ON a.id=d.asset_id SET d.tenant_id=a.tenant_id, d.organization_id=a.organization_id WHERE d.tenant_id IS NULL');
        if (Schema::hasColumn('asset_documents','file_path')) {
            DB::statement('UPDATE asset_documents SET path=file_path WHERE path IS NULL OR path=""');
        }

        Schema::table('asset_documents', function (Blueprint $table) {
            $table->index(['tenant_id','asset_id','document_type'],'asset_document_scope_idx');
            $table->index('expires_at','asset_document_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::table('asset_documents', function (Blueprint $table) {
            $table->dropIndex('asset_document_scope_idx');
            $table->dropIndex('asset_document_expiry_idx');
            $table->dropColumn(['tenant_id','organization_id','path','mime_type','version','issued_at','expires_at']);
        });
    }
};
