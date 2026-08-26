<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkflowRoleHomeRedirect
{
    private const ROLES=[
        'MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE','PROJECT_MANAGER','CEO',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user=$request->user();
        if($user && $request->routeIs('dashboard') && in_array($user->role,self::ROLES,true)){
            return redirect()->route('workflow.workspace');
        }
        return $next($request);
    }
}
