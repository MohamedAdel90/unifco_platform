<?php

namespace App\Services\Inventory;

use App\Models\Item;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function __construct(private AuditService $audit) {}

    public function move(Item $item, string $warehouse, string $type, float $quantity, string $idempotencyKey, ?string $referenceType = null, ?int $referenceId = null): array
    {
        if ($quantity <= 0) throw ValidationException::withMessages(['quantity' => 'Quantity must be positive.']);
        if (! in_array($type, ['RECEIPT','ISSUE'], true)) throw ValidationException::withMessages(['movement_type' => 'Unsupported movement type.']);

        return DB::transaction(function () use ($item,$warehouse,$type,$quantity,$idempotencyKey,$referenceType,$referenceId) {
            $tenantId = Auth::user()->tenant_id;
            $existing = DB::table('stock_movements')->where('tenant_id',$tenantId)->where('idempotency_key',$idempotencyKey)->first();
            if ($existing) return ['movement_id' => $existing->id, 'duplicate' => true];

            $balance = DB::table('stock_balances')->where(['tenant_id'=>$tenantId,'item_id'=>$item->id,'warehouse_code'=>$warehouse])->lockForUpdate()->first();
            $current = (float) ($balance->quantity ?? 0);
            $next = $type === 'RECEIPT' ? $current + $quantity : $current - $quantity;
            if ($next < 0) throw ValidationException::withMessages(['quantity' => 'Insufficient stock.']);

            DB::table('stock_balances')->updateOrInsert(
                ['tenant_id'=>$tenantId,'item_id'=>$item->id,'warehouse_code'=>$warehouse],
                ['quantity'=>$next,'reserved_quantity'=>$balance->reserved_quantity ?? 0,'created_at'=>$balance->created_at ?? now(),'updated_at'=>now()]
            );
            $correlation = (string) Str::uuid();
            $movementId = DB::table('stock_movements')->insertGetId([
                'tenant_id'=>$tenantId,'item_id'=>$item->id,'warehouse_code'=>$warehouse,'movement_type'=>$type,
                'quantity'=>$quantity,'reference_type'=>$referenceType,'reference_id'=>$referenceId,
                'correlation_id'=>$correlation,'idempotency_key'=>$idempotencyKey,'created_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now(),
            ]);
            $this->audit->record('inventory.stock.'.strtolower($type), $item, ['quantity'=>$current], ['quantity'=>$next], $correlation);
            return ['movement_id'=>$movementId,'quantity'=>$next,'duplicate'=>false];
        });
    }
}
