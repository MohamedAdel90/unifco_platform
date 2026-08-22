<?php

namespace Tests\Feature;

use App\Models\{Asset,Customer,Item,Organization,Tenant,User,Warehouse,WorkOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkOrderPartConsumptionTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $tenant=Tenant::create(['name'=>'UNIFCO Test','code'=>'UNIFCO','status'=>'ACTIVE']);
        $org=Organization::create(['tenant_id'=>$tenant->id,'name'=>'HQ','code'=>'HQ','status'=>'ACTIVE']);
        $customer=Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>'C-001','name'=>'Test Customer','status'=>'ACTIVE']);
        $admin=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'name'=>'Admin','email'=>'consume-admin@example.test','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
        $customerUser=User::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'name'=>'Customer User','email'=>'consume-customer@example.test','password'=>'password','role'=>'CUSTOMER','status'=>'ACTIVE']);
        $asset=Asset::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer->id,'asset_code'=>'AST-CONSUME','name'=>'Test Generator','acquisition_cost'=>10000,'status'=>'REGISTERED']);
        $workOrder=WorkOrder::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'work_order_no'=>'WO-CONSUME','asset_id'=>$asset->id,'maintenance_type'=>'CORRECTIVE','priority'=>'HIGH','status'=>'OPEN']);
        $main=Warehouse::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>'MAIN-WH','name'=>'Main Warehouse','location_type'=>'CENTRAL','status'=>'ACTIVE']);
        $van=Warehouse::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>'VAN-001','name'=>'Technician Van','location_type'=>'VEHICLE','vehicle_code'=>'VAN-001','is_mobile'=>true,'status'=>'ACTIVE']);
        $item=Item::create(['tenant_id'=>$tenant->id,'item_code'=>'PART-CONSUME','name'=>'Power Contactor','uom'=>'EA','status'=>'ACTIVE']);
        DB::table('stock_balances')->insert(['tenant_id'=>$tenant->id,'item_id'=>$item->id,'warehouse_code'=>'MAIN-WH','quantity'=>10,'reserved_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
        return compact('tenant','org','customer','admin','customerUser','asset','workOrder','main','van','item');
    }

    private function receiveRequest(array $c, float $quantity=3): array
    {
        $this->actingAs($c['admin'])->post('/maintenance/work-orders/'.$c['workOrder']->id.'/part-requests',[
            'source_warehouse_id'=>$c['main']->id,'destination_warehouse_id'=>$c['van']->id,'priority'=>'HIGH','reason'=>'Corrective replacement',
            'item_id'=>[$c['item']->id],'quantity'=>[$quantity],
        ])->assertSessionHasNoErrors();
        $request=DB::table('work_order_part_requests')->first();
        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/approve')->assertSessionHasNoErrors();
        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/pick')->assertSessionHasNoErrors();
        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/issue')->assertSessionHasNoErrors();
        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/receive')->assertSessionHasNoErrors();
        $line=DB::table('work_order_part_request_lines')->where('work_order_part_request_id',$request->id)->first();
        return [$request,$line];
    }

    public function test_received_parts_can_be_consumed_on_asset_and_unused_balance_returned(): void
    {
        $c=$this->context();[$request,$line]=$this->receiveRequest($c,3);
        $this->assertEquals(3.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'VAN-001'])->value('quantity'));

        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/lines/'.$line->id.'/consume',[
            'quantity'=>2,'removed_serial'=>'OLD-001','removed_disposition'=>'REPAIRABLE','notes'=>'Installed and tested successfully',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(1.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'VAN-001'])->value('quantity'));
        $this->assertDatabaseHas('asset_part_installations',['asset_id'=>$c['asset']->id,'work_order_id'=>$c['workOrder']->id,'item_id'=>$c['item']->id,'quantity'=>2]);
        $this->assertDatabaseHas('maintenance_materials',['work_order_id'=>$c['workOrder']->id,'item_id'=>$c['item']->id,'quantity'=>2]);
        $this->assertSame('RECEIVED',DB::table('work_order_part_requests')->where('id',$request->id)->value('status'));

        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/lines/'.$line->id.'/return',['quantity'=>1,'reason'=>'Unused excess'])->assertSessionHasNoErrors();
        $this->assertEquals(0.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'VAN-001'])->value('quantity'));
        $this->assertEquals(8.0,(float)DB::table('stock_balances')->where(['item_id'=>$c['item']->id,'warehouse_code'=>'MAIN-WH'])->value('quantity'));
        $this->assertSame('CLOSED',DB::table('work_order_part_requests')->where('id',$request->id)->value('status'));
        $this->assertDatabaseHas('work_order_part_returns',['item_id'=>$c['item']->id,'quantity'=>1]);
    }

    public function test_work_order_cannot_close_until_received_parts_are_consumed_or_returned(): void
    {
        $c=$this->context();[$request,$line]=$this->receiveRequest($c,2);
        $this->actingAs($c['admin'])->post('/maintenance/work-orders/'.$c['workOrder']->id.'/complete',['completion_notes'=>'Repair complete'])->assertSessionHasErrors('parts');
        $this->assertSame('OPEN',$c['workOrder']->fresh()->status);

        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/lines/'.$line->id.'/consume',['quantity'=>2,'notes'=>'Installed'])->assertSessionHasNoErrors();
        $this->assertSame('CLOSED',DB::table('work_order_part_requests')->where('id',$request->id)->value('status'));
        $this->actingAs($c['admin'])->post('/maintenance/work-orders/'.$c['workOrder']->id.'/complete',['completion_notes'=>'Repair complete'])->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED',$c['workOrder']->fresh()->status);
    }

    public function test_internal_and_customer_asset_360_show_installed_part_history_and_service_report_shows_usage(): void
    {
        $c=$this->context();[$request,$line]=$this->receiveRequest($c,1);
        $this->actingAs($c['admin'])->post('/inventory/part-requests/'.$request->id.'/lines/'.$line->id.'/consume',['quantity'=>1,'notes'=>'Installed'])->assertSessionHasNoErrors();

        $this->actingAs($c['admin'])->get('/eam/assets/'.$c['asset']->id)->assertOk()->assertSee('Installed Parts & Components', false)->assertSee('PART-CONSUME')->assertSee('Internal Cost');
        $this->actingAs($c['customerUser'])->get('/customer/assets/'.$c['asset']->id)->assertOk()->assertSee('Installed Parts & Components', false)->assertSee('PART-CONSUME')->assertDontSee('Internal Cost');
        $this->actingAs($c['customerUser'])->get('/customer/work-orders/'.$c['workOrder']->id)->assertOk()->assertSee('Spare Parts Used')->assertSee('PART-CONSUME')->assertDontSee('unit_cost');
    }
}
