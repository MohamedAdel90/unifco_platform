<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditService
{
    public function record(string $action, ?Model $entity = null, array $before = [], array $after = [], ?string $correlationId = null, ?string $reason = null, array $metadata = []): void
    {
        $user = Auth::user();
        $createdAt = now();
        $correlationId ??= (string) Str::uuid();
        $beforeState = $before ? json_encode($this->redact($before), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $afterState = $after ? json_encode($this->redact($after), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $request = app()->runningInConsole() ? null : request();
        $row = [
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'correlation_id' => $correlationId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ];
        if (Schema::hasColumn('audit_logs', 'entry_hash')) {
            $previousHash = DB::table('audit_logs')->where('tenant_id', $user?->tenant_id)->latest('id')->value('entry_hash');
            $sessionId = $request?->hasSession() ? $request->session()->getId() : null;
            $row += [
                'ip_address' => $request?->ip(), 'session_id' => $sessionId, 'reason' => $reason,
                'metadata' => $metadata ? json_encode($this->redact($metadata), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'previous_hash' => $previousHash,
            ];
            $row['entry_hash'] = hash('sha256', implode('|', [
                $previousHash, $user?->tenant_id, $user?->id, $action, $row['entity_type'], $row['entity_id'],
                $correlationId, $beforeState, $afterState, $reason, $createdAt->toISOString(),
            ]));
        }
        DB::table('audit_logs')->insert($row);
    }

    private function redact(array $value): array
    {
        foreach ($value as $key => $item) {
            if (preg_match('/password|token|secret|hash/i', (string) $key)) $value[$key] = '[REDACTED]';
            elseif (is_array($item)) $value[$key] = $this->redact($item);
        }
        return $value;
    }
}
