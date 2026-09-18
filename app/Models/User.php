<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['tenant_id','organization_id','customer_id','employee_id','name','name_ar','name_en','email','mobile','password','role','user_type','customer_portal_role','status','last_login_at','force_password_change','mfa_status','locked_at','session_version'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['email_verified_at'=>'datetime','last_login_at'=>'datetime','locked_at'=>'datetime','force_password_change'=>'boolean','session_version'=>'integer','password'=>'hashed']; }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['is_primary', 'granted_by', 'granted_at', 'revoked_at', 'reason'])->withTimestamps();
    }

    public function activeRoles(): BelongsToMany
    {
        return $this->roles()->wherePivotNull('revoked_at')->where('roles.is_active', true);
    }

    public function accessScopes(): BelongsToMany
    {
        return $this->belongsToMany(AccessScope::class, 'user_scopes')
            ->withPivot(['source', 'granted_by', 'expires_at', 'reason'])->withTimestamps();
    }

    public function operationalDomains(): BelongsToMany
    {
        return $this->belongsToMany(OperationalDomain::class, 'user_operational_domains')
            ->withPivot(['assignment_type','priority','is_active'])->withTimestamps();
    }

    public function routedServiceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'operations_manager_id');
    }

    public function sessions(): HasMany { return $this->hasMany(UserSession::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }

    public function hasRole(string $code): bool
    {
        return $this->activeRoles()->where('roles.code', strtoupper($code))->exists();
    }
}
