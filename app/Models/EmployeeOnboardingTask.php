<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmployeeOnboardingTask extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','category','task_name','due_on','status','assigned_to','completed_at','notes']; protected function casts(): array { return ['due_on'=>'date','completed_at'=>'datetime']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function assignee(): BelongsTo { return $this->belongsTo(User::class,'assigned_to'); } }
