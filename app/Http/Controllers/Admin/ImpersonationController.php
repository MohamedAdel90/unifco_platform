<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\{AuditService, AuthorizationService};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function start(Request $request, int $user, AuthorizationService $authorization, AuditService $audit): RedirectResponse
    {
        $admin = $request->user();
        $authorization->authorize($admin, 'impersonation.read_only');
        $target = User::where('tenant_id', $admin->tenant_id)->where('status', 'ACTIVE')->findOrFail($user);
        abort_if($target->id === $admin->id, 422, 'You cannot impersonate your current account.');
        $request->session()->put('impersonation', ['admin_id' => $admin->id, 'target_id' => $target->id, 'started_at' => now()->toISOString(), 'read_only' => true]);
        $audit->record('security.impersonation.started', $target, [], ['admin_id' => $admin->id, 'target_user_id' => $target->id, 'mode' => 'READ_ONLY']);
        return redirect()->route('dashboard')->with('status', 'Read-only impersonation started.');
    }

    public function stop(Request $request, AuditService $audit): RedirectResponse
    {
        $state = $request->session()->get('impersonation');
        abort_unless(is_array($state) && isset($state['admin_id'], $state['target_id']), 404);
        $admin = User::whereKey($state['admin_id'])->firstOrFail();
        $target = User::whereKey($state['target_id'])->first();
        auth()->setUser($admin);
        $audit->record('security.impersonation.ended', $target, ['started_at' => $state['started_at'] ?? null], ['ended_at' => now()->toISOString(), 'mode' => 'READ_ONLY']);
        $request->session()->forget('impersonation');
        return redirect()->route('system-admin.dashboard')->with('status', 'Impersonation ended.');
    }
}
