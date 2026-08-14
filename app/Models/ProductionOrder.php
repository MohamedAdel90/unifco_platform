<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','order_no','product_name','planned_quantity','produced_quantity','status','item_id','bom_id','routing_id','warehouse_code','standard_cost','actual_cost','cost_variance'];
    protected function casts(): array { return ['planned_quantity'=>'decimal:4','produced_quantity'=>'decimal:4','standard_cost'=>'decimal:2','actual_cost'=>'decimal:2','cost_variance'=>'decimal:2']; }
}
