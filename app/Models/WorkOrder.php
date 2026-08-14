<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','work_order_no','asset_id','maintenance_type','priority','status','planned_start'];
    protected function casts(): array { return ['planned_start'=>'datetime']; }
}
