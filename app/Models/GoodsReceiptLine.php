<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptLine extends Model
{
    protected $fillable=['goods_receipt_id','purchase_order_line_id','item_id','quantity','unit_cost'];
    protected function casts(): array { return ['quantity'=>'decimal:4','unit_cost'=>'decimal:2']; }
    public function receipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class,'goods_receipt_id'); }
    public function purchaseOrderLine(): BelongsTo { return $this->belongsTo(PurchaseOrderLine::class); }
}
