<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class HrComplianceCase extends Model { use BelongsToTenant; protected $fillable=['tenant_id','organization_id','case_no','category','severity','title','description','employee_id','source_key','due_on','status','remediation_notes','owner_user_id','resolved_by','resolved_at']; protected function casts():array{return ['due_on'=>'date','resolved_at'=>'datetime'];} public function employee():BelongsTo{return $this->belongsTo(Employee::class);} }
