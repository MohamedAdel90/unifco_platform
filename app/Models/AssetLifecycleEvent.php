<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLifecycleEvent extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','asset_id','event_type','from_status','to_status','title','notes','metadata','performed_by','performed_at'];
    protected function casts(): array { return ['metadata'=>'array','performed_at'=>'datetime']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
}
