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

        $isArabic = str_contains($html, '<html lang="ar"');
        $locale = $isArabic ? 'ar' : 'en';
        $otherLocale = $isArabic ? 'en' : 'ar';
        $home = route('public.home', ['lang' => $locale]);
        $languageUrl = $request->fullUrlWithQuery(['lang' => $otherLocale]);
        $languageLabel = $isArabic ? 'EN' : 'AR';

        $brand = '<div class="brandzone request-home-brand">'
            .'<a class="request-brand-link" href="'.$home.'">'
            .'<span class="site-logo-frame"><img class="site-logo" src="'.route('brand.logo').'" alt="UNIFCO"></span>'
            .'<span class="brand-copy"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span>'
            .'</a>'
            .'<a class="lang" href="'.$languageUrl.'">'.$languageLabel.'</a>'
            .'</div>';

        $html = preg_replace('/<div class="brandzone">.*?<\/div>/s', $brand, $html, 1) ?? $html;

        $style = <<<'HTML'
<style id="public-request-home-header-match">
body .top{height:auto!important;min-height:76px!important;background:rgba(255,255,255,.97)!important;border-bottom:1px solid #e7ebf0!important}
body .topin{width:min(1280px,94%)!important;min-height:76px!important;height:76px!important;margin-inline:auto!important;display:grid!important;grid-template-columns:minmax(245px,1fr) auto minmax(520px,1.7fr)!important;align-items:center!important;gap:20px!important;direction:ltr!important}
body .request-home-brand{display:flex!important;align-items:center!important;gap:20px!important;justify-self:start!important;min-width:0!important}
body .request-brand-link{display:flex!important;align-items:center!important;gap:9px!important;text-decoration:none!important;direction:ltr!important;flex:0 0 auto!important}
body .request-brand-link .site-logo-frame{width:42px!important;height:50px!important;overflow:hidden!important;flex:0 0 42px!important;display:block!important}
body .request-brand-link .site-logo{display:block!important;width:42px!important;height:65px!important;max-width:none!important;object-fit:cover!important;object-position:center top!important;margin:0!important;padding:0!important}
body .request-brand-link .brand-copy{display:flex!important;flex-direction:column!important;line-height:.88!important;direction:ltr!important}
body .request-brand-link .brand-copy strong{font-family:Inter,Arial,sans-serif!important;font-size:24px!important;font-weight:800!important;letter-spacing:.03em!important;color:#071f4d!important;white-space:nowrap!important}
body .request-brand-link .brand-copy small{margin-top:5px!important;font-family:Inter,Arial,sans-serif!important;font-size:6px!important;font-weight:900!important;letter-spacing:.16em!important;color:#ce122d!important;white-space:nowrap!important}
body .request-home-brand .lang{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-width:48px!important;height:42px!important;min-height:42px!important;padding:9px 13px!important;border:1px solid #ccd4df!important;border-radius:6px!important;background:#fff!important;color:#071f4d!important;font-size:10px!important;font-weight:900!important;text-decoration:none!important;box-shadow:none!important}
body .request-pill{justify-self:center!important;min-width:170px!important;height:42px!important;min-height:42px!important;padding:10px 18px!important;border-radius:6px!important;background:#ce122d!important;color:#fff!important;font-size:11px!important;font-weight:900!important;box-shadow:none!important}
body .primary-nav{justify-self:end!important;gap:16px!important;min-width:0!important;height:76px!important;align-items:center!important}
body .primary-nav a{min-width:50px!important;padding:7px 1px!important;font-size:10px!important;gap:4px!important}
body .primary-nav .ico,body .primary-nav svg{width:20px!important;height:20px!important}
@media(max-width:1180px){
 body .topin{grid-template-columns:minmax(225px,.95fr) auto minmax(455px,1.55fr)!important;gap:14px!important}
 body .request-home-brand{gap:14px!important}
 body .request-brand-link .brand-copy strong{font-size:21px!important}
 body .primary-nav{gap:9px!important}
 body .primary-nav a{font-size:9px!important;min-width:44px!important}
}
@media(max-width:1030px){
 body .topin{grid-template-columns:minmax(235px,1fr) auto!important}
 body .primary-nav{display:none!important}
 body .request-pill{justify-self:end!important}
}
@media(max-width:620px){
 body .topin{width:94%!important;grid-template-columns:1fr auto!important;gap:8px!important}
 body .request-home-brand{gap:9px!important}
 body .request-brand-link{gap:7px!important}
 body .request-brand-link .site-logo-frame{width:38px!important;height:46px!important;flex-basis:38px!important}
 body .request-brand-link .site-logo{width:38px!important;height:59px!important}
 body .request-brand-link .brand-copy strong{font-size:21px!important}
 body .request-brand-link .brand-copy small{font-size:5.5px!important;margin-top:4px!important}
 body .request-home-brand .lang{min-width:43px!important;height:40px!important;min-height:40px!important;padding:8px 10px!important}
 body .request-pill{min-width:auto!important;height:40px!important;min-height:40px!important;padding:8px 13px!important;font-size:10px!important}
}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));
        return $response;
    }
}
