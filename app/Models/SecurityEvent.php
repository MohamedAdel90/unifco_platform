<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'event_type', 'severity', 'status', 'ip_address', 'session_id', 'context', 'occurred_at', 'resolved_at', 'resolved_by'];
    protected function casts(): array { return ['context' => 'array', 'occurred_at' => 'datetime', 'resolved_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
