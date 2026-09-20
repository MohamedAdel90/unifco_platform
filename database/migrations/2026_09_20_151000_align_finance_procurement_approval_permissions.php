<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const GRANTS = [
        'PROCUREMENT' => ['procurement.po.approve'],
        'FINANCE_MANAGER' => ['procurement.po.approve','finance.journal.post'],
        'CEO' => ['procurement.po.approve'],
    ];

    public function up(): void
    {
        if(!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('role_permissions')) return;

        foreach(self::GRANTS as $roleCode=>$permissions){
            $role=DB::table('roles')->whereNull('tenant_id')->where('code',$roleCode)->first();
            if(!$role) continue;

            foreach($permissions as $code){
                [$module,$action]=array_pad(explode('.',$code,2),2,'access');
                DB::table('permissions')->updateOrInsert(
                    ['code'=>$code],
                    [
                        'module'=>$module,'action'=>$action,'risk_level'=>'HIGH',
                        'is_business_authority'=>true,'is_scope_aware'=>true,
                        'description'=>'Business approval gated by Approval Authority.',
                        'created_at'=>now(),'updated_at'=>now(),
                    ]
                );
                $permissionId=DB::table('permissions')->where('code',$code)->value('id');
                DB::table('role_permissions')->updateOrInsert(
                    ['tenant_id'=>null,'role_code'=>$roleCode,'permission_code'=>$code],
                    [
                        'role_id'=>$role->id,'permission_id'=>$permissionId,'effect'=>'ALLOW',
                        'created_at'=>now(),'updated_at'=>now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if(!Schema::hasTable('role_permissions')) return;
        foreach(self::GRANTS as $roleCode=>$permissions){
            DB::table('role_permissions')->where('role_code',$roleCode)->whereIn('permission_code',$permissions)->delete();
        }
    }
};
