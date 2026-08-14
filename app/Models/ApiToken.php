<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    protected $fillable=['tenant_id','user_id','name','token_hash','abilities','last_used_at','expires_at','revoked_at'];
    protected $hidden=['token_hash'];
    protected function casts(): array { return ['abilities'=>'array','last_used_at'=>'datetime','expires_at'=>'datetime','revoked_at'=>'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function isActive(): bool { return ! $this->revoked_at && (! $this->expires_at || $this->expires_at->isFuture()); }
}
