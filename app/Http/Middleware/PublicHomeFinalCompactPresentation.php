<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeFinalCompactPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</head>')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-home-final-compact">
.home-final-redesign{border-radius:12px 12px 0 0!important}
.home-final-redesign .final-cta{gap:14px 24px!important;padding:24px 20px 0!important}
.home-final-redesign .final-copy{max-width:560px!important}
.home-final-redesign .final-copy h2{margin:0 0 4px!important;font-size:clamp(22px,2.2vw,32px)!important;line-height:1.15!important;letter-spacing:-.01em!important}
.home-final-redesign .final-copy p{font-size:10.5px!important;line-height:1.6!important;max-width:520px!important}
.home-final-redesign .final-actions{gap:8px!important}
.home-final-redesign .final-actions .btn{min-width:130px!important;min-height:38px!important;padding:8px 14px!important;border-radius:8px!important;font-size:10px!important;font-weight:800!important}
.home-final-redesign .final-benefits{margin-top:4px!important}
.home-final-redesign .final-benefits span{min-height:50px!important;gap:8px!important;padding:10px 12px!important;font-size:9.5px!important;border-inline-start:1px solid rgba(255,255,255,.12)!important}
.home-final-redesign .final-benefits b{font-weight:700!important}
.home-final-redesign .final-benefits svg{width:18px!important;height:18px!important;flex:0 0 18px!important}
@media(max-width:1050px){
 .home-final-redesign .final-cta{padding:22px 18px 0!important;gap:12px!important}
 .home-final-redesign .final-copy h2{font-size:clamp(20px,4vw,28px)!important}
 .home-final-redesign .final-copy p{font-size:10px!important;max-width:100%!important}
 .home-final-redesign .final-actions{justify-content:center!important;flex-wrap:wrap!important}
}
@media(max-width:700px){
 .home-final-redesign .final-cta{padding:18px 14px 0!important}
 .home-final-redesign .final-copy h2{font-size:22px!important;line-height:1.2!important}
 .home-final-redesign .final-copy p{font-size:9.5px!important}
 .home-final-redesign .final-actions .btn{min-width:120px!important;min-height:36px!important;font-size:9.5px!important}
 .home-final-redesign .final-benefits{grid-template-columns:1fr 1fr!important}
 .home-final-redesign .final-benefits span{min-height:44px!important;font-size:9px!important;padding:9px 10px!important}
}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));
        return $response;
    }
}
