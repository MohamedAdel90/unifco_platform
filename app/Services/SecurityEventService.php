<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SecurityEventService
{
    public function record(string $event, string $severity = 'INFO', ?User $user = null, array $context = []): void
    {
        if (! Schema::hasTable('security_events')) return;
        $request = app()->runningInConsole() ? null : request();
        DB::table('security_events')->insert([
            'tenant_id' => $user?->tenant_id, 'user_id' => $user?->id, 'event_type' => $event,
            'severity' => $severity, 'status' => 'OPEN', 'ip_address' => $request?->ip(),
            'session_id' => $request?->hasSession() ? $request->session()->getId() : null,
            'context' => $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
