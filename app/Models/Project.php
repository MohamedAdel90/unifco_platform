<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','project_no','name','customer_id','planned_start','planned_finish','budget','status'];
    protected function casts(): array { return ['planned_start'=>'date','planned_finish'=>'date','budget'=>'decimal:2']; }
}
