<?php

namespace Tests\Feature;

use App\Models\{ChartAccount,FiscalPeriod,Item,PurchaseOrder,PurchaseRequisition,Supplier,Tenant,User,Warehouse};
use App\Services\Inventory\{InventoryOperationsService,StockService};
use App\Services\Procurement\{GoodsReceiptService,ProcurementFlowService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Wave9ProcurementInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email): User
    {
        $tenant=Tenant::firstOrCreate(['code'=>'T1'],['name'=>'Tenant 1','status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'name'=>$email,'email'=>$email,'password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
    }

    private function financePrerequisites(User $user): void
    {
        FiscalPeriod::create(['organization_id'=>$user->organization_id,'code'=>'CURRENT','starts_on'=>now()->startOfMonth()->toDateString(),'ends_on'=>now()->endOfMonth()->toDateString(),'status'=>'OPEN']);
        foreach ([['INVENTORY','Inventory','ASSET','DEBIT'],['GRIR','GRIR','LIABILITY','CREDIT'],['2000','AP','LIABILITY','CREDIT'],['5000','Expense','EXPENSE','DEBIT']] as [$code,$name,$type,$normal])
            ChartAccount::create(['organization_id'=>$user->organization_id,'code'=>$code,'name'=>$name,'type'=>$type,'normal_balance'=>$normal,'posting_allowed'=>true,'status'=>'ACTIVE']);
    }

    public function test_requisition_to_po_receipt_and_ap_handoff(): void
    {
        $requester=$this->admin('requester@test.local'); $approver=$this->admin('approver@test.local'); $poApprover=$this->admin('po@test.local');
        $this->actingAs($requester); $this->financePrerequisites($requester);
        $item=Item::create(['item_code'=>'RM-9','name'=>'Raw Material','uom'=>'EA','status'=>'ACTIVE']);
        $supplier=Supplier::create(['supplier_code'=>'SUP-1','name'=>'Supplier One','status'=>'ACTIVE']);
        $req=PurchaseRequisition::create(['requisition_no'=>'PR-1','requested_date'=>now()->toDateString(),'status'=>'DRAFT','created_by'=>$requester->id]);
        $req->lines()->create(['line_no'=>1,'item_id'=>$item->id,'quantity'=>5,'estimated_unit_price'=>10]);

        $this->actingAs($approver); app(ProcurementFlowService::class)->approveRequisition($req);
        $po=app(ProcurementFlowService::class)->convertToPurchaseOrder($req->fresh(),$supplier,'PO-9');
        $this->actingAs($poApprover); $this->post('/procurement/purchase-orders/'.$po->id.'/approve')->assertRedirect();
        $receipt=app(GoodsReceiptService::class)->receive($po->fresh(),'GR-9','MAIN',[$po->lines()->first()->id=>5]);
        $invoice=app(ProcurementFlowService::class)->matchSupplierInvoice($po->fresh(),$supplier,['invoice_no'=>'INV-9','invoice_date'=>now()->toDateString(),'amount'=>50,'currency'=>'USD','control_account_code'=>'2000','offset_account_code'=>'5000']);

        $this->assertDatabaseHas('goods_receipts',['id'=>$receipt->id]);
        $this->assertDatabaseHas('supplier_invoices',['id'=>$invoice->id,'status'=>'MATCHED']);
        $this->assertDatabaseHas('financial_documents',['id'=>$invoice->financial_document_id,'document_type'=>'AP_INVOICE','status'=>'DRAFT']);
    }

    public function test_transfer_and_count_update_stock_with_audit(): void
    {
        $user=$this->admin('inventory@test.local'); $this->actingAs($user);
        $item=Item::create(['item_code'=>'IT-9','name'=>'Inventory Item','uom'=>'EA','status'=>'ACTIVE']);
        Warehouse::create(['code'=>'MAIN','name'=>'Main','status'=>'ACTIVE']); Warehouse::create(['code'=>'SECOND','name'=>'Second','status'=>'ACTIVE']);
        app(StockService::class)->move($item,'MAIN','RECEIPT',10,'seed-9');
        app(InventoryOperationsService::class)->transfer($item,'TR-9','MAIN','SECOND',4);
        app(InventoryOperationsService::class)->postCount($item,'CNT-9','SECOND',3);

        $this->assertEquals(6.0,(float)DB::table('stock_balances')->where(['item_id'=>$item->id,'warehouse_code'=>'MAIN'])->value('quantity'));
        $this->assertEquals(3.0,(float)DB::table('stock_balances')->where(['item_id'=>$item->id,'warehouse_code'=>'SECOND'])->value('quantity'));
        $this->assertDatabaseHas('inventory_counts',['count_no'=>'CNT-9','variance'=>-1]);
    }
}
