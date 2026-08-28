<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AssetLifecycleEvent extends Model
{
    use BelongsToTenant;

    protected $fillable=['tenant_id','organization_id','asset_id','event_type','from_status','to_status','title','notes','metadata','performed_by','performed_at'];
    protected function casts(): array { return ['metadata'=>'array','performed_at'=>'datetime']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }

    protected static function booted(): void
    {
        static::updating(fn() => throw new LogicException('Asset lifecycle history is immutable.'));
        static::deleting(fn() => throw new LogicException('Asset lifecycle history cannot be deleted.'));
    }
}
