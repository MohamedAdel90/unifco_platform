<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, ?string $ability=null): Response
    {
        $plain=$request->bearerToken();
        abort_unless($plain,401,'Bearer token required.');
        $token=ApiToken::with('user')->where('token_hash',hash('sha256',$plain))->first();
        abort_unless($token && $token->isActive() && $token->user && $token->user->status==='ACTIVE',401,'Invalid or expired token.');
        if ($ability) abort_unless(in_array('*',$token->abilities ?? [],true) || in_array($ability,$token->abilities ?? [],true),403,'Token ability denied.');
        Auth::setUser($token->user);
        $token->forceFill(['last_used_at'=>now()])->save();
        $request->attributes->set('api_token',$token);
        return $next($request);
    }
}
