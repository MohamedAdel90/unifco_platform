<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmployeeGoal extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','performance_cycle_id','title','description','weight','target_value','actual_value','unit','due_on','status','score','created_by']; protected function casts(): array { return ['due_on'=>'date','weight'=>'decimal:2','target_value'=>'decimal:2','actual_value'=>'decimal:2','score'=>'decimal:2']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function cycle(): BelongsTo { return $this->belongsTo(PerformanceCycle::class,'performance_cycle_id'); } }
