<?php

namespace Database\Seeders;

use App\Models\{Bom,BomLine,Item,Organization,ProductionOrder,Routing,RoutingOperation,Tenant,User,WorkCenter};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManufacturingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $org = Organization::where('tenant_id', $tenant->id)->where('code', 'HQ')->firstOrFail();
        $admin = User::where('tenant_id', $tenant->id)->where('email', 'admin@unifco.local')->firstOrFail();

        if (WorkCenter::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $itemId = static fn (string $code) => Item::where('tenant_id',$tenant->id)->where('item_code',$code)->value('id');

        foreach ([
            ['WC-001','Machining Center',45], ['WC-002','Assembly Line',35], ['WC-003','Packaging Station',20],
        ] as [$code,$name,$rate]) {
            WorkCenter::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>$code,'name'=>$name,'hourly_rate'=>$rate,'status'=>'ACTIVE']);
        }

        $bom = Bom::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'product_item_id'=>$itemId('FG-001'),'bom_no'=>'BOM-0001','version'=>1,'status'=>'ACTIVE']);
        foreach ([
            ['RAW-001', 2, 8.00], ['RAW-003', 10, 0.50], ['RAW-004', 1, 5.00],
        ] as $i => [$code,$qty,$cost]) {
            BomLine::create(['bom_id'=>$bom->id,'line_no'=>$i + 1,'component_item_id'=>$itemId($code),'quantity_per'=>$qty,'standard_unit_cost'=>$cost]);
        }

        $routing = Routing::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'product_item_id'=>$itemId('FG-001'),'routing_no'=>'RT-0001','status'=>'ACTIVE']);
        $workCenter = static fn (string $code) => WorkCenter::where('tenant_id',$tenant->id)->where('code',$code)->value('id');
        foreach ([
            [10,'WC-001','Machining',2], [20,'WC-002','Assembly',1.5], [30,'WC-003','Packaging',0.5],
        ] as [$seq,$wc,$name,$hours]) {
            RoutingOperation::create(['routing_id'=>$routing->id,'sequence'=>$seq,'work_center_id'=>$workCenter($wc),'operation_name'=>$name,'standard_hours'=>$hours]);
        }

        $orders = [
            ['MO-2026-0001', 'Finished Widget', 100, 50, 'RELEASED', 20000, 19500, -500],
            ['MO-2026-0002', 'Finished Widget', 50, 0, 'CREATED', 10000, 0, 0],
        ];
        $createdOrders = [];
        foreach ($orders as [$no,$product,$planned,$produced,$status,$std,$act,$var]) {
            $createdOrders[] = ProductionOrder::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'order_no'=>$no,'product_name'=>$product,
                'planned_quantity'=>$planned,'produced_quantity'=>$produced,'status'=>$status,
                'item_id'=>$itemId('FG-001'),'bom_id'=>$bom->id,'routing_id'=>$routing->id,
                'warehouse_code'=>'FG','standard_cost'=>$std,'actual_cost'=>$act,'cost_variance'=>$var,
            ]);
        }

        $mo1 = $createdOrders[0];
        DB::table('production_materials')->insert([
            ['tenant_id'=>$tenant->id,'production_order_id'=>$mo1->id,'item_id'=>$itemId('RAW-001'),'planned_quantity'=>200,'issued_quantity'=>200,'unit_cost'=>8,'created_at'=>now(),'updated_at'=>now()],
            ['tenant_id'=>$tenant->id,'production_order_id'=>$mo1->id,'item_id'=>$itemId('RAW-003'),'planned_quantity'=>1000,'issued_quantity'=>900,'unit_cost'=>0.5,'created_at'=>now(),'updated_at'=>now()],
        ]);
        DB::table('production_confirmations')->insert([
            'tenant_id'=>$tenant->id,'production_order_id'=>$mo1->id,'work_center_id'=>$workCenter('WC-001'),
            'hours'=>2,'good_quantity'=>50,'scrap_quantity'=>0,'created_by'=>$admin->id,'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('quality_inspections')->insert([
            'tenant_id'=>$tenant->id,'production_order_id'=>$mo1->id,'result'=>'PASSED','notes'=>'All dimensions within tolerance',
            'inspected_by'=>$admin->id,'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
}