<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany,HasMany};

class OperationalDomain extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id','code','name_en','name_ar','is_active','sort_order'];
    protected $casts = ['is_active'=>'boolean'];

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_operational_domains')
            ->withPivot(['assignment_type','priority','is_active'])->withTimestamps();
    }

    public function assets(): HasMany { return $this->hasMany(Asset::class); }
    public function serviceRequests(): HasMany { return $this->hasMany(ServiceRequest::class); }
}
