<?php

namespace Tests\Feature;

use App\Models\{Item,Organization,Tenant,User,Warehouse};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WarehouseFieldInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $tenant=Tenant::create(['name'=>'UNIFCO Test','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>'warehouse-admin@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
    }

    public function test_admin_can_open_spare_parts_control_center(): void
    {
        $admin=$this->admin();
        $this->actingAs($admin)->get('/inventory/warehouse-control')
            ->assertOk()->assertSee('Spare Parts Control Center')->assertSee('INTERNAL UNIFCO ONLY');
    }

    public function test_stock_can_move_from_main_warehouse_to_technician_vehicle_with_issue_and_receipt(): void
    {
        $admin=$this->admin();
        $main=Warehouse::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'code'=>'MAIN-WH','name'=>'Main Warehouse','location_type'=>'CENTRAL','status'=>'ACTIVE']);
        $van=Warehouse::create(['tenant_id'=>$admin->tenant_id,'organization_id'=>$admin->organization_id,'code'=>'VAN-001','name'=>'Technician Van 001','location_type'=>'VEHICLE','vehicle_code'=>'VAN-001','plate_no'=>'TEST-001','is_mobile'=>true,'status'=>'ACTIVE']);
        $item=Item::create(['tenant_id'=>$admin->tenant_id,'item_code'=>'SP-001','name'=>'Test Contactor','uom'=>'EA','status'=>'ACTIVE']);
        DB::table('stock_balances')->insert(['tenant_id'=>$admin->tenant_id,'item_id'=>$item->id,'warehouse_code'=>$main->code,'quantity'=>10,'reserved_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);

        $this->actingAs($admin)->post('/inventory/transfers',[
            'from_warehouse_id'=>$main->id,'to_warehouse_id'=>$van->id,'purpose'=>'Van replenishment','item_id'=>[$item->id],'quantity'=>[3],
        ])->assertSessionHasNoErrors();

        $transfer=DB::table('inventory_transfer_orders')->first();
        $this->assertSame('REQUESTED',$transfer->status);

        $this->actingAs($admin)->post('/inventory/transfers/'.$transfer->id.'/issue')->assertSessionHasNoErrors();
        $this->assertSame('IN_TRANSIT',DB::table('inventory_transfer_orders')->where('id',$transfer->id)->value('status'));
        $this->assertEquals(7.0,(float)DB::table('stock_balances')->where(['tenant_id'=>$admin->tenant_id,'item_id'=>$item->id,'warehouse_code'=>'MAIN-WH'])->value('quantity'));

        $this->actingAs($admin)->post('/inventory/transfers/'.$transfer->id.'/receive')->assertSessionHasNoErrors();
        $this->assertSame('RECEIVED',DB::table('inventory_transfer_orders')->where('id',$transfer->id)->value('status'));
        $this->assertEquals(3.0,(float)DB::table('stock_balances')->where(['tenant_id'=>$admin->tenant_id,'item_id'=>$item->id,'warehouse_code'=>'VAN-001'])->value('quantity'));
    }

    public function test_bootstrap_creates_dedicated_storekeeper_and_main_warehouse_access(): void
    {
        $admin=$this->admin();
        $this->artisan('unifco:bootstrap-warehouse-access')->assertSuccessful();
        $storekeeper=User::where('email','storekeeper@unifco.local')->firstOrFail();
        $this->assertSame('STOREKEEPER',$storekeeper->role);
        $warehouse=Warehouse::where('code','MAIN-WH')->firstOrFail();
        $this->assertDatabaseHas('warehouse_user_assignments',['warehouse_id'=>$warehouse->id,'user_id'=>$storekeeper->id]);
        $this->actingAs($storekeeper)->get('/inventory/warehouse-control')->assertOk()->assertSee('Spare Parts Control Center');
    }
}
