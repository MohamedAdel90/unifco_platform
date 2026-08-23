<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CandidateInterview extends Model { use BelongsToTenant; protected $fillable=['tenant_id','recruitment_candidate_id','interview_type','scheduled_at','interviewer_user_id','score','decision','feedback']; protected function casts(): array { return ['scheduled_at'=>'datetime','score'=>'decimal:2']; } public function candidate(): BelongsTo { return $this->belongsTo(RecruitmentCandidate::class,'recruitment_candidate_id'); } public function interviewer(): BelongsTo { return $this->belongsTo(User::class,'interviewer_user_id'); } }
