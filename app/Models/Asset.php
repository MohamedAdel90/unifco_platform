<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','customer_id','parent_asset_id','asset_code','name','location_code','serial_no','warranty_expiry','acquisition_cost','salvage_value','useful_life_months','accumulated_depreciation','net_book_value','meter_value','commission_date','disposed_at','status'];
    protected function casts(): array { return ['acquisition_cost'=>'decimal:2','salvage_value'=>'decimal:2','accumulated_depreciation'=>'decimal:2','net_book_value'=>'decimal:2','meter_value'=>'decimal:4','commission_date'=>'date','disposed_at'=>'datetime']; }
    public function parent(): BelongsTo { return $this->belongsTo(self::class,'parent_asset_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}
