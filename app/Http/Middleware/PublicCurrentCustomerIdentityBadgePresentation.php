<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentCustomerIdentityBadgePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="customer-summary"') || ! str_contains($html, '</head>')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-current-customer-identity-badge-v3">
/* CSS-only presentation: no observers or runtime DOM rewriting. */
#customer-summary .customer-code{
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:5px!important;
    min-height:27px!important;
    padding:4px 9px!important;
    border-radius:8px!important;
    background:#e8f1fd!important;
    color:#0b3471!important;
    font-size:9px!important;
    font-weight:900!important;
    line-height:1.35!important;
    direction:ltr!important;
    white-space:nowrap!important;
}
#customer-summary .customer-code::before{
    content:'#'!important;
    display:inline-block!important;
    margin:0!important;
    color:#1776d2!important;
    font:inherit!important;
    font-size:1em!important;
    font-weight:900!important;
    line-height:1!important;
}
#customer-summary .customer-icon{
    width:32px!important;
    height:32px!important;
    border-radius:8px!important;
    background-color:#e8f1fd!important;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231769c2' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='14' rx='2'/%3E%3Ccircle cx='8' cy='11' r='2'/%3E%3Cpath d='M5.5 16c.7-1.5 1.6-2.2 2.5-2.2s1.8.7 2.5 2.2M13 10h5M13 14h4'/%3E%3C/svg%3E")!important;
    background-repeat:no-repeat!important;
    background-position:center!important;
    background-size:18px 18px!important;
    color:transparent!important;
    font-size:0!important;
    line-height:0!important;
    overflow:hidden!important;
}
#customer-summary .customer-icon > *{
    display:none!important;
}
html[dir="ltr"] #customer-summary .customer-code,
html[dir="rtl"] #customer-summary .customer-code{
    direction:ltr!important;
}
</style>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $response->setContent($html);

        return $response;
    }
}
