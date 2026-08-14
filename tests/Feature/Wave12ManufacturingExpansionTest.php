<?php

namespace Tests\Feature;

use App\Models\{Bom,Item,Organization,ProductionOrder,Routing,Tenant,User,Warehouse,WorkCenter};
use App\Services\Inventory\StockService;
use App\Services\Manufacturing\ManufacturingOperationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Wave12ManufacturingExpansionTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $tenant=Tenant::create(['code'=>'MFG','name'=>'Manufacturing Tenant','status'=>'ACTIVE']);
        $organization=Organization::create(['tenant_id'=>$tenant->id,'code'=>'MFG-ORG','name'=>'Manufacturing Organization','status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'organization_id'=>$organization->id,'name'=>'Manufacturing Admin','email'=>'mfg@test.local','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
    }

    public function test_bom_routing_material_issue_quality_completion_and_cost_variance(): void
    {
        $user=$this->user(); $this->actingAs($user);
        $product=Item::create(['item_code'=>'FG-12','name'=>'Finished Good','uom'=>'EA','status'=>'ACTIVE']);
        $component=Item::create(['item_code'=>'RM-12','name'=>'Raw Material','uom'=>'EA','status'=>'ACTIVE']);
        Warehouse::create(['code'=>'MAIN','name'=>'Main Warehouse','status'=>'ACTIVE']);
        app(StockService::class)->move($component,'MAIN','RECEIPT',100,'wave12-seed');

        $wc=WorkCenter::create(['organization_id'=>$user->organization_id,'code'=>'WC-12','name'=>'Assembly','hourly_rate'=>20,'status'=>'ACTIVE']);
        $bom=Bom::create(['organization_id'=>$user->organization_id,'product_item_id'=>$product->id,'bom_no'=>'BOM-12','version'=>1,'status'=>'ACTIVE']);
        $bom->lines()->create(['line_no'=>1,'component_item_id'=>$component->id,'quantity_per'=>2,'standard_unit_cost'=>10]);
        $routing=Routing::create(['organization_id'=>$user->organization_id,'product_item_id'=>$product->id,'routing_no'=>'RT-12','status'=>'ACTIVE']);
        $routing->operations()->create(['sequence'=>10,'work_center_id'=>$wc->id,'operation_name'=>'Assembly','standard_hours'=>2]);
        $order=ProductionOrder::create(['organization_id'=>$user->organization_id,'order_no'=>'MO-12','product_name'=>'Finished Good','planned_quantity'=>5,'produced_quantity'=>0,'status'=>'CREATED']);

        $svc=app(ManufacturingOperationsService::class);
        $svc->release($order,$bom,$routing,'MAIN');
        $this->assertEquals(140.0,(float)$order->fresh()->standard_cost);
        $svc->issueMaterials($order->fresh());
        $svc->confirm($order->fresh(),['work_center_id'=>$wc->id,'hours'=>2.5,'good_quantity'=>5,'scrap_quantity'=>0]);
        $svc->inspect($order->fresh(),'PASS','Inspection passed');
        $completed=$svc->complete($order->fresh(),5);

        $this->assertSame('COMPLETED',$completed->status);
        $this->assertEquals(150.0,(float)$completed->actual_cost);
        $this->assertEquals(10.0,(float)$completed->cost_variance);
        $this->assertEquals(90.0,(float)DB::table('stock_balances')->where(['item_id'=>$component->id,'warehouse_code'=>'MAIN'])->value('quantity'));
        $this->assertEquals(5.0,(float)DB::table('stock_balances')->where(['item_id'=>$product->id,'warehouse_code'=>'MAIN'])->value('quantity'));
        $this->assertDatabaseHas('quality_inspections',['production_order_id'=>$order->id,'result'=>'PASS']);
    }

    public function test_completion_requires_passing_quality_inspection(): void
    {
        $user=$this->user(); $this->actingAs($user);
        $product=Item::create(['item_code'=>'FG-Q','name'=>'Quality Product','uom'=>'EA','status'=>'ACTIVE']);
        $component=Item::create(['item_code'=>'RM-Q','name'=>'Quality Material','uom'=>'EA','status'=>'ACTIVE']);
        Warehouse::create(['code'=>'MAIN','name'=>'Main','status'=>'ACTIVE']);
        app(StockService::class)->move($component,'MAIN','RECEIPT',10,'quality-seed');
        $wc=WorkCenter::create(['organization_id'=>$user->organization_id,'code'=>'WC-Q','name'=>'QC Work Center','hourly_rate'=>10,'status'=>'ACTIVE']);
        $bom=Bom::create(['organization_id'=>$user->organization_id,'product_item_id'=>$product->id,'bom_no'=>'BOM-Q','version'=>1,'status'=>'ACTIVE']);
        $bom->lines()->create(['line_no'=>1,'component_item_id'=>$component->id,'quantity_per'=>1,'standard_unit_cost'=>1]);
        $routing=Routing::create(['organization_id'=>$user->organization_id,'product_item_id'=>$product->id,'routing_no'=>'RT-Q','status'=>'ACTIVE']);
        $routing->operations()->create(['sequence'=>10,'work_center_id'=>$wc->id,'operation_name'=>'Run','standard_hours'=>1]);
        $order=ProductionOrder::create(['organization_id'=>$user->organization_id,'order_no'=>'MO-Q','product_name'=>'Quality Product','planned_quantity'=>1,'produced_quantity'=>0,'status'=>'CREATED']);
        $svc=app(ManufacturingOperationsService::class); $svc->release($order,$bom,$routing,'MAIN');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->complete($order->fresh(),1);
    }
}
