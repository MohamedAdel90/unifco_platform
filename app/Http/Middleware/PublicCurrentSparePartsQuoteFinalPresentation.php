<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Legacy duplicate spare-parts presentation.
 *
 * The active spare-parts UI is rendered by PublicCurrentSparePartsAssetPresentation.
 * Keeping a second renderer here caused the older markup to overwrite the newer
 * image/unit/compact-selected-list design while the response middleware stack
 * was unwinding. This middleware intentionally remains registered as a no-op
 * for backward compatibility with the bootstrap middleware list.
 */
class PublicCurrentSparePartsQuoteFinalPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
