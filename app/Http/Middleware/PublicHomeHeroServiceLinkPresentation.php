<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeHeroServiceLinkPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        $old = '<div class="hero-actions"><a class="btn red" href="'.route('public.request-service').'">';
        $new = '<div class="hero-actions"><a class="btn red" href="'.route('public.current-maintenance').'">';
        if (str_contains($html, $old)) {
            $html = str_replace($old, $new, $html);
            $response->setContent($html);
        }

        return $response;
    }
}
