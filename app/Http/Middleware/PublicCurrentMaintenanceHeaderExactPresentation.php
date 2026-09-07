<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenanceHeaderExactPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '') {
            return $response;
        }

        $locale = 'ar';
        $home = app(\App\Services\HomepageContentService::class)->getContent($locale);
        $header = view('public.partials.site-header', compact('home', 'locale'))->render();

        $patterns = [
            '/<header class="current-service-nav">.*?<\/header>/s',
            '/<header class="top">.*?<\/header>/s',
            '/<header class="top site-header"[^>]*>.*?<\/header>/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html)) {
                $html = preg_replace($pattern, $header, $html, 1) ?? $html;
                break;
            }
        }

        $response->setContent($html);
        return $response;
    }
}
