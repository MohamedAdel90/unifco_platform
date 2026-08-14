<?php

namespace Tests\Feature;

use App\Models\{Asset,ProductionOrder,Tenant,User,WorkOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Wave5OperationsAssetsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $tenant=Tenant::create(['code'=>'OPS','name'=>'Operations','status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'name'=>'Admin','email'=>'ops@test.local','password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
    }

    public function test_production_order_lifecycle_is_audited(): void
    {
        $user=$this->user(); $this->actingAs($user);
        $order=ProductionOrder::create(['order_no'=>'MO-1','product_name'=>'Product A','planned_quantity'=>10,'produced_quantity'=>0,'status'=>'CREATED']);
        $this->post(route('manufacturing.production-orders.release',$order))->assertRedirect();
        $this->post(route('manufacturing.production-orders.complete',$order),['produced_quantity'=>8])->assertRedirect();
        $this->assertDatabaseHas('production_orders',['id'=>$order->id,'status'=>'COMPLETED','produced_quantity'=>8]);
        $this->assertDatabaseHas('audit_logs',['action'=>'manufacturing.production_order.completed']);
    }

    public function test_asset_capitalization_and_maintenance_work_order_flow(): void
    {
        $user=$this->user(); $this->actingAs($user);
        $asset=Asset::create(['asset_code'=>'A-1','name'=>'Machine','acquisition_cost'=>2500,'commission_date'=>now()->toDateString(),'status'=>'REGISTERED']);
        $this->post(route('eam.assets.capitalize',$asset))->assertRedirect();
        $this->assertDatabaseHas('assets',['id'=>$asset->id,'status'=>'CAPITALIZED']);

        $work=WorkOrder::create(['work_order_no'=>'WO-1','asset_id'=>$asset->id,'maintenance_type'=>'CORRECTIVE','priority'=>'HIGH','status'=>'OPEN']);
        $this->post(route('maintenance.work-orders.complete',$work))->assertRedirect();
        $this->assertDatabaseHas('work_orders',['id'=>$work->id,'status'=>'COMPLETED']);
        $this->assertDatabaseHas('audit_logs',['action'=>'maintenance.work_order.completed']);
    }

    public function test_work_order_cannot_reference_another_tenants_asset(): void
    {
        $user=$this->user(); $this->actingAs($user);
        $other=Tenant::create(['code'=>'OTHER','name'=>'Other','status'=>'ACTIVE']);
        $asset=Asset::withoutGlobalScopes()->create(['tenant_id'=>$other->id,'asset_code'=>'X-1','name'=>'Other Asset','acquisition_cost'=>1,'status'=>'REGISTERED']);
        $this->post(route('maintenance.work-orders.store'),[
            'work_order_no'=>'WO-X','asset_id'=>$asset->id,'maintenance_type'=>'CORRECTIVE','priority'=>'NORMAL'
        ])->assertSessionHasErrors('asset_id');
        $this->assertDatabaseMissing('work_orders',['work_order_no'=>'WO-X']);
    }
}
