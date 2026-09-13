<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessScope extends Model
{
    public const TYPES = ['GLOBAL', 'COMPANY', 'DEPARTMENT', 'BRANCH', 'PROJECT', 'SITE', 'CUSTOMER', 'CONTRACT', 'ASSET', 'OWN_RECORDS', 'ASSIGNED_RECORDS'];

    protected $fillable = ['tenant_id', 'scope_type', 'scope_id', 'name', 'constraints', 'is_active'];
    protected function casts(): array { return ['constraints' => 'array', 'is_active' => 'boolean']; }
}
