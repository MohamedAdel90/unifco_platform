<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicRequestResponsivePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, '<html')) {
            return $response;
        }

        $styles = <<<'HTML'
<style id="unifco-public-request-responsive-final">
html,body{width:100%!important;max-width:100%!important;min-width:0!important;overflow-x:hidden!important}
body{position:relative!important}
*,*::before,*::after{max-width:100%}
img,video,canvas,svg{height:auto;max-width:100%}
.top,.page-head,main,form,#routine-form,.panel,section{min-width:0!important}
.wrap,.top .wrap,main.wrap{width:min(1180px,calc(100% - 32px))!important;max-width:1180px!important;min-width:0!important;margin-inline:auto!important}
.panel{width:100%!important;max-width:100%!important;min-width:0!important}
.service-line,.grid,.visit,.uploads,.asset-methods,.asset-card.show,.customer-details,.uf-customer-card>.grid,.uf-current-customer-details,.uf-spare-details-grid{min-width:0!important}
.service-line>*,.grid>*,.visit>*,.uploads>*,.asset-methods>*,.asset-card.show>*,.customer-details>*,.uf-customer-card>.grid>*,.uf-current-customer-details>*,.uf-spare-details-grid>*{min-width:0!important}
input,select,textarea,button{max-width:100%!important;min-width:0}
.uf-spare-table-wrap{width:100%!important;max-width:100%!important;overflow-x:auto!important;-webkit-overflow-scrolling:touch!important}
.uf-spare-table{width:100%!important;max-width:none!important}

@media(max-width:900px){
 .wrap,.top .wrap,main.wrap{width:calc(100% - 24px)!important}
 .top{height:auto!important;min-height:64px!important;padding:8px 0!important}
 .logo{max-height:44px!important;width:auto!important}
 .page-head{padding:20px 12px 12px!important}
 .page-head h1{font-size:24px!important;line-height:1.35!important}
 .panel{padding:14px!important;border-radius:12px!important}
 .service-line,.visit,.uploads{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .asset-methods{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .asset-card.show,.customer-details,.uf-customer-card>.grid,.uf-current-customer-details,.uf-spare-details-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .span4,.uf-current-customer-details .field.address,.uf-spare-notes{grid-column:1/-1!important}
}

@media(max-width:640px){
 .wrap,.top .wrap,main.wrap{width:calc(100% - 16px)!important}
 .top .wrap{gap:10px!important}
 .back{font-size:10px!important;white-space:nowrap!important}
 .logo{max-width:128px!important;max-height:40px!important}
 .page-head{padding:16px 8px 10px!important}
 .page-head h1{font-size:21px!important}
 .page-head p{font-size:10px!important;line-height:1.7!important}
 .panel{padding:11px!important;margin-bottom:10px!important;border-radius:10px!important}
 .section-title{font-size:13px!important;line-height:1.5!important;flex-wrap:wrap!important}
 .service-line,.grid,.visit,.uploads,.asset-card.show,.customer-details,.uf-customer-card>.grid,.uf-current-customer-details,.uf-spare-details-grid{grid-template-columns:1fr!important}
 .span2,.span4,.uf-current-customer-details .field.address,.uf-spare-notes{grid-column:auto!important}
 .lookup-row,.asset-search.show{display:grid!important;grid-template-columns:1fr!important;width:100%!important}
 #routine-form .lookup-row,.uf-customer-card .lookup-row{display:grid!important;grid-template-columns:1fr!important;width:100%!important}
 #routine-form #customer_number,.uf-customer-card #customer_number,#routine-form #customer-lookup,.uf-customer-card #customer-lookup{width:100%!important;max-width:100%!important;flex:none!important}
 .asset-methods{grid-template-columns:1fr 1fr!important}
 .customer-summary-head{display:grid!important;grid-template-columns:1fr!important;gap:10px!important}
 .customer-name,.detail-value{max-width:100%!important;white-space:normal!important;overflow-wrap:anywhere!important}
 .uf-spare-choice{padding:10px!important;gap:8px!important;align-items:flex-start!important}
 .uf-spare-choice-main{min-width:0!important}
 .uf-spare-choice strong{font-size:12px!important}
 .uf-spare-section{padding:8px!important}
 .uf-spare-section-title{align-items:flex-start!important;flex-direction:column!important;gap:3px!important}
 .uf-spare-assets{display:grid!important;grid-template-columns:1fr!important}
 .uf-spare-asset-chip{max-width:none!important;width:100%!important}
 .uf-spare-table{min-width:760px!important}
 .uf-spare-table th,.uf-spare-table td{white-space:nowrap!important}
 .uf-spare-selected{max-height:300px!important;overflow:auto!important;-webkit-overflow-scrolling:touch!important}
 input,select,textarea,.btn,.uf-spare-option{font-size:16px!important}
 textarea{min-height:90px!important}
}

@media(max-width:390px){
 .wrap,.top .wrap,main.wrap{width:calc(100% - 12px)!important}
 .panel{padding:9px!important}
 .asset-methods{grid-template-columns:1fr!important}
 .num{width:22px!important;height:22px!important;min-width:22px!important}
}
</style>
HTML;

        // Guarantee a correct mobile viewport even if an older presentation layer rewrites the head.
        if (preg_match('/<meta[^>]+name=["\']viewport["\'][^>]*>/i', $html)) {
            $html = preg_replace('/<meta[^>]+name=["\']viewport["\'][^>]*>/i', '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">', $html, 1);
        } else {
            $html = str_replace('<head>', '<head><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">', $html);
        }

        $html = str_replace('</head>', $styles.'</head>', $html);
        $response->setContent($html);

        return $response;
    }
}
