<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['code', 'module', 'action', 'risk_level', 'is_business_authority', 'is_scope_aware', 'description'];
    protected function casts(): array { return ['is_business_authority' => 'boolean', 'is_scope_aware' => 'boolean']; }
}
