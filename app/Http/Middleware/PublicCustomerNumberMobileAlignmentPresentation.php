<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCustomerNumberMobileAlignmentPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="customer_number"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-customer-number-mobile-alignment-v3">
/* This block is intentionally emitted at the end of BODY so it wins over the
   earlier context-layout middleware on the response path. */
@media (min-width:1051px){
    .uf-customer-context-layout{
        align-items:stretch!important;
    }
    .uf-customer-context-layout>.uf-context-customer{
        align-self:stretch!important;
        height:auto!important;
        min-height:0!important;
        margin:0!important;
    }
    .uf-customer-context-layout>.uf-context-details{
        align-self:stretch!important;
        height:auto!important;
        min-height:0!important;
        grid-template-rows:auto minmax(0,1fr)!important;
    }
    .uf-customer-context-layout>.uf-context-details>#contract-section{
        align-self:start!important;
        height:auto!important;
        margin:0!important;
    }
    .uf-customer-context-layout>.uf-context-details>#site-section{
        align-self:stretch!important;
        height:100%!important;
        min-height:0!important;
        margin:0!important;
    }
}

@media (max-width:620px){
    .uf-context-customer>.lookup-row>#customer_number{
        grid-column:1/-1!important;
        width:60%!important;
        max-width:60%!important;
        min-width:0!important;
        justify-self:auto!important;
        margin-left:auto!important;
        margin-right:0!important;
        box-sizing:border-box!important;
    }
    html[dir="ltr"] .uf-context-customer>.lookup-row>#customer_number{
        margin-left:0!important;
        margin-right:auto!important;
    }
}
</style>
HTML;

        if (str_contains($html, '</body>')) {
            $html = str_replace('</body>', $style.'</body>', $html);
        } else {
            $html .= $style;
        }
        $response->setContent($html);

        return $response;
    }
}
