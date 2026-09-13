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
<style id="unifco-customer-number-mobile-alignment-v2">
/* Desktop/tablet: the customer card and the contract + site stack share one grid row.
   Both columns therefore expand and contract together with exactly the same outer height. */
@media (min-width:1051px){
    .uf-customer-context-layout{
        align-items:stretch!important;
    }
    .uf-customer-context-layout>.uf-context-customer{
        align-self:stretch!important;
        height:100%!important;
        min-height:100%!important;
        margin:0!important;
    }
    .uf-customer-context-layout>.uf-context-details{
        align-self:stretch!important;
        height:100%!important;
        min-height:100%!important;
        grid-template-rows:auto minmax(0,1fr)!important;
    }
    .uf-customer-context-layout>.uf-context-details>#contract-section{
        height:auto!important;
        margin:0!important;
    }
    .uf-customer-context-layout>.uf-context-details>#site-section{
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

        $html = str_replace('</head>', $style.'</head>', $html);
        $response->setContent($html);

        return $response;
    }
}
