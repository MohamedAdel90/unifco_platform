<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany,HasOne};
class RecruitmentCandidate extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','recruitment_vacancy_id','employee_id','candidate_no','name','email','mobile','nationality','source','stage','expected_salary','notes']; protected function casts(): array { return ['expected_salary'=>'decimal:2']; } public function vacancy(): BelongsTo { return $this->belongsTo(RecruitmentVacancy::class,'recruitment_vacancy_id'); } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function interviews(): HasMany { return $this->hasMany(CandidateInterview::class); } public function offer(): HasOne { return $this->hasOne(EmploymentOffer::class); } }
