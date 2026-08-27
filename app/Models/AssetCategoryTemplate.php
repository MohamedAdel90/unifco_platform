<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AssetCategoryTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable=['tenant_id','organization_id','category','asset_type','name','specification_schema','active'];
    protected function casts(): array { return ['specification_schema'=>'array','active'=>'boolean']; }
}
