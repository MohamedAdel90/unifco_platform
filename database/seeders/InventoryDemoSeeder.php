<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $admin = User::where('tenant_id', $tenant->id)->where('email', 'admin@unifco.local')->firstOrFail();

        $exists = DB::table('stock_balances')->where('tenant_id', $tenant->id)->exists();
        if ($exists) {
            return;
        }

        $itemId = static fn (string $code) => Item::where('tenant_id',$tenant->id)->where('item_code',$code)->value('id');

        $stock = [
            ['RAW-001', 'MAIN', 1000], ['RAW-001', 'RAW', 5000],
            ['RAW-002', 'MAIN', 800], ['RAW-002', 'RAW', 4000],
            ['RAW-003', 'MAIN', 2000], ['RAW-003', 'RAW', 9000],
            ['RAW-004', 'MAIN', 300], ['RAW-004', 'RAW', 1500],
            ['FG-001', 'MAIN', 150], ['FG-001', 'FG', 250],
            ['MRO-001', 'MAIN', 40],
        ];
        foreach ($stock as [$code,$warehouse,$qty]) {
            DB::table('stock_balances')->updateOrInsert(
                ['tenant_id'=>$tenant->id,'item_id'=>$itemId($code),'warehouse_code'=>$warehouse],
                ['quantity'=>$qty,'reserved_quantity'=>0,'created_at'=>now(),'updated_at'=>now()],
            );
        }

        DB::table('inventory_transfers')->insert([
            'tenant_id'=>$tenant->id,'item_id'=>$itemId('RAW-001'),'transfer_no'=>'TR-2026-0001',
            'from_warehouse_code'=>'RAW','to_warehouse_code'=>'MAIN','quantity'=>200,
            'status'=>'POSTED','created_by'=>$admin->id,'created_at'=>now(),'updated_at'=>now(),
        ]);

        DB::table('inventory_counts')->insert([
            'tenant_id'=>$tenant->id,'item_id'=>$itemId('RAW-001'),'count_no'=>'CC-2026-0001',
            'warehouse_code'=>'MAIN','system_quantity'=>1000,'counted_quantity'=>1000,'variance'=>0,
            'status'=>'POSTED','created_by'=>$admin->id,'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
}