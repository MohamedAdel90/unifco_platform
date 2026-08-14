<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','order_no','product_name','planned_quantity','produced_quantity','status'];
    protected function casts(): array { return ['planned_quantity'=>'decimal:4','produced_quantity'=>'decimal:4']; }
}
