<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicEmergencyBannerCompactPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $style = <<<'HTML'
<style id="unifco-emergency-banner-compact-v1">
.emergency-banner{
    padding:10px 16px!important;
    margin-bottom:10px!important;
    border-radius:10px!important;
    min-height:0!important;
    box-shadow:0 6px 16px rgba(227,19,44,.12)!important;
}
.emergency-banner h2{
    margin:0!important;
    font-size:15px!important;
    line-height:1.25!important;
    font-weight:900!important;
}
.emergency-banner p{
    margin:3px 0 0!important;
    font-size:8px!important;
    line-height:1.45!important;
    font-weight:700!important;
}
@media(max-width:650px){
    .emergency-banner{padding:9px 12px!important}
    .emergency-banner h2{font-size:14px!important}
    .emergency-banner p{font-size:8px!important}
}
</style>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $response->setContent($html);

        return $response;
    }
}
