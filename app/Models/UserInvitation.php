<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'email', 'token_hash', 'status', 'invited_by', 'expires_at', 'accepted_at', 'revoked_at'];
    protected $hidden = ['token_hash'];
    protected function casts(): array { return ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
