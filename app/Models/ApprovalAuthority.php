<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalAuthority extends Model
{
    protected $fillable = ['tenant_id', 'approval_type', 'role_id', 'level', 'access_scope_id', 'amount_from', 'amount_to', 'amount_limit', 'conditions', 'is_active', 'configured_by'];
    protected function casts(): array { return ['amount_from'=>'decimal:2','amount_to'=>'decimal:2','amount_limit' => 'decimal:2', 'conditions' => 'array', 'is_active' => 'boolean']; }
}
