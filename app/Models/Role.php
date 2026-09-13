<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['tenant_id', 'code', 'name_en', 'name_ar', 'description', 'is_system_role', 'grants_business_authority', 'requires_approval', 'is_active'];

    protected function casts(): array
    {
        return ['is_system_role' => 'boolean', 'grants_business_authority' => 'boolean', 'requires_approval' => 'boolean', 'is_active' => 'boolean'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withPivot(['is_primary', 'granted_by', 'granted_at', 'revoked_at', 'reason'])->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withPivot(['effect', 'granted_by'])->withTimestamps();
    }
}
