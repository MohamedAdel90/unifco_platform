<?php

namespace Database\Seeders;

use App\Models\{Asset,AssetMeterReading,Item,MaintenancePlan,Organization,Tenant,User,WorkOrder};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaintenanceEamDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $org = Organization::where('tenant_id', $tenant->id)->where('code', 'HQ')->firstOrFail();
        $admin = User::where('tenant_id', $tenant->id)->where('email', 'admin@unifco.local')->firstOrFail();

        if (Asset::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $assets = [];
        $assets['line'] = Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_code'=>'AST-0001',
            'name'=>'Production Line A','location_code'=>'PLANT-A','serial_no'=>'SER-1001',
            'warranty_expiry'=>'2027-01-15','acquisition_cost'=>250000,'salvage_value'=>25000,
            'useful_life_months'=>120,'accumulated_depreciation'=>50000,'net_book_value'=>200000,
            'meter_value'=>12000,'commission_date'=>'2022-01-15','status'=>'CAPITALIZED',
        ]);
        $assets['press'] = Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'parent_asset_id'=>$assets['line']->id,
            'asset_code'=>'AST-0002','name'=>'Press Machine 1000T','location_code'=>'PLANT-A','serial_no'=>'SER-2001',
            'warranty_expiry'=>'2026-12-31','acquisition_cost'=>180000,'salvage_value'=>18000,
            'useful_life_months'=>96,'accumulated_depreciation'=>30000,'net_book_value'=>150000,
            'meter_value'=>0,'commission_date'=>'2022-06-01','status'=>'CAPITALIZED',
        ]);
        $assets['forklift'] = Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_code'=>'AST-0003',
            'name'=>'Electric Forklift','location_code'=>'LOGISTICS','serial_no'=>'SER-3001',
            'warranty_expiry'=>'2026-10-15','acquisition_cost'=>45000,'salvage_value'=>4500,
            'useful_life_months'=>60,'accumulated_depreciation'=>0,'net_book_value'=>45000,
            'meter_value'=>0,'commission_date'=>null,'status'=>'REGISTERED',
        ]);
        $assets['compressor'] = Asset::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_code'=>'AST-0004',
            'name'=>'Legacy Air Compressor','location_code'=>'PLANT-B','serial_no'=>'SER-4001',
            'acquisition_cost'=>20000,'salvage_value'=>0,'accumulated_depreciation'=>20000,
            'net_book_value'=>0,'meter_value'=>0,'commission_date'=>'2019-03-01',
            'disposed_at'=>'2026-07-01 10:00:00','status'=>'DISPOSED',
        ]);

        AssetMeterReading::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_id'=>$assets['line']->id,
            'reading'=>12000,'reading_date'=>'2026-08-15','notes'=>'Weekly meter log','recorded_by'=>$admin->id,
        ]);

        $plans = [];
        $plans['pm'] = MaintenancePlan::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_id'=>$assets['line']->id,
            'plan_no'=>'MP-0001','name'=>'Quarterly PM Production Line','frequency_type'=>'DAYS',
            'frequency_value'=>90,'next_due_date'=>'2026-11-15','priority'=>'HIGH','status'=>'ACTIVE',
        ]);
        MaintenancePlan::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_id'=>$assets['forklift']->id,
            'plan_no'=>'MP-0002','name'=>'Forklift Scheduled Service','frequency_type'=>'METER',
            'frequency_value'=>1000,'next_due_meter'=>15000,'priority'=>'NORMAL','status'=>'ACTIVE',
        ]);

        $wo1 = WorkOrder::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'work_order_no'=>'WO-0001',
            'asset_id'=>$assets['line']->id,'maintenance_plan_id'=>$plans['pm']->id,
            'maintenance_type'=>'PREVENTIVE','priority'=>'HIGH','status'=>'OPEN',
            'planned_start'=>'2026-08-20 08:00:00','total_cost'=>0,
        ]);
        $wo2 = WorkOrder::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'work_order_no'=>'WO-0002',
            'asset_id'=>$assets['press']->id,'maintenance_type'=>'CORRECTIVE','priority'=>'NORMAL',
            'status'=>'IN_PROGRESS','planned_start'=>'2026-08-14 09:00:00',
            'started_at'=>'2026-08-14 09:30:00','labor_hours'=>4,'labor_cost'=>180,
            'material_cost'=>25,'external_cost'=>0,'total_cost'=>205,'downtime_minutes'=>0,
        ]);
        WorkOrder::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'work_order_no'=>'WO-0003',
            'asset_id'=>$assets['compressor']->id,'maintenance_type'=>'CORRECTIVE','priority'=>'NORMAL',
            'status'=>'COMPLETED','started_at'=>'2026-07-08 08:00:00','completed_at'=>'2026-07-10 16:00:00',
            'labor_hours'=>20,'labor_cost'=>1200,'material_cost'=>2000,'external_cost'=>0,
            'total_cost'=>3200,'downtime_minutes'=>480,'failure_code'=>'BEARING',
        ]);

        $itemId = Item::where('tenant_id',$tenant->id)->where('item_code','MRO-001')->value('id');
        DB::table('maintenance_materials')->insert([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'work_order_id'=>$wo2->id,
            'item_id'=>$itemId,'warehouse_code'=>'MAIN','quantity'=>5,'unit_cost'=>5,'total_cost'=>25,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('asset_transfers')->insert([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_id'=>$assets['line']->id,
            'from_location'=>'PLANT-A','to_location'=>'PLANT-B','transfer_date'=>'2026-08-01',
            'transferred_by'=>$admin->id,'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('asset_depreciation_entries')->insert([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_id'=>$assets['line']->id,
            'posting_date'=>'2026-08-31','amount'=>2000,'accumulated_after'=>52000,'nbv_after'=>198000,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
}