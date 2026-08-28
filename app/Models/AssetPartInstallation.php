<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetPartInstallation extends Model
{
    use BelongsToTenant;

    protected $fillable=['tenant_id','organization_id','work_order_id','asset_id','work_order_part_request_line_id','item_id','installed_part_number','installed_serial_number','installed_manufacturer','warehouse_code','quantity','unit_cost','total_cost','installed_by','installed_at','warranty_start','warranty_end','component_status','removed_at','removed_item_id','removed_serial','removed_disposition','notes'];
    protected function casts(): array { return ['installed_at'=>'datetime','removed_at'=>'datetime','warranty_start'=>'date','warranty_end'=>'date','quantity'=>'decimal:4','unit_cost'=>'decimal:2','total_cost'=>'decimal:2']; }
    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function removedItem(): BelongsTo { return $this->belongsTo(Item::class,'removed_item_id'); }
    public function installedBy(): BelongsTo { return $this->belongsTo(User::class,'installed_by'); }
}
