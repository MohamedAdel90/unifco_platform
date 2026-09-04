<?php

namespace App\Http\Middleware;

use App\Services\HomepageContentService;
use App\Services\HomepageHeroRenderer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeHeroPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $requested = $request->query('lang');
        $locale = in_array($requested, ['ar', 'en'], true)
            ? $requested
            : $request->session()->get('public_locale', 'ar');

        $home = app(HomepageContentService::class)->getContent($locale);
        $html = app(HomepageHeroRenderer::class)->render((string) $response->getContent(), $home);
        $response->setContent($html);

        return $response;
    }
}
