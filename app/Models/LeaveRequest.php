<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LeaveRequest extends Model { use BelongsToTenant; protected $fillable=['tenant_id','employee_id','leave_policy_id','leave_type','starts_on','ends_on','days','day_portion','reason','status','decision_notes','requested_by','decided_by','decided_at']; protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date','days'=>'decimal:2','decided_at'=>'datetime']; } public function policy(): BelongsTo { return $this->belongsTo(LeavePolicy::class,'leave_policy_id'); } }
