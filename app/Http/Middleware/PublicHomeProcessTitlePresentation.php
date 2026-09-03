<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeProcessTitlePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '<html lang="ar"')) {
            return $response;
        }

        $html = str_replace(
            [
                'طريقة عمل واضحة من البداية إلى النهاية -- من التقييم إلى التحسين',
                'طريقة عمل واضحة من البداية إلى النهاية — من التقييم إلى التحسين',
            ],
            'طريقة عمل واضحة من التقييم إلى التحسين',
            $html
        );

        $response->setContent($html);

        return $response;
    }
}
