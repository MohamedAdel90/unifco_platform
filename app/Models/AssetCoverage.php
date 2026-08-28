<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};

class AssetCoverage extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id','organization_id','asset_id','coverage_type','provider','reference_no','starts_at','expires_at','coverage_amount','currency','scope','status','renewed_from_id','created_by'];

    protected function casts(): array
    {
        return ['starts_at'=>'date','expires_at'=>'date','coverage_amount'=>'decimal:2'];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function renewedFrom(): BelongsTo { return $this->belongsTo(self::class,'renewed_from_id'); }
    public function claims(): HasMany { return $this->hasMany(AssetCoverageClaim::class); }

    public function isExpired(): bool { return $this->expires_at->isBefore(today()); }
    public function expiresSoon(int $days=30): bool { return !$this->isExpired() && $this->expires_at->lte(today()->addDays($days)); }
}
