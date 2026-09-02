<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeLocationPresentation
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

        $response->setContent(str_replace(
            ['جدة، المملكة العربية السعودية', 'Jeddah, Saudi Arabia'],
            ['الرياض، المملكة العربية السعودية', 'Riyadh, Saudi Arabia'],
            $html
        ));

        return $response;
    }
}
