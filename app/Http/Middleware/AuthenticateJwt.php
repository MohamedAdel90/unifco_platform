<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $header=(string)$request->header('Authorization');
        abort_unless(str_starts_with($header,'Bearer '),401,'Bearer JWT required.');
        try { $claims=app(JwtService::class)->decode(substr($header,7)); } catch (\Throwable $e) { abort(401,'Invalid or expired JWT.'); }
        $user=User::whereKey((int)($claims['sub']??0))->where('status','ACTIVE')->first();
        abort_unless($user && (int)$user->tenant_id===(int)($claims['tid']??0),401,'Invalid JWT subject.');
        $tenantHeader=$request->header('X-Tenant-Id');
        abort_if($tenantHeader!==null && (int)$tenantHeader!==(int)$user->tenant_id,403,'Tenant context mismatch.');
        auth()->setUser($user); $request->setUserResolver(fn()=>$user); $request->attributes->set('jwt_claims',$claims);
        return $next($request);
    }
}
