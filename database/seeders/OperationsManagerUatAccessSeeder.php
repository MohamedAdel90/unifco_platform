<?php

namespace Database\Seeders;

use App\Models\{AccessScope,Role,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationsManagerUatAccessSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('user_roles') || !DB::getSchemaBuilder()->hasTable('access_scopes')) return;

        $user=User::where('email','operations.manager@unifco.local')->first();
        $role=Role::whereNull('tenant_id')->where('code','OPERATIONS_MANAGER')->first();
        if (!$user || !$role) return;

        DB::table('user_roles')->updateOrInsert(
            ['user_id'=>$user->id,'role_id'=>$role->id],
            [
                'tenant_id'=>$user->tenant_id,'is_primary'=>true,'granted_at'=>now(),'revoked_at'=>null,
                'reason'=>'Workflow UAT Operations Manager','created_at'=>now(),'updated_at'=>now(),
            ]
        );

        $scope=AccessScope::firstOrCreate(
            ['tenant_id'=>$user->tenant_id,'scope_type'=>'GLOBAL','scope_id'=>null],
            ['name'=>'Operations UAT Global','is_active'=>true]
        );
        DB::table('user_scopes')->updateOrInsert(
            ['user_id'=>$user->id,'access_scope_id'=>$scope->id],
            [
                'tenant_id'=>$user->tenant_id,'source'=>'SYSTEM','reason'=>'Workflow UAT Operations Manager',
                'created_at'=>now(),'updated_at'=>now(),
            ]
        );
    }
}
