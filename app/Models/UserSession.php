<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'session_id', 'login_at', 'last_activity_at', 'ip_address', 'user_agent', 'device', 'browser', 'status', 'revoked_at', 'revoked_by', 'revoke_reason'];
    protected function casts(): array { return ['login_at' => 'datetime', 'last_activity_at' => 'datetime', 'revoked_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
