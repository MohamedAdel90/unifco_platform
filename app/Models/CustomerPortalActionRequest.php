<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CustomerPortalActionRequest extends Model
{
    use BelongsToTenant;

    protected $fillable=[
        'tenant_id','organization_id','customer_id','user_id','action_type','reference_type','reference_id','status',
        'notes','attachment_path','attachment_name','attachment_mime','submitted_at','resolved_at','resolved_by','resolution_notes',
    ];

    protected function casts(): array
    {
        return ['submitted_at'=>'datetime','resolved_at'=>'datetime'];
    }
}
