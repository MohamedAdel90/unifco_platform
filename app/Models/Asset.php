<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','asset_code','name','acquisition_cost','commission_date','status'];
    protected function casts(): array { return ['acquisition_cost'=>'decimal:2','commission_date'=>'date']; }
}
