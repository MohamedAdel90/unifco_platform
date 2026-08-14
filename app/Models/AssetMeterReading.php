<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AssetMeterReading extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','asset_id','reading','reading_date','notes','recorded_by'];
    protected function casts(): array { return ['reading'=>'decimal:4','reading_date'=>'date']; }
}
