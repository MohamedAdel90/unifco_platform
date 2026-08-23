<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class EmployeeLeaveBalance extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','leave_policy_id','balance_year','opening_days','accrued_days','used_days','adjusted_days','carried_forward_days']; protected function casts(): array { return ['opening_days'=>'decimal:2','accrued_days'=>'decimal:2','used_days'=>'decimal:2','adjusted_days'=>'decimal:2','carried_forward_days'=>'decimal:2']; } public function availableDays(): float { return (float)$this->opening_days+(float)$this->accrued_days+(float)$this->adjusted_days+(float)$this->carried_forward_days-(float)$this->used_days; } }
