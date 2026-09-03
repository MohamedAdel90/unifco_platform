<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeServicesKickerPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        $style = <<<'HTML'
<style id="unifco-home-services-kicker-v1">
#services .section-head > .kicker{
    font-size:16.5px!important;
    line-height:1.35!important;
}
</style>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $response->setContent($html);

        return $response;
    }
}
