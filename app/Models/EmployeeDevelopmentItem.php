<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmployeeDevelopmentItem extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','item_type','title','description','target_date','status','estimated_cost','provider']; protected function casts(): array { return ['target_date'=>'date','estimated_cost'=>'decimal:2']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } }
