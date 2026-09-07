<?php

namespace App\Http\Middleware;

use App\Services\HomepageContentService;
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

        $locale = $request->query('lang', 'ar') === 'en' ? 'en' : 'ar';
        $home = app(HomepageContentService::class)->getContent($locale);
        $sharedHeader = view('public.partials.site-header', compact('home', 'locale'))->render();

        // This middleware is intentionally the final response pass for the
        // maintenance request experience. Remove every legacy/generated header
        // and then place exactly the same shared homepage header inside the
        // persistent request chrome. Keeping it inside the chrome also avoids
        // the old selector CSS rule that hides body > header.top.
        $patterns = [
            '/<header class="uf-request-nav">.*?<\/header>/s',
            '/<header class="current-service-nav">.*?<\/header>/s',
            '/<header class="top request-homepage-header">.*?<\/header>/s',
            '/<header class="top site-header"[^>]*>.*?<\/header>/s',
            '/<header class="top">.*?<\/header>/s',
            '/<div class="page-head">.*?<\/div>/s',
            '/<section class="request-clarity-hero">.*?<\/section>/s',
            '/<div class="request-ticket-note">.*?<\/div>/s',
            '/<section class="panel request-selector-panel">.*?<\/section>/s',
            '/<style id="unifco-shared-site-header-style">.*?<\/style>/s',
            '/<script id="unifco-shared-site-header-script">.*?<\/script>/s',
        ];

        foreach ($patterns as $pattern) {
            $html = preg_replace($pattern, '', $html) ?? $html;
        }

        // Enforce exactly one request chrome if a previous presentation layer
        // accidentally duplicated it.
        $first = strpos($html, '<div id="unifco-request-chrome">');
        if ($first !== false) {
            $nextChrome = strpos($html, '<div id="unifco-request-chrome">', $first + 1);
            while ($nextChrome !== false) {
                $end = strpos($html, '</div>', $nextChrome);
                if ($end === false) {
                    break;
                }
                $html = substr($html, 0, $nextChrome).substr($html, $end + 6);
                $nextChrome = strpos($html, '<div id="unifco-request-chrome">', $first + 1);
            }
        }

        if (str_contains($html, '<div id="unifco-request-chrome">')) {
            $html = str_replace(
                '<div id="unifco-request-chrome">',
                '<div id="unifco-request-chrome">'.$sharedHeader,
                $html
            );
        }

        $response->setContent($html);

        return $response;
    }
}
