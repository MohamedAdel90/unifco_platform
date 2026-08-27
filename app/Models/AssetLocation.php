<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class AssetLocation extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','customer_id','customer_site_id','parent_id','location_type','code','name','description','latitude','longitude','active'];
    protected function casts(): array { return ['latitude'=>'decimal:7','longitude'=>'decimal:7','active'=>'boolean']; }
    public function parent(): BelongsTo { return $this->belongsTo(self::class,'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class,'parent_id'); }
    public function site(): BelongsTo { return $this->belongsTo(CustomerSite::class,'customer_site_id'); }
}
