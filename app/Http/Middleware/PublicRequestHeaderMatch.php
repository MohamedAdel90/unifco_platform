<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicRequestHeaderMatch
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->is('request-service')) {
            return $response;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        if (! str_contains($contentType, 'text/html') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</head>')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="public-request-home-header-match">
/* Match the request-page brand cluster to the public homepage header. */
body .top{height:auto!important;min-height:76px!important;background:rgba(255,255,255,.97)!important;border-bottom:1px solid #e7ebf0!important}
body .topin{width:min(1280px,94%)!important;min-height:76px!important;height:76px!important;margin-inline:auto!important;display:grid!important;grid-template-columns:minmax(220px,1fr) auto minmax(520px,1.7fr)!important;align-items:center!important;gap:20px!important;direction:ltr!important}
body .brandzone{display:flex!important;align-items:center!important;gap:20px!important;justify-self:start!important;min-width:0!important}
body .brandzone>a:first-child{display:flex!important;align-items:center!important;gap:9px!important;text-decoration:none!important;direction:ltr!important;min-width:174px!important;overflow:visible!important}
body .brandzone>a:first-child::after{content:"UNIFCO\A ONE FACILITY SHOP";white-space:pre!important;display:flex!important;flex-direction:column!important;font-family:Inter,Arial,sans-serif!important;font-size:24px!important;font-weight:800!important;line-height:.72!important;letter-spacing:.03em!important;color:#071f4d!important;padding-top:3px!important}
body .brandzone>a:first-child::before{content:"";position:absolute!important}
body .brandzone .logo{display:block!important;width:42px!important;height:50px!important;max-width:42px!important;min-width:42px!important;object-fit:cover!important;object-position:center top!important;padding:0!important;margin:0!important;flex:0 0 42px!important}
body .brandzone>a:first-child{position:relative!important}
body .brandzone>a:first-child span{display:none!important}
body .brandzone>a:first-child::after{background:linear-gradient(to bottom,#071f4d 0 68%,#ce122d 68% 100%);-webkit-background-clip:text;background-clip:text;color:transparent!important}
body .brandzone .lang{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:auto!important;min-width:48px!important;height:42px!important;min-height:42px!important;padding:9px 13px!important;border:1px solid #ccd4df!important;border-radius:6px!important;background:#fff!important;color:#071f4d!important;font-size:10px!important;font-weight:900!important;text-decoration:none!important;box-shadow:none!important}
body .request-pill{justify-self:center!important;min-width:170px!important;height:42px!important;min-height:42px!important;padding:10px 18px!important;border-radius:6px!important;background:#ce122d!important;color:#fff!important;font-size:11px!important;font-weight:900!important;box-shadow:none!important}
body .primary-nav{justify-self:end!important;gap:16px!important;min-width:0!important;height:76px!important;align-items:center!important}
body .primary-nav a{min-width:50px!important;padding:7px 1px!important;font-size:10px!important;gap:4px!important}
body .primary-nav .ico,body .primary-nav svg{width:20px!important;height:20px!important}
@media(max-width:1180px){
 body .topin{grid-template-columns:minmax(205px,.9fr) auto minmax(455px,1.55fr)!important;gap:14px!important}
 body .brandzone{gap:14px!important}
 body .brandzone>a:first-child::after{font-size:21px!important}
 body .primary-nav{gap:9px!important}
 body .primary-nav a{font-size:9px!important;min-width:44px!important}
}
@media(max-width:1030px){
 body .topin{grid-template-columns:minmax(210px,1fr) auto!important}
 body .primary-nav{display:none!important}
 body .request-pill{justify-self:end!important}
}
@media(max-width:620px){
 body .topin{width:94%!important;grid-template-columns:1fr auto!important;gap:8px!important}
 body .brandzone{gap:9px!important}
 body .brandzone>a:first-child{min-width:132px!important;gap:7px!important}
 body .brandzone .logo{width:38px!important;height:46px!important;max-width:38px!important;min-width:38px!important;flex-basis:38px!important}
 body .brandzone>a:first-child::after{font-size:18px!important}
 body .brandzone .lang{min-width:43px!important;height:40px!important;min-height:40px!important;padding:8px 10px!important}
 body .request-pill{min-width:auto!important;height:40px!important;min-height:40px!important;padding:8px 13px!important;font-size:10px!important}
}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));
        return $response;
    }
}
