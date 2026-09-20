<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const ROLES = [
        'CEO',
        'OPERATIONS_MANAGER',
        'MAINTENANCE_MANAGER',
        'PROJECT_MANAGER',
        'MAINTENANCE_ENGINEER',
        'QUALITY',
        'HSE',
        'FINANCE_MANAGER',
        'SALES',
        'PROCUREMENT',
        'TENDERS_CONTRACTS',
        'CUSTOMER_SERVICE',
    ];

    private const PERMISSIONS = [
        'workflow.approval.read',
        'workflow.approval.decide',
    ];

    public function up(): void
    {
        if(!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('role_permissions')) return;

        foreach(self::PERMISSIONS as $code){
            [$module,$action]=array_pad(explode('.',$code,2),2,'access');
            DB::table('permissions')->updateOrInsert(
                ['code'=>$code],
                [
                    'module'=>$module,
                    'action'=>$action,
                    'risk_level'=>$action==='decide'?'HIGH':'NORMAL',
                    'is_business_authority'=>$action==='decide',
                    'is_scope_aware'=>true,
                    'description'=>'Governed workflow approval '.$action,
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]
            );
        }

        foreach(self::ROLES as $roleCode){
            $role=DB::table('roles')->whereNull('tenant_id')->where('code',$roleCode)->first();
            if(!$role) continue;

            foreach(self::PERMISSIONS as $permissionCode){
                $permissionId=DB::table('permissions')->where('code',$permissionCode)->value('id');
                DB::table('role_permissions')->updateOrInsert(
                    ['tenant_id'=>null,'role_code'=>$roleCode,'permission_code'=>$permissionCode],
                    [
                        'role_id'=>$role->id,
                        'permission_id'=>$permissionId,
                        'effect'=>'ALLOW',
                        'updated_at'=>now(),
                        'created_at'=>now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if(!Schema::hasTable('role_permissions')) return;
        DB::table('role_permissions')
            ->whereIn('role_code',self::ROLES)
            ->whereIn('permission_code',self::PERMISSIONS)
            ->delete();
    }
};
