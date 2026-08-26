<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CustomerActivityEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','customer_id','event_type','reference_type','reference_id',
        'title','description','visibility','metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
