<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCustody extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','asset_id','custodian_user_id','custodian_name','department','branch','status','assigned_at','returned_at','notes','assigned_by','returned_by'];
    protected function casts(): array { return ['assigned_at'=>'datetime','returned_at'=>'datetime']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function custodian(): BelongsTo { return $this->belongsTo(User::class,'custodian_user_id'); }
}
