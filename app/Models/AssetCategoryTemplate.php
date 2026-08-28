<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AssetCategoryTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable=['tenant_id','organization_id','category','asset_type','name','specification_schema','active','code','system_group','default_criticality','default_useful_life_months','meter_based_supported','default_meter_unit','status'];
    protected function casts(): array { return ['specification_schema'=>'array','active'=>'boolean','meter_based_supported'=>'boolean']; }
}
