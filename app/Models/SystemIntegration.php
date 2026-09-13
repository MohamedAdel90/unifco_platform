<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemIntegration extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'metadata' => 'array',
            'last_checked_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }
}
