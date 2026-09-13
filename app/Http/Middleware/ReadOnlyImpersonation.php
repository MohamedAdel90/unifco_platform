<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ReadOnlyImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        $state = $request->session()->get('impersonation');
        if (! is_array($state) || ! isset($state['admin_id'], $state['target_id'])) return $next($request);
        if ($request->routeIs('admin.impersonation.stop')) return $next($request);
        abort_unless(in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true), 403, 'Impersonation is read-only. Exit impersonation to perform changes.');

        $admin = Auth::user();
        abort_unless($admin && (int) $admin->id === (int) $state['admin_id'] && $admin->status === 'ACTIVE' && ! $admin->locked_at, 403);
        $target = User::where('tenant_id', $admin->tenant_id)->where('status', 'ACTIVE')->find($state['target_id']);
        if (! $target) {
            $request->session()->forget('impersonation');
            return redirect()->route('system-admin.dashboard')->withErrors(['impersonation' => 'Target user is no longer available.']);
        }
        Auth::setUser($target);
        $request->setUserResolver(fn () => $target);
        view()->share(['impersonationAdmin' => $admin, 'impersonationTarget' => $target]);
        return $next($request);
    }
}
