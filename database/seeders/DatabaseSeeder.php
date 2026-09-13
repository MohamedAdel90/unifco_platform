<?php

namespace Database\Seeders;

use App\Models\{AccessScope,Organization,Role,Tenant,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(['code'=>'UNIFCO'], ['name'=>'UNIFCO','status'=>'ACTIVE']);
        $org = Organization::firstOrCreate(['tenant_id'=>$tenant->id,'code'=>'HQ'], ['name'=>'UNIFCO HQ','status'=>'ACTIVE']);
        $admin=User::firstOrCreate(['email'=>'admin@unifco.local'], [
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'UNIFCO Administrator',
            'password'=>Hash::make(env('UNIFCO_ADMIN_PASSWORD','ChangeMe123!')),'role'=>'SYSTEM_ADMIN','user_type'=>'INTERNAL','status'=>'ACTIVE',
        ]);
        if (DB::getSchemaBuilder()->hasTable('user_roles')) {
            $role=Role::whereNull('tenant_id')->where('code','SYSTEM_ADMIN')->firstOrFail();
            DB::table('user_roles')->updateOrInsert(['user_id'=>$admin->id,'role_id'=>$role->id],[
                'tenant_id'=>$tenant->id,'is_primary'=>true,'granted_at'=>now(),'revoked_at'=>null,
                'reason'=>'Initial System Administrator','created_at'=>now(),'updated_at'=>now(),
            ]);
            $scope=AccessScope::firstOrCreate(['tenant_id'=>$tenant->id,'scope_type'=>'GLOBAL','scope_id'=>null],['name'=>'Global','is_active'=>true]);
            DB::table('user_scopes')->updateOrInsert(['user_id'=>$admin->id,'access_scope_id'=>$scope->id],[
                'tenant_id'=>$tenant->id,'source'=>'SYSTEM','reason'=>'Initial administrative scope','created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        if (! app()->environment('testing')) {
            $this->call([
                CoreDemoSeeder::class,
                FinanceDemoSeeder::class,
                ProcurementDemoSeeder::class,
                InventoryDemoSeeder::class,
                HrDemoSeeder::class,
                CrmDemoSeeder::class,
                ProjectsDemoSeeder::class,
                ManufacturingDemoSeeder::class,
                MaintenanceEamDemoSeeder::class,
                PlatformDemoSeeder::class,
                WorkflowTestUsersSeeder::class,
            ]);
        }
    }
}
