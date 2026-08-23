<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSessionValid
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user=Auth::user();
            if (! $request->session()->has('account_session_version')) {
                $request->session()->put('account_session_version',(int)$user->session_version);
            }
            $sessionVersion=(int)$request->session()->get('account_session_version');
            $invalid=$user->status!=='ACTIVE' || $user->locked_at || $sessionVersion!==(int)$user->session_version;
            if ($invalid) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors(['email'=>'Your session has been revoked or your account is unavailable.']);
            }
        }
        return $next($request);
    }
}
