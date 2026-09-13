<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_data_entries',function(Blueprint $table):void{
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type',60); $table->string('code',80); $table->string('name_ar',160)->nullable(); $table->string('name_en',160);
            $table->text('description')->nullable(); $table->json('configuration')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['tenant_id','type','code']); $table->index(['tenant_id','type','is_active']);
        });
        Schema::create('email_templates',function(Blueprint $table):void{
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code',100); $table->string('subject_ar')->nullable(); $table->string('subject_en');
            $table->longText('body_ar')->nullable(); $table->longText('body_en'); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['tenant_id','code']);
        });
        foreach(['organization.manage','email_templates.manage'] as $code){
            [$module,$action]=explode('.',$code,2);
            DB::table('permissions')->updateOrInsert(['code'=>$code],[
                'module'=>$module,'action'=>$action,'risk_level'=>'NORMAL','is_business_authority'=>false,'is_scope_aware'=>false,'created_at'=>now(),'updated_at'=>now(),
            ]);
            $permissionId=DB::table('permissions')->where('code',$code)->value('id');
            foreach(DB::table('roles')->where('code','SYSTEM_ADMIN')->get() as $role){
                DB::table('role_permissions')->updateOrInsert(
                    ['tenant_id'=>$role->tenant_id,'role_code'=>$role->code,'permission_code'=>$code],
                    ['role_id'=>$role->id,'permission_id'=>$permissionId,'effect'=>'ALLOW','created_at'=>now(),'updated_at'=>now()]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->whereIn('permission_code',['organization.manage','email_templates.manage'])->delete();
        DB::table('permissions')->whereIn('code',['organization.manage','email_templates.manage'])->delete();
        Schema::dropIfExists('email_templates'); Schema::dropIfExists('master_data_entries');
    }
};
