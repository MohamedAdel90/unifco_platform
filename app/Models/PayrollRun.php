<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollRun extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','payroll_policy_id','payroll_no','period_start','period_end','posting_date','currency','gross_total','employee_deductions_total','employer_contributions_total','net_total','status','created_by','approved_by','journal_id']; protected function casts(): array { return ['period_start'=>'date','period_end'=>'date','posting_date'=>'date','gross_total'=>'decimal:2','employee_deductions_total'=>'decimal:2','employer_contributions_total'=>'decimal:2','net_total'=>'decimal:2']; } public function lines(): HasMany { return $this->hasMany(PayrollLine::class); } }
