<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicEmergencyMaintenancePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        // Legacy emergency-only shell intentionally disabled.
        // Emergency maintenance now follows the same unified request sequence
        // as all other service request types. Emergency-specific fields remain
        // handled by the unified emergency details presentation.
        return $next($request);
    }
}
