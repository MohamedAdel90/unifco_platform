<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserSessionService
{
    public function register(User $user, Request $request): void
    {
        if (! Schema::hasTable('user_sessions')) return;
        $agent = (string) $request->userAgent();
        DB::table('user_sessions')->updateOrInsert(['session_id' => $request->session()->getId()], [
            'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'login_at' => now(), 'last_activity_at' => now(),
            'ip_address' => $request->ip(), 'user_agent' => $agent,
            'device' => preg_match('/Mobile|Android|iPhone/i', $agent) ? 'Mobile' : 'Desktop',
            'browser' => $this->browser($agent), 'status' => 'ACTIVE', 'revoked_at' => null, 'revoked_by' => null,
            'revoke_reason' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function touch(Request $request): bool
    {
        if (! Schema::hasTable('user_sessions') || ! $request->hasSession()) return true;
        $row = DB::table('user_sessions')->where('session_id', $request->session()->getId())->first();
        if (! $row) return true;
        if ($row->status !== 'ACTIVE' || $row->revoked_at) return false;
        if (! $row->last_activity_at || now()->diffInMinutes($row->last_activity_at) >= 2) {
            DB::table('user_sessions')->where('id', $row->id)->update(['last_activity_at' => now(), 'updated_at' => now()]);
        }
        return true;
    }

    public function end(Request $request, string $status = 'LOGGED_OUT'): void
    {
        if (! Schema::hasTable('user_sessions') || ! $request->hasSession()) return;
        DB::table('user_sessions')->where('session_id', $request->session()->getId())->update([
            'status' => $status, 'revoked_at' => now(), 'last_activity_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function browser(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg/') => 'Edge', str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome', str_contains($agent, 'Safari/') => 'Safari', default => 'Unknown',
        };
    }
}
