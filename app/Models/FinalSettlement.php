<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalSettlement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id','organization_id','employee_id','end_of_service_policy_id','settlement_no','termination_reason','service_start_date','last_working_day',
        'service_years','last_wage_basis','eos_base_award','eos_multiplier','eos_award','unused_leave_days','leave_daily_rate','leave_encashment',
        'unpaid_salary','other_earnings','notice_compensation','employee_debt','advance_recovery','other_deductions','gross_entitlements','total_deductions','net_settlement',
        'status','notes','calculation_snapshot','created_by','submitted_by','submitted_at','approved_by','approved_at','paid_by','paid_at',
    ];

    protected function casts(): array
    {
        return [
            'service_start_date'=>'date','last_working_day'=>'date','submitted_at'=>'datetime','approved_at'=>'datetime','paid_at'=>'datetime','calculation_snapshot'=>'array',
            'service_years'=>'decimal:6','last_wage_basis'=>'decimal:2','eos_base_award'=>'decimal:2','eos_multiplier'=>'decimal:4','eos_award'=>'decimal:2',
            'unused_leave_days'=>'decimal:2','leave_daily_rate'=>'decimal:4','leave_encashment'=>'decimal:2','unpaid_salary'=>'decimal:2','other_earnings'=>'decimal:2',
            'notice_compensation'=>'decimal:2','employee_debt'=>'decimal:2','advance_recovery'=>'decimal:2','other_deductions'=>'decimal:2','gross_entitlements'=>'decimal:2',
            'total_deductions'=>'decimal:2','net_settlement'=>'decimal:2',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function policy(): BelongsTo { return $this->belongsTo(EndOfServicePolicy::class,'end_of_service_policy_id'); }
}
