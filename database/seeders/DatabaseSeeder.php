<?php

namespace Database\Seeders;

use App\Models\{Organization,Tenant,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(['code'=>'UNIFCO'], ['name'=>'UNIFCO','status'=>'ACTIVE']);
        $org = Organization::firstOrCreate(['tenant_id'=>$tenant->id,'code'=>'HQ'], ['name'=>'UNIFCO HQ','status'=>'ACTIVE']);
        User::firstOrCreate(['email'=>'admin@unifco.local'], [
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'UNIFCO Administrator',
            'password'=>Hash::make(env('UNIFCO_ADMIN_PASSWORD','ChangeMe123!')),'role'=>'ADMIN','status'=>'ACTIVE',
        ]);
    }
}
