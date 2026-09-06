<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicRequestLegacyChromeCleanup
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance', 'public.current-maintenance.spare-parts')
            || ! method_exists($response, 'getContent')
            || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        if ($html === '' || ! str_contains($html, 'id="unifco-request-chrome"')) {
            return $response;
        }

        // Legacy global presentation middleware still decorates the maintenance
        // route after the dedicated shared chrome has been rendered. Strip those
        // legacy shells so only the approved fixed upper section remains.
        $patterns = [
            '/<header class="current-service-nav">.*?<\/header>/s',
            '/<header class="top">.*?<\/header>/s',
            '/<div class="page-head">.*?<\/div>/s',
            '/<section class="request-clarity-hero">.*?<\/section>/s',
            '/<div class="request-ticket-note">.*?<\/div>/s',
            '/<section class="panel request-selector-panel">.*?<\/section>/s',
        ];

        foreach ($patterns as $pattern) {
            $html = preg_replace($pattern, '', $html) ?? $html;
        }

        // Enforce exactly one shared chrome even if another presentation layer
        // accidentally duplicates it in a future change.
        $first = strpos($html, '<div id="unifco-request-chrome">');
        if ($first !== false) {
            $next = strpos($html, '<div id="unifco-request-chrome">', $first + 1);
            while ($next !== false) {
                $end = strpos($html, '</div>', $next);
                if ($end === false) {
                    break;
                }
                $html = substr($html, 0, $next).substr($html, $end + 6);
                $next = strpos($html, '<div id="unifco-request-chrome">', $first + 1);
            }
        }

        $response->setContent($html);

        return $response;
    }
}
