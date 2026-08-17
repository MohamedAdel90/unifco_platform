<?php

namespace Database\Seeders;

use App\Models\{GoodsReceipt,GoodsReceiptLine,Item,Organization,PurchaseOrder,PurchaseOrderLine,PurchaseRequisition,PurchaseRequisitionLine,Supplier,SupplierInvoice,Tenant,User};
use Illuminate\Database\Seeder;

class ProcurementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $org = Organization::where('tenant_id', $tenant->id)->where('code', 'HQ')->firstOrFail();
        $admin = User::where('tenant_id', $tenant->id)->where('email', 'admin@unifco.local')->firstOrFail();

        if (Supplier::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        foreach ([
            ['SUP-001','Acme Supplies Ltd','procurement@acme.test','TIN-1001'],
            ['SUP-002','Global Materials Co','sales@globalmaterials.test','TIN-1002'],
            ['SUP-003','Industrial Parts Inc','orders@industrialparts.test','TIN-1003'],
        ] as [$code,$name,$email,$tax]) {
            Supplier::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'supplier_code'=>$code,'name'=>$name,'email'=>$email,'tax_no'=>$tax,'status'=>'ACTIVE']);
        }

        $item = static fn (string $code) => Item::where('tenant_id',$tenant->id)->where('item_code',$code)->firstOrFail();

        $requisitions = [
            [
                'requisition_no' => 'PR-2026-0001', 'requested_date' => '2026-08-02',
                'purpose' => 'Raw materials for August production', 'status' => 'APPROVED',
                'approved_at' => '2026-08-03 10:00:00',
                'lines' => [
                    ['item' => 'RAW-001', 'qty' => 500, 'price' => 8.00],
                    ['item' => 'RAW-002', 'qty' => 300, 'price' => 12.00],
                ],
            ],
            [
                'requisition_no' => 'PR-2026-0002', 'requested_date' => '2026-08-10',
                'purpose' => 'Fastener kits restock', 'status' => 'DRAFT', 'approved_at' => null,
                'lines' => [['item' => 'RAW-004', 'qty' => 200, 'price' => 5.00]],
            ],
        ];

        $purchaseOrders = [
            [
                'po_number' => 'PO-2026-0001', 'supplier' => 'SUP-001', 'requisition' => 'PR-2026-0001',
                'order_date' => '2026-08-04', 'status' => 'APPROVED',
                'lines' => [
                    ['item' => 'RAW-001', 'qty' => 500, 'price' => 8.00],
                    ['item' => 'RAW-002', 'qty' => 300, 'price' => 12.00],
                ],
            ],
            [
                'po_number' => 'PO-2026-0002', 'supplier' => 'SUP-002', 'requisition' => null,
                'order_date' => '2026-08-11', 'status' => 'DRAFT',
                'lines' => [['item' => 'RAW-003', 'qty' => 1000, 'price' => 0.50]],
            ],
            [
                'po_number' => 'PO-2026-0003', 'supplier' => 'SUP-001', 'requisition' => 'PR-2026-0002',
                'order_date' => '2026-08-12', 'status' => 'POSTED',
                'lines' => [['item' => 'RAW-004', 'qty' => 200, 'price' => 5.00]],
            ],
        ];

        $postedPO = null;
        foreach ($purchaseOrders as $po) {
            $supplier = Supplier::where('tenant_id',$tenant->id)->where('supplier_code',$po['supplier'])->firstOrFail();
            $requisition = $po['requisition'] ? PurchaseRequisition::where('tenant_id',$tenant->id)->where('requisition_no',$po['requisition'])->first() : null;
            $total = array_sum(array_map(static fn ($l) => $l['qty'] * $l['price'], $po['lines']));
            $model = PurchaseOrder::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
                'created_by'=>$admin->id,'approved_by'=>$po['status']!=='DRAFT' ? $admin->id : null,
                'supplier_id'=>$supplier->id,'purchase_requisition_id'=>$requisition?->id,
                'po_number'=>$po['po_number'],'supplier_name'=>$supplier->name,
                'order_date'=>$po['order_date'],'total'=>$total,'status'=>$po['status'],
            ]);
            foreach ($po['lines'] as $i => $line) {
                PurchaseOrderLine::create([
                    'purchase_order_id'=>$model->id,'line_no'=>$i + 1,
                    'item_id'=>$item($line['item'])->id,'quantity'=>$line['qty'],'unit_price'=>$line['price'],
                ]);
            }
            if ($po['status'] === 'POSTED') {
                $postedPO = $model;
            }
        }

        if ($postedPO) {
            $supplier = Supplier::where('tenant_id',$tenant->id)->where('supplier_code','SUP-001')->firstOrFail();
            SupplierInvoice::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
                'supplier_id'=>$supplier->id,'purchase_order_id'=>$postedPO->id,
                'invoice_no'=>'INV-2026-0001','invoice_date'=>'2026-08-14','amount'=>$postedPO->total,
                'status'=>'POSTED','created_by'=>$admin->id,
            ]);

            $receipt = GoodsReceipt::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
                'purchase_order_id'=>$postedPO->id,'created_by'=>$admin->id,
                'receipt_no'=>'GR-2026-0001','warehouse_code'=>'MAIN',
                'receipt_date'=>'2026-08-15','status'=>'POSTED',
            ]);
            foreach ($postedPO->lines as $i => $line) {
                GoodsReceiptLine::create([
                    'goods_receipt_id'=>$receipt->id,'purchase_order_line_id'=>$line->id,
                    'item_id'=>$line->item_id,'quantity'=>$line->quantity,'unit_cost'=>$line->unit_price,
                ]);
            }
        }
    }
}