<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['name', 'code', 'status'];

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}
