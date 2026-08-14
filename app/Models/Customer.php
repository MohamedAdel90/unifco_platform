<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToTenant;
    protected $fillable = ['tenant_id','organization_id','customer_code','name','email','status'];
}
