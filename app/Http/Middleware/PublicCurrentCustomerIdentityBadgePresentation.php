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
        if (! str_contains($html, 'id="customer-summary"') || ! str_contains($html, '</body>')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-current-customer-identity-badge-v1">
/* Current-customer summary only: preserve layout and refine the two identity indicators. */
#customer-summary.uf-legacy-customer-summary .customer-code{
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
#customer-summary.uf-legacy-customer-summary .customer-code:before{
    content:'#'!important;
    display:inline-block!important;
    margin:0!important;
    color:#1776d2!important;
    font:inherit!important;
    font-size:1em!important;
    font-weight:900!important;
    line-height:1!important;
}
#customer-summary.uf-legacy-customer-summary .customer-icon{
    width:32px!important;
    height:32px!important;
    border-radius:8px!important;
    background:#e8f1fd!important;
    color:#1769c2!important;
    display:grid!important;
    place-items:center!important;
}
#customer-summary.uf-legacy-customer-summary .customer-icon svg{
    display:block!important;
    width:18px!important;
    height:18px!important;
    fill:none!important;
    stroke:currentColor!important;
    stroke-width:1.8!important;
    stroke-linecap:round!important;
    stroke-linejoin:round!important;
}
html[dir="ltr"] #customer-summary.uf-legacy-customer-summary .customer-code,
html[dir="rtl"] #customer-summary.uf-legacy-customer-summary .customer-code{
    direction:ltr!important;
}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-current-customer-identity-badge-script-v1">
(()=>{
    const idCardSvg='<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="11" r="2"/><path d="M5.5 16c.7-1.5 1.6-2.2 2.5-2.2s1.8.7 2.5 2.2M13 10h5M13 14h4"/></svg>';

    const enhance=()=>{
        const summary=document.getElementById('customer-summary');
        if(!summary)return;
        const icon=summary.querySelector('.customer-icon');
        if(icon && icon.dataset.ufIdentityIcon!=='1'){
            icon.dataset.ufIdentityIcon='1';
            icon.innerHTML=idCardSvg;
            icon.setAttribute('aria-hidden','true');
        }
    };

    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',enhance,{once:true});
    else enhance();

    new MutationObserver(enhance).observe(document.documentElement,{childList:true,subtree:true});
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
