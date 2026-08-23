<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class EndOfServicePolicy extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','code','name','effective_from','effective_to',
        'first_five_years_month_factor','after_five_years_month_factor',
        'resignation_two_to_five_multiplier','resignation_five_to_ten_multiplier','resignation_ten_plus_multiplier',
        'standard_month_days','include_housing_allowance','include_transport_allowance','include_other_allowances','status',
    ];

    protected function casts(): array
    {
        return [
            'effective_from'=>'date','effective_to'=>'date',
            'first_five_years_month_factor'=>'decimal:4','after_five_years_month_factor'=>'decimal:4',
            'resignation_two_to_five_multiplier'=>'decimal:4','resignation_five_to_ten_multiplier'=>'decimal:4','resignation_ten_plus_multiplier'=>'decimal:4',
            'standard_month_days'=>'decimal:2','include_housing_allowance'=>'boolean','include_transport_allowance'=>'boolean','include_other_allowances'=>'boolean',
        ];
    }
}
