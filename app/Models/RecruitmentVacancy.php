<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class RecruitmentVacancy extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','manpower_requisition_id','vacancy_no','title','description','opens_on','closes_on','status']; protected function casts(): array { return ['opens_on'=>'date','closes_on'=>'date']; } public function requisition(): BelongsTo { return $this->belongsTo(ManpowerRequisition::class,'manpower_requisition_id'); } public function candidates(): HasMany { return $this->hasMany(RecruitmentCandidate::class); } }
