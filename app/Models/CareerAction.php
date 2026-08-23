<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CareerAction extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','action_type','from_job_position_id','to_job_position_id','old_basic_salary','new_basic_salary','effective_on','reason','status','requested_by','approved_by','approved_at']; protected function casts(): array { return ['effective_on'=>'date','approved_at'=>'datetime','old_basic_salary'=>'decimal:2','new_basic_salary'=>'decimal:2']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function fromPosition(): BelongsTo { return $this->belongsTo(JobPosition::class,'from_job_position_id'); } public function toPosition(): BelongsTo { return $this->belongsTo(JobPosition::class,'to_job_position_id'); } }
