<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','po_number','supplier_name','order_date','total','status'];
    protected function casts(): array { return ['order_date'=>'date','total'=>'decimal:2']; }
}
