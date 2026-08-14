<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollLine extends Model { protected $fillable=['payroll_run_id','employee_id','basic_pay','allowances','deductions','net_pay']; protected function casts(): array { return ['basic_pay'=>'decimal:2','allowances'=>'decimal:2','deductions'=>'decimal:2','net_pay'=>'decimal:2']; } }
