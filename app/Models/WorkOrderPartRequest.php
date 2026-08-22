<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class WorkOrderPartRequest extends Model
{
    use BelongsToTenant;

    protected $fillable=[
        'tenant_id','organization_id','request_no','work_order_id','asset_id','source_warehouse_id','destination_warehouse_id',
        'priority','status','reason','requested_by','approved_by','picked_by','issued_by','received_by',
        'approved_at','picked_at','issued_at','received_at','decision_note'
    ];

    protected function casts(): array
    {
        return ['approved_at'=>'datetime','picked_at'=>'datetime','issued_at'=>'datetime','received_at'=>'datetime'];
    }

    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function sourceWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class,'source_warehouse_id'); }
    public function destinationWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class,'destination_warehouse_id'); }
    public function lines(): HasMany { return $this->hasMany(WorkOrderPartRequestLine::class); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class,'requested_by'); }
}
