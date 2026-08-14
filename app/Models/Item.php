<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','item_code','name','uom','status'];
}
