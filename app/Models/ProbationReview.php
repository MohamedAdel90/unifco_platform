<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProbationReview extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','review_date','probation_end_date','performance_score','attendance_score','conduct_score','overall_score','recommendation','comments','reviewed_by']; protected function casts(): array { return ['review_date'=>'date','probation_end_date'=>'date','performance_score'=>'decimal:2','attendance_score'=>'decimal:2','conduct_score'=>'decimal:2','overall_score'=>'decimal:2']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } }
