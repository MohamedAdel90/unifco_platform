<?php

namespace App\Console\Commands;

use App\Models\{Organization,Tenant,User,Warehouse};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB,Hash};

class BootstrapWarehouseAccess extends Command
{
    protected $signature='unifco:bootstrap-warehouse-access';
    protected $description='Create the UNIFCO main spare parts warehouse and dedicated storekeeper access.';

    public function handle(): int
    {
        $tenant=Tenant::where('code','UNIFCO')->first();
        if(!$tenant){ $this->warn('UNIFCO tenant not found; warehouse access bootstrap skipped.'); return self::SUCCESS; }
        $org=Organization::where('tenant_id',$tenant->id)->where('code','HQ')->first();

        $warehouse=Warehouse::firstOrCreate(
            ['tenant_id'=>$tenant->id,'code'=>'MAIN-WH'],
            ['organization_id'=>$org?->id,'name'=>'UNIFCO Main Spare Parts Warehouse','location_type'=>'CENTRAL','is_mobile'=>false,'allow_negative_stock'=>false,'status'=>'ACTIVE']
        );

        $email=env('UNIFCO_STOREKEEPER_EMAIL','storekeeper@unifco.local');
        $password=env('UNIFCO_STOREKEEPER_PASSWORD','ChangeMe123!');
        $user=User::firstOrCreate(
            ['email'=>$email],
            ['tenant_id'=>$tenant->id,'organization_id'=>$org?->id,'name'=>'UNIFCO Storekeeper','password'=>Hash::make($password),'role'=>'STOREKEEPER','status'=>'ACTIVE']
        );
        if($user->role!=='STOREKEEPER' || $user->tenant_id!==$tenant->id){
            $this->error('Configured storekeeper email already belongs to another account.');
            return self::FAILURE;
        }

        DB::table('warehouse_user_assignments')->updateOrInsert(
            ['warehouse_id'=>$warehouse->id,'user_id'=>$user->id],
            ['tenant_id'=>$tenant->id,'access_level'=>'MANAGER','created_at'=>now(),'updated_at'=>now()]
        );

        $this->info('Warehouse access ready: '.$warehouse->code.' / '.$email);
        return self::SUCCESS;
    }
}
