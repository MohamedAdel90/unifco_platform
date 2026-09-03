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
        $legacy = route('public.request-service');
        $advanced = route('public.current-maintenance');

        $heroOld = '<div class="hero-actions"><a class="btn red" href="'.$legacy.'">';
        $heroNew = '<div class="hero-actions"><a class="btn red" href="'.$advanced.'">';
        if (str_contains($html, $heroOld)) {
            $html = str_replace($heroOld, $heroNew, $html);
        }

        $topOld = '<div class="nav-actions"><a class="lang"';
        $topPos = strpos($html, $topOld);
        if ($topPos !== false) {
            $topEnd = strpos($html, '</div>', $topPos);
            if ($topEnd !== false) {
                $topChunk = substr($html, $topPos, $topEnd - $topPos + 6);
                $newTopChunk = str_replace('href="'.$legacy.'"', 'href="'.$advanced.'"', $topChunk);
                $html = substr_replace($html, $newTopChunk, $topPos, strlen($topChunk));
            }
        }

        $response->setContent($html);
        return $response;
    }
}
