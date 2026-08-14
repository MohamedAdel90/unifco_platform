<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ChartAccount extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','organization_id','code','name','type','normal_balance','posting_allowed','status'];
    protected function casts(): array { return ['posting_allowed'=>'boolean']; }
}
