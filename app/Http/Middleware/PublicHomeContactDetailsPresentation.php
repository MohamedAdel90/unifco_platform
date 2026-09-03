<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeContactDetailsPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $html = str_replace(
            [
                'https://wa.me/966501234567',
                '+966 50 123 4567',
                'mailto:info@unifco.com.sa',
                'info@unifco.com.sa',
            ],
            [
                'https://wa.me/966599402090',
                '0599402090',
                'mailto:info@unifco.com',
                'info@unifco.com',
            ],
            $html
        );

        $response->setContent($html);
        return $response;
    }
}
