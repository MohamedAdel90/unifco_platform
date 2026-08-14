<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','entity_type','entity_id','action','requested_by','decided_by','status','decision_note','decided_at'];
    protected function casts(): array { return ['decided_at'=>'datetime']; }
}
