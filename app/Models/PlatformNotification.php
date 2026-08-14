<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PlatformNotification extends Model
{
    use BelongsToTenant;

    protected $fillable=['tenant_id','user_id','type','title','message','action_url','read_at'];
    protected function casts(): array { return ['read_at'=>'datetime']; }
}
