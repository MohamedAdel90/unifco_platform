<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDocument extends Model
{
    use BelongsToTenant;

    protected $fillable=['tenant_id','organization_id','asset_id','document_type','title','path','original_name','mime_type','version','issued_at','expires_at','uploaded_by'];
    protected function casts(): array { return ['issued_at'=>'date','expires_at'=>'date']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
}
