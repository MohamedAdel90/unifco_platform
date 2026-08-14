<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','project_no','name','customer_id','opportunity_id','planned_start','planned_finish','budget','actual_cost','status'];
    protected function casts(): array { return ['planned_start'=>'date','planned_finish'=>'date','budget'=>'decimal:2','actual_cost'=>'decimal:2']; }
}
