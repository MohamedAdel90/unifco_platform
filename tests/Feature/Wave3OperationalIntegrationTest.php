<?php

namespace Tests\Feature;

use App\Models\{ChartAccount,FiscalPeriod,Item,PurchaseOrder,Tenant,User};
use App\Services\Procurement\GoodsReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Wave3OperationalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email): User
    {
        $tenant=Tenant::firstOrCreate(['code'=>'T1'],['name'=>'Tenant 1','status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'name'=>$email,'email'=>$email,'password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
    }

    public function test_goods_receipt_updates_inventory_and_posts_finance(): void
    {
        $user=$this->admin('receiver@test.local'); $this->actingAs($user);
        FiscalPeriod::create([
            'organization_id'=>$user->organization_id,
            'code'=>now()->format('Y-m'),
            'starts_on'=>now()->startOfMonth()->toDateString(),
            'ends_on'=>now()->endOfMonth()->toDateString(),
            'status'=>'OPEN',
        ]);
        foreach ([['INVENTORY','Inventory','ASSET','DEBIT'],['GRIR','Goods Received Clearing','LIABILITY','CREDIT']] as [$code,$name,$type,$normal]) {
            ChartAccount::create([
                'organization_id'=>$user->organization_id,
                'code'=>$code,
                'name'=>$name,
                'type'=>$type,
                'normal_balance'=>$normal,
                'posting_allowed'=>true,
                'status'=>'ACTIVE',
            ]);
        }
        $item=Item::create(['item_code'=>'RM-1','name'=>'Raw Material','uom'=>'EA','status'=>'ACTIVE']);
        $po=PurchaseOrder::create(['created_by'=>$user->id,'po_number'=>'PO-100','supplier_name'=>'Supplier','order_date'=>now()->toDateString(),'total'=>50,'status'=>'APPROVED']);
        $line=$po->lines()->create(['line_no'=>1,'item_id'=>$item->id,'quantity'=>5,'unit_price'=>10]);

        $receipt=app(GoodsReceiptService::class)->receive($po,'GR-100','MAIN',[$line->id=>5]);

        $this->assertDatabaseHas('stock_balances',['item_id'=>$item->id,'warehouse_code'=>'MAIN','quantity'=>5]);
        $this->assertDatabaseHas('journals',['id'=>$receipt->journal_id,'status'=>'POSTED']);
        $this->assertDatabaseHas('outbox_events',['event_type'=>'GoodsReceived','aggregate_id'=>$receipt->id]);
    }

    public function test_outbox_command_marks_events_published(): void
    {
        $user=$this->admin('publisher@test.local'); $this->actingAs($user);
        DB::table('outbox_events')->insert(['id'=>(string)\Illuminate\Support\Str::uuid(),'tenant_id'=>$user->tenant_id,'event_type'=>'TestEvent','aggregate_type'=>'Test','aggregate_id'=>1,'payload'=>'{}','created_at'=>now(),'updated_at'=>now()]);
        $this->artisan('unifco:outbox-publish')->assertSuccessful();
        $this->assertNotNull(DB::table('outbox_events')->value('published_at'));
    }
}
