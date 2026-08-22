<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseBin extends Model
{
    use BelongsToTenant;

    protected $fillable=['tenant_id','warehouse_id','bin_code','name','zone','status'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
}
