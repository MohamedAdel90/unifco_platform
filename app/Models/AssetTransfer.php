<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTransfer extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','asset_id','from_custody_id','to_custodian_user_id','to_custodian_name','to_department','to_branch','to_customer_site_id','from_location','to_location','transfer_date','transferred_by','status','reason','request_notes','requested_by','requested_at','reviewed_by','reviewed_at','review_notes','completed_at'];
    protected function casts(): array { return ['transfer_date'=>'date','requested_at'=>'datetime','reviewed_at'=>'datetime','completed_at'=>'datetime']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function fromCustody(): BelongsTo { return $this->belongsTo(AssetCustody::class,'from_custody_id'); }
}
