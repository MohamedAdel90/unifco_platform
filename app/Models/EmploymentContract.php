<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentContract extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','employee_id','contract_no','contract_type','starts_on','ends_on','probation_ends_on',
        'basic_salary','housing_allowance','transport_allowance','other_allowances','currency','status','signed_on',
        'qiwa_status','qiwa_contract_ref','qiwa_documented_on','created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on'=>'date','ends_on'=>'date','probation_ends_on'=>'date','signed_on'=>'date','qiwa_documented_on'=>'date',
            'basic_salary'=>'decimal:2','housing_allowance'=>'decimal:2','transport_allowance'=>'decimal:2','other_allowances'=>'decimal:2',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
