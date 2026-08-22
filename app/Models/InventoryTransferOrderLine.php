<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferOrderLine extends Model
{
    protected $fillable=['inventory_transfer_order_id','item_id','requested_quantity','issued_quantity','received_quantity'];
    protected function casts(): array { return ['requested_quantity'=>'decimal:4','issued_quantity'=>'decimal:4','received_quantity'=>'decimal:4']; }
    public function transfer(): BelongsTo { return $this->belongsTo(InventoryTransferOrder::class,'inventory_transfer_order_id'); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
}
