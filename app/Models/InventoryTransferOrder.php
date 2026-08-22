<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class InventoryTransferOrder extends Model
{
    use BelongsToTenant;

    protected $fillable=[
        'tenant_id','organization_id','transfer_no','from_warehouse_id','to_warehouse_id','status','purpose',
        'requested_by','issued_by','received_by','issued_at','received_at','notes'
    ];

    protected function casts(): array { return ['issued_at'=>'datetime','received_at'=>'datetime']; }

    public function fromWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class,'from_warehouse_id'); }
    public function toWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class,'to_warehouse_id'); }
    public function lines(): HasMany { return $this->hasMany(InventoryTransferOrderLine::class); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class,'requested_by'); }
}
