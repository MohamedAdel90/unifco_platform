<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class PurchaseOrder extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','created_by','approved_by','supplier_id','purchase_requisition_id','po_number','supplier_name','order_date','total','status'];
    protected function casts(): array { return ['order_date'=>'date','total'=>'decimal:2']; }
    public function lines(): HasMany { return $this->hasMany(PurchaseOrderLine::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function requisition(): BelongsTo { return $this->belongsTo(PurchaseRequisition::class,'purchase_requisition_id'); }
}
