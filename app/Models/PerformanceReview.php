<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PerformanceReview extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','performance_cycle_id','review_type','review_date','goal_score','competency_score','overall_score','rating','strengths','development_areas','manager_comments','employee_comments','status','prepared_by','submitted_by','submitted_at','approved_by','approved_at']; protected function casts(): array { return ['review_date'=>'date','submitted_at'=>'datetime','approved_at'=>'datetime','goal_score'=>'decimal:2','competency_score'=>'decimal:2','overall_score'=>'decimal:2']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function cycle(): BelongsTo { return $this->belongsTo(PerformanceCycle::class,'performance_cycle_id'); } }
