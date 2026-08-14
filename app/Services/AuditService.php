<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditService
{
    public function record(string $action, ?Model $entity = null, array $before = [], array $after = [], ?string $correlationId = null): void
    {
        $user = Auth::user();
        DB::table('audit_logs')->insert([
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'correlation_id' => $correlationId ?: (string) Str::uuid(),
            'before_state' => $before ? json_encode($before) : null,
            'after_state' => $after ? json_encode($after) : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
