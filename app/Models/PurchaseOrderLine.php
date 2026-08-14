<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $fillable=['purchase_order_id','line_no','item_id','quantity','unit_price'];
    protected function casts(): array { return ['quantity'=>'decimal:4','unit_price'=>'decimal:2']; }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
}
