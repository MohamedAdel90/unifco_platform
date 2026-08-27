<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCommissioningRecord extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','asset_id','status','inspection_date','inspection_result','checklist','notes','created_by','approved_by','approved_at'];
    protected function casts(): array { return ['inspection_date'=>'date','checklist'=>'array','approved_at'=>'datetime']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
}
