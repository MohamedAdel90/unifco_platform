<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPortalAlert extends Model
{
    protected $fillable=['tenant_id','customer_id','alert_type','title','message','severity','due_date','source_type','source_id','read_at'];
    protected function casts(): array { return ['due_date'=>'date','read_at'=>'datetime']; }
}
