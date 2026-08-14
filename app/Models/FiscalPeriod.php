<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FiscalPeriod extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','code','starts_on','ends_on','status','closed_by','closed_at'];
    protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date','closed_at'=>'datetime']; }
}
