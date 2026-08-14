<?php

namespace App\Services\Inventory;

use App\Models\Item;
use App\Services\AuditService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class InventoryOperationsService
{
    public function __construct(private StockService $stock, private AuditService $audit) {}

    public function transfer(Item $item, string $transferNo, string $from, string $to, float $quantity): void
    {
        if ($from === $to) throw ValidationException::withMessages(['warehouse'=>'Source and destination warehouses must differ.']);
        DB::transaction(function () use ($item,$transferNo,$from,$to,$quantity) {
            $this->stock->move($item,$from,'ISSUE',$quantity,"transfer:{$transferNo}:out",'InventoryTransfer',null);
            $this->stock->move($item,$to,'RECEIPT',$quantity,"transfer:{$transferNo}:in",'InventoryTransfer',null);
            DB::table('inventory_transfers')->insert(['tenant_id'=>Auth::user()->tenant_id,'item_id'=>$item->id,'transfer_no'=>$transferNo,'from_warehouse_code'=>$from,'to_warehouse_code'=>$to,'quantity'=>$quantity,'status'=>'POSTED','created_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);
            $this->audit->record('inventory.transfer.posted',$item,[],compact('transferNo','from','to','quantity'));
        });
    }

    public function postCount(Item $item, string $countNo, string $warehouse, float $counted): void
    {
        DB::transaction(function () use ($item,$countNo,$warehouse,$counted) {
            $tenant=Auth::user()->tenant_id;
            $system=(float)(DB::table('stock_balances')->where(['tenant_id'=>$tenant,'item_id'=>$item->id,'warehouse_code'=>$warehouse])->value('quantity') ?? 0);
            $variance=$counted-$system;
            if (abs($variance)>0.0001) $this->stock->move($item,$warehouse,$variance>0?'RECEIPT':'ISSUE',abs($variance),"count:{$countNo}",'InventoryCount',null);
            DB::table('inventory_counts')->insert(['tenant_id'=>$tenant,'item_id'=>$item->id,'count_no'=>$countNo,'warehouse_code'=>$warehouse,'system_quantity'=>$system,'counted_quantity'=>$counted,'variance'=>$variance,'status'=>'POSTED','created_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);
            $this->audit->record('inventory.count.posted',$item,['quantity'=>$system],['quantity'=>$counted,'variance'=>$variance]);
        });
    }
}
