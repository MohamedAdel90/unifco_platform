<?php

namespace Tests\Feature;

use App\Models\{Asset,Item,Organization,Tenant,User,Warehouse,WorkOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkOrderPartRequestTest extends TestCase
{
    use RefreshDatabase;

    private function setupContext(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO Test','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>'part-admin@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'asset_code'=>'AST-001','name'=>'Test Generator','acquisition_cost'=>10000,'status'=>'REGISTERED']);
        $workOrder=WorkOrder::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'work_order_no'=>'WO-001','asset_id'=>$asset->id,'maintenance_type'=>'CORRECTIVE','priority'=>'HIGH','status'=>'OPEN']);
        $main=Warehouse::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>'MAIN-WH','name'=>'Main Warehouse','location_type'=>'CENTRAL','status'=>'ACTIVE']);
        $van=Warehouse::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>'VAN-001','name'=>'Technician Van','location_type'=>'VEHICLE','vehicle_code'=>'VAN-001','is_mobile'=>true,'status'=>'ACTIVE']);
        $item=Item::create(['tenant_id'=>$tenant->id,'item_code'=>'PART-001','name'=>'Contactor','uom'=>'EA','status'=>'ACTIVE']);
        DB::table('stock_balances')->insert(['tenant_id'=>$tenant->id,'item_id'=>$item->id,'warehouse_code'=>'MAIN-WH','quantity'=>10,'reserved_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
        return compact('tenant','org','admin','asset','workOrder','main','van','item');
    }

    public function test_request_reserve_pick_issue_and_receive_moves_stock_to_vehicle(): void
    {
        $c=$this->setupContext();
        $this->actingAs($c['admin'])->post('/maintenance/work-orders/'.$c['workOrder']->id.'/part-requests',[
            'source_warehouse_id'=>$c['main']->id,'destination_warehouse_id'=>$c['van']->id,'priority'=>'HIGH','reason'=>'Required for corrective repair',
            'item_id'=>[$c['item']->id],'quantity'=>[3],
        ])->assertSessionHasNoErrors();

        $request=DB::table('work_order_part_requests')->first();
        $this->assertSame('REQUESTED',$request->status);
        $this->assertEquals(10.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'MAIN-WH'])->value('quantity'));
        $this->assertEquals(0.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'MAIN-WH'])->value('reserved_quantity'));

        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/approve')->assertSessionHasNoErrors();
        $this->assertSame('APPROVED',DB::table('work_order_part_requests')->where('id',$request->id)->value('status'));
        $this->assertEquals(10.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'MAIN-WH'])->value('quantity'));
        $this->assertEquals(3.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'MAIN-WH'])->value('reserved_quantity'));

        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/pick')->assertSessionHasNoErrors();
        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/issue')->assertSessionHasNoErrors();
        $this->assertSame('ISSUED',DB::table('work_order_part_requests')->where('id',$request->id)->value('status'));
        $this->assertEquals(7.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'MAIN-WH'])->value('quantity'));
        $this->assertEquals(0.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'MAIN-WH'])->value('reserved_quantity'));

        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/receive')->assertSessionHasNoErrors();
        $this->assertSame('RECEIVED',DB::table('work_order_part_requests')->where('id',$request->id)->value('status'));
        $this->assertEquals(3.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'VAN-001'])->value('quantity'));
    }

    public function test_approval_fails_when_available_stock_is_insufficient(): void
    {
        $c=$this->setupContext();
        $this->actingAs($c['admin'])->post('/maintenance/work-orders/'.$c['workOrder']->id.'/part-requests',[
            'source_warehouse_id'=>$c['main']->id,'destination_warehouse_id'=>$c['van']->id,'priority'=>'NORMAL',
            'item_id'=>[$c['item']->id],'quantity'=>[11],
        ])->assertSessionHasNoErrors();
        $request=DB::table('work_order_part_requests')->first();
        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/approve')->assertSessionHasErrors('stock');
        $this->assertSame('REQUESTED',DB::table('work_order_part_requests')->where('id',$request->id)->value('status'));
        $this->assertEquals(0.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'MAIN-WH'])->value('reserved_quantity'));
    }

    public function test_critical_request_requires_supervisor_or_manager_approval(): void
    {
        $c=$this->setupContext();
        $storekeeper=User::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'name'=>'Storekeeper','email'=>'storekeeper-test@example.test','password'=>'password','role'=>'STOREKEEPER','status'=>'ACTIVE']);
        $supervisor=User::create(['tenant_id'=>$c['tenant']->id,'organization_id'=>$c['org']->id,'name'=>'Supervisor','email'=>'supervisor-test@example.test','password'=>'password','role'=>'SUPERVISOR','status'=>'ACTIVE']);

        $this->actingAs($c['admin'])->post('/maintenance/work-orders/'.$c['workOrder']->id.'/part-requests',[
            'source_warehouse_id'=>$c['main']->id,'destination_warehouse_id'=>$c['van']->id,'priority'=>'CRITICAL','reason'=>'Asset down',
            'item_id'=>[$c['item']->id],'quantity'=>[2],
        ])->assertSessionHasNoErrors();
        $request=DB::table('work_order_part_requests')->first();

        $this->actingAs($storekeeper)->post('/inventory/part-requests/'.$request->id.'/approve')->assertSessionHasErrors('approval');
        $this->actingAs($supervisor)->post('/inventory/part-requests/'.$request->id.'/approve')->assertSessionHasNoErrors();
        $this->assertSame('APPROVED',DB::table('work_order_part_requests')->where('id',$request->id)->value('status'));
        $this->assertEquals(2.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'MAIN-WH'])->value('reserved_quantity'));
    }

    public function test_work_order_page_renders_part_request_workspace(): void
    {
        $c=$this->setupContext();
        $this->actingAs($c['admin'])->get('/maintenance/work-orders/'.$c['workOrder']->id)
            ->assertOk()->assertSee('Technician Part Request')->assertSee('Submit Part Request');
    }
}
