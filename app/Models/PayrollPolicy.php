<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PayrollPolicy extends Model
{
    use BelongsToTenant;

    protected $fillable=['tenant_id','code','name','effective_from','effective_to','pension_employee_rate','pension_employer_rate','saned_employee_rate','saned_employer_rate','occupational_hazard_employer_rate','overtime_basic_premium_rate','standard_month_days','status'];

    protected function casts(): array
    {
        return ['effective_from'=>'date','effective_to'=>'date','pension_employee_rate'=>'decimal:4','pension_employer_rate'=>'decimal:4','saned_employee_rate'=>'decimal:4','saned_employer_rate'=>'decimal:4','occupational_hazard_employer_rate'=>'decimal:4','overtime_basic_premium_rate'=>'decimal:4','standard_month_days'=>'decimal:2'];
    }
}
