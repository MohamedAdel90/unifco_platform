<?php

namespace Tests\Feature;

use App\Models\{Asset,Item,MaintenancePlan,Organization,Tenant,User,Warehouse,WorkOrder};
use App\Services\Inventory\StockService;
use App\Services\Maintenance\MaintenanceEamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Wave13MaintenanceEamTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $tenant=Tenant::create(['code'=>'EAM13','name'=>'EAM Tenant','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'code'=>'OPS','name'=>'Operations','status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'EAM Admin','email'=>'eam13@test.local','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
    }

    public function test_preventive_plan_generates_work_order_and_tracks_costs(): void
    {
        $user=$this->admin(); $this->actingAs($user);
        $asset=Asset::create(['organization_id'=>$user->organization_id,'asset_code'=>'A-13','name'=>'Pump','location_code'=>'PLANT','acquisition_cost'=>12000,'net_book_value'=>12000,'commission_date'=>'2026-01-01','status'=>'CAPITALIZED']);
        $item=Item::create(['item_code'=>'SP-13','name'=>'Seal Kit','uom'=>'EA','status'=>'ACTIVE']);
        Warehouse::create(['code'=>'MAIN','name'=>'Main','status'=>'ACTIVE']);
        app(StockService::class)->move($item,'MAIN','RECEIPT',10,'wave13-seed');
        MaintenancePlan::create(['organization_id'=>$user->organization_id,'asset_id'=>$asset->id,'plan_no'=>'PM-13','name'=>'Monthly PM','frequency_type'=>'MONTHS','frequency_value'=>1,'next_due_date'=>today()->toDateString(),'priority'=>'HIGH','status'=>'ACTIVE']);

        $service=app(MaintenanceEamService::class);
        $this->assertSame(1,$service->generateDueWorkOrders());
        $order=WorkOrder::firstOrFail();
        $service->start($order); $service->addLabor($order->fresh(),2,25); $service->issueMaterial($order->fresh(),$item,'MAIN',2,15);
        $completed=$service->complete($order->fresh(),90,'BEARING',20);

        $this->assertSame('COMPLETED',$completed->status);
        $this->assertEquals(100.0,(float)$completed->total_cost);
        $this->assertEquals(8.0,(float)DB::table('stock_balances')->where(['item_id'=>$item->id,'warehouse_code'=>'MAIN'])->value('quantity'));
        $this->assertDatabaseHas('maintenance_materials',['work_order_id'=>$order->id,'quantity'=>2]);
        $this->assertSame(0,$service->generateDueWorkOrders());
    }

    public function test_asset_meter_transfer_depreciation_and_disposal_lifecycle(): void
    {
        $user=$this->admin(); $this->actingAs($user);
        $parent=Asset::create(['organization_id'=>$user->organization_id,'asset_code'=>'PARENT-13','name'=>'Plant','acquisition_cost'=>0,'commission_date'=>'2026-01-01','status'=>'REGISTERED']);
        $asset=Asset::create(['organization_id'=>$user->organization_id,'parent_asset_id'=>$parent->id,'asset_code'=>'M-13','name'=>'Motor','location_code'=>'LINE-A','acquisition_cost'=>12000,'salvage_value'=>0,'useful_life_months'=>120,'accumulated_depreciation'=>0,'net_book_value'=>12000,'commission_date'=>'2026-01-01','status'=>'CAPITALIZED']);
        $service=app(MaintenanceEamService::class);
        $service->recordMeter($asset,500,'2026-08-15','Runtime');
        $service->transferAsset($asset->fresh(),'LINE-B','2026-08-15');
        $depreciated=$service->depreciate($asset->fresh(),'2026-08-31');

        $this->assertEquals(100.0,(float)$depreciated->accumulated_depreciation);
        $this->assertEquals(11900.0,(float)$depreciated->net_book_value);
        $this->assertSame('LINE-B',$depreciated->location_code);
        $this->assertEquals(500.0,(float)$depreciated->meter_value);
        $disposed=$service->dispose($depreciated);
        $this->assertSame('DISPOSED',$disposed->status);
        $this->assertDatabaseHas('asset_transfers',['asset_id'=>$asset->id,'to_location'=>'LINE-B']);
        $this->assertDatabaseHas('asset_depreciation_entries',['asset_id'=>$asset->id,'amount'=>100]);
    }
}
