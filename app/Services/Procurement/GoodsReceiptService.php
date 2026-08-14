<?php

namespace App\Services\Procurement;

use App\Models\{GoodsReceipt,Journal,PurchaseOrder};
use App\Services\{AuditService};
use App\Services\Finance\JournalPostingService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function __construct(
        private StockService $stock,
        private JournalPostingService $posting,
        private AuditService $audit,
    ) {}

    public function receive(PurchaseOrder $po, string $receiptNo, string $warehouse, array $quantities): GoodsReceipt
    {
        if ($po->status !== 'APPROVED') throw ValidationException::withMessages(['purchase_order'=>'Only APPROVED purchase orders can be received.']);
        $po->load('lines.item');

        return DB::transaction(function () use ($po,$receiptNo,$warehouse,$quantities) {
            $receipt=GoodsReceipt::create([
                'organization_id'=>$po->organization_id,'purchase_order_id'=>$po->id,'created_by'=>Auth::id(),
                'receipt_no'=>$receiptNo,'warehouse_code'=>$warehouse,'receipt_date'=>now()->toDateString(),'status'=>'POSTED',
            ]);
            $total=0.0;
            foreach ($po->lines as $line) {
                $qty=(float)($quantities[$line->id] ?? 0);
                if ($qty <= 0) continue;
                $already=(float)DB::table('goods_receipt_lines')->where('purchase_order_line_id',$line->id)->sum('quantity');
                if ($already + $qty > (float)$line->quantity + 0.0001) {
                    throw ValidationException::withMessages(['lines'=>"Receipt quantity exceeds remaining quantity on PO line {$line->line_no}."]);
                }
                $receipt->lines()->create(['purchase_order_line_id'=>$line->id,'item_id'=>$line->item_id,'quantity'=>$qty,'unit_cost'=>$line->unit_price]);
                $this->stock->move($line->item,$warehouse,'RECEIPT',$qty,"gr:{$receipt->id}:{$line->id}",GoodsReceipt::class,$receipt->id);
                $total += $qty * (float)$line->unit_price;
            }
            if ($total <= 0) throw ValidationException::withMessages(['lines'=>'At least one positive receipt quantity is required.']);

            $journal=Journal::create([
                'organization_id'=>$po->organization_id,'created_by'=>null,'journal_no'=>'GR-'.$receiptNo,
                'journal_date'=>now()->toDateString(),'description'=>'Goods receipt '.$receiptNo.' for PO '.$po->po_number,'status'=>'DRAFT',
            ]);
            $journal->lines()->createMany([
                ['line_no'=>1,'account_code'=>'INVENTORY','debit'=>$total,'credit'=>0,'description'=>'Inventory receipt'],
                ['line_no'=>2,'account_code'=>'GRIR','debit'=>0,'credit'=>$total,'description'=>'Goods received clearing'],
            ]);
            $this->posting->post($journal);
            $receipt->update(['journal_id'=>$journal->id]);

            $correlation=(string)Str::uuid();
            DB::table('outbox_events')->insert([
                'id'=>(string)Str::uuid(),'tenant_id'=>$po->tenant_id,'event_type'=>'GoodsReceived','aggregate_type'=>GoodsReceipt::class,
                'aggregate_id'=>$receipt->id,'correlation_id'=>$correlation,'payload'=>json_encode($receipt->fresh('lines')->toArray()),
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            $this->audit->record('procurement.goods_receipt.posted',$receipt,[], $receipt->fresh('lines')->toArray(),$correlation);
            return $receipt->fresh(['lines','journal.lines']);
        });
    }
}
