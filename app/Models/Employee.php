<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','job_position_id','employee_no','name','email','hire_date','status'];
    protected function casts(): array { return ['hire_date' => 'date']; }
    public function position(): BelongsTo { return $this->belongsTo(JobPosition::class,'job_position_id'); }
}
