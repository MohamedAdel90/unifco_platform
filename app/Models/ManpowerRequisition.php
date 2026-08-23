<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class ManpowerRequisition extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','job_position_id','requisition_no','title','department','headcount','employment_type','work_location','budget_min','budget_max','needed_by','justification','status','requested_by','approved_by','approved_at']; protected function casts(): array { return ['needed_by'=>'date','approved_at'=>'datetime','budget_min'=>'decimal:2','budget_max'=>'decimal:2']; } public function position(): BelongsTo { return $this->belongsTo(JobPosition::class,'job_position_id'); } public function vacancies(): HasMany { return $this->hasMany(RecruitmentVacancy::class); } }
