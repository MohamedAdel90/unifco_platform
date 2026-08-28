<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCoverageClaim extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id','organization_id','asset_id','asset_coverage_id','claim_no','claim_date','claimed_amount','approved_amount','status','description','resolution_notes','submitted_by','reviewed_by','reviewed_at'];

    protected function casts(): array
    {
        return ['claim_date'=>'date','claimed_amount'=>'decimal:2','approved_amount'=>'decimal:2','reviewed_at'=>'datetime'];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function coverage(): BelongsTo { return $this->belongsTo(AssetCoverage::class,'asset_coverage_id'); }
}
