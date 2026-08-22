<?php

namespace App\Services\Inventory;

use App\Models\{InventoryTransferOrder,InventoryTransferOrderLine,Item,Warehouse};
use App\Services\AuditService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryTransferService
{
    public function __construct(private StockService $stock, private AuditService $audit) {}

    public function create(Warehouse $from, Warehouse $to, array $lines, ?string $purpose=null, ?string $notes=null): InventoryTransferOrder
    {
        if($from->id===$to->id) throw ValidationException::withMessages(['to_warehouse_id'=>'Source and destination must be different.']);
        if($from->status!=='ACTIVE' || $to->status!=='ACTIVE') throw ValidationException::withMessages(['warehouse'=>'Both stock locations must be active.']);

        return DB::transaction(function() use($from,$to,$lines,$purpose,$notes){
            $transfer=InventoryTransferOrder::create([
                'tenant_id'=>Auth::user()->tenant_id,'organization_id'=>Auth::user()->organization_id,
                'transfer_no'=>$this->nextNumber(),'from_warehouse_id'=>$from->id,'to_warehouse_id'=>$to->id,
                'status'=>'REQUESTED','purpose'=>$purpose,'requested_by'=>Auth::id(),'notes'=>$notes,
            ]);
            foreach($lines as $line){
                if((float)$line['quantity']<=0) continue;
                InventoryTransferOrderLine::create([
                    'inventory_transfer_order_id'=>$transfer->id,'item_id'=>$line['item_id'],
                    'requested_quantity'=>$line['quantity'],'issued_quantity'=>0,'received_quantity'=>0,
                ]);
            }
            if(!$transfer->lines()->exists()) throw ValidationException::withMessages(['items'=>'At least one positive quantity is required.']);
            $this->audit->record('inventory.transfer.request',$transfer,null,['status'=>'REQUESTED','from'=>$from->code,'to'=>$to->code]);
            return $transfer->load(['lines.item','fromWarehouse','toWarehouse']);
        });
    }

    public function issue(InventoryTransferOrder $transfer): InventoryTransferOrder
    {
        if($transfer->status!=='REQUESTED') throw ValidationException::withMessages(['transfer'=>'Only requested transfers can be issued.']);

        return DB::transaction(function() use($transfer){
            $transfer->load(['lines.item','fromWarehouse','toWarehouse']);
            foreach($transfer->lines as $line){
                $qty=(float)$line->requested_quantity;
                $this->stock->move($line->item,$transfer->fromWarehouse->code,'ISSUE',$qty,'transfer-issue-'.$transfer->id.'-'.$line->id,'inventory_transfer_order',$transfer->id);
                $line->update(['issued_quantity'=>$qty]);
            }
            $transfer->update(['status'=>'IN_TRANSIT','issued_by'=>Auth::id(),'issued_at'=>now()]);
            $this->audit->record('inventory.transfer.issue',$transfer,['status'=>'REQUESTED'],['status'=>'IN_TRANSIT']);
            return $transfer->fresh(['lines.item','fromWarehouse','toWarehouse']);
        });
    }

    public function receive(InventoryTransferOrder $transfer): InventoryTransferOrder
    {
        if($transfer->status!=='IN_TRANSIT') throw ValidationException::withMessages(['transfer'=>'Only in-transit transfers can be received.']);

        return DB::transaction(function() use($transfer){
            $transfer->load(['lines.item','fromWarehouse','toWarehouse']);
            foreach($transfer->lines as $line){
                $qty=(float)$line->issued_quantity;
                $this->stock->move($line->item,$transfer->toWarehouse->code,'RECEIPT',$qty,'transfer-receipt-'.$transfer->id.'-'.$line->id,'inventory_transfer_order',$transfer->id);
                $line->update(['received_quantity'=>$qty]);
            }
            $transfer->update(['status'=>'RECEIVED','received_by'=>Auth::id(),'received_at'=>now()]);
            $this->audit->record('inventory.transfer.receive',$transfer,['status'=>'IN_TRANSIT'],['status'=>'RECEIVED']);
            return $transfer->fresh(['lines.item','fromWarehouse','toWarehouse']);
        });
    }

    private function nextNumber(): string
    {
        $prefix='TRF-'.now()->format('Ym').'-';
        $last=InventoryTransferOrder::where('transfer_no','like',$prefix.'%')->orderByDesc('id')->value('transfer_no');
        $seq=$last ? ((int)substr($last,-5))+1 : 1;
        return $prefix.str_pad((string)$seq,5,'0',STR_PAD_LEFT);
    }
}
