<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkflowRoleHomeRedirect
{
    private const ROLES=[
        'MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE','PROJECT_MANAGER','CEO',
    ];

    public function __construct(private AuthorizationService $authorization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user=$request->user();
        $isHome=$request->routeIs('dashboard') || $request->routeIs('public.home');

        if($user && $isHome && $this->authorization->allows($user,'operations.dashboard.view')){
            return redirect()->route('operations-manager.dashboard');
        }

        if($user && in_array($user->role,self::ROLES,true) && $isHome){
            return redirect()->route('workflow.workspace');
        }
        return $next($request);
    }
}
