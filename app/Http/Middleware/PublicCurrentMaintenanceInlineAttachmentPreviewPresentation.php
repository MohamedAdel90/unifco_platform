<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenanceInlineAttachmentPreviewPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        // Legacy inline preview layer disabled.
        // PublicUniversalCompactAttachmentsPresentation is now the single
        // authoritative attachment preview/limit implementation.
        return $next($request);
    }
}
