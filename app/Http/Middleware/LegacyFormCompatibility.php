<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyFormCompatibility
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('post') && $request->is('hr/employees') && ! $request->filled('status')) {
            $request->merge(['status' => 'ACTIVE']);
        }

        if ($request->isMethod('post') && $request->is('maintenance/work-orders/*/complete') && ! $request->filled('completion_notes')) {
            $request->merge(['completion_notes' => 'Completed through compatible operational workflow.']);
        }

        return $next($request);
    }
}
