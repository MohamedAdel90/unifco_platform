<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','entity_type','entity_id','action','workflow_key','approval_role','step_order','sla_minutes','due_at','reminded_at','escalated_at','metadata',
        'requested_by','decided_by','status','decision_note','decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at'=>'datetime','due_at'=>'datetime','reminded_at'=>'datetime','escalated_at'=>'datetime','metadata'=>'array',
        ];
    }
}
