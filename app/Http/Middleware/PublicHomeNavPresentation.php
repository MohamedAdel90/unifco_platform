<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeNavPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '') {
            return $response;
        }

        // The public homepage owns its approved shared header presentation.
        // Strip the generic branding compatibility layer after the inner
        // middleware has run so it cannot override the shared navigation.
        $html = preg_replace('/<style id="dynamic-brand-logo-presentation">.*?<\/style>/s', '', $html) ?? $html;
        $html = str_replace('<link rel="preload" as="image" href="/brand/unifco-logo-v2.webp">', '', $html);

        $locale = str_contains($html, '<html lang="en"') ? 'en' : 'ar';
        $home = app(\App\Services\HomepageContentService::class)->getContent($locale);
        $header = view('public.partials.site-header', compact('home', 'locale'))->render();

        $html = preg_replace('/<header class="top">.*?<\/header>/s', $header, $html, 1) ?? $html;
        $response->setContent($html);

        return $response;
    }
}
