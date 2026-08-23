<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmploymentOffer extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','recruitment_candidate_id','offer_no','basic_salary','housing_allowance','transport_allowance','other_allowances','currency','proposed_start_date','probation_days','status','created_by','accepted_at']; protected function casts(): array { return ['proposed_start_date'=>'date','accepted_at'=>'datetime','basic_salary'=>'decimal:2','housing_allowance'=>'decimal:2','transport_allowance'=>'decimal:2','other_allowances'=>'decimal:2']; } public function candidate(): BelongsTo { return $this->belongsTo(RecruitmentCandidate::class,'recruitment_candidate_id'); } }
