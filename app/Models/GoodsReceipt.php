<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class GoodsReceipt extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','purchase_order_id','created_by','journal_id','receipt_no','warehouse_code','receipt_date','status'];
    protected function casts(): array { return ['receipt_date'=>'date']; }
    public function lines(): HasMany { return $this->hasMany(GoodsReceiptLine::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
}
