<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ServiceContract extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','customer_id','contract_no','title','starts_on','ends_on',
        'contract_value','currency','billing_cycle','scope','sla_summary','status',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'contract_value' => 'decimal:2',
        ];
    }
}
