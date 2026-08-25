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
        $requestLabel = $isArabic ? 'طلب خدمة' : 'Request Service';

        preg_match('/<nav class="primary-nav">(.*?)<\/nav>/s', $html, $navMatch);
        $navInner = $navMatch[1] ?? '';

        $mobileLabels = $isArabic
            ? ['الرئيسية'=>'home','من نحن'=>'about','الخدمات'=>'services','عملاؤنا'=>'industries','المشاريع'=>'projects','الوظائف'=>'careers','تواصل معنا'=>'contact']
            : ['Home'=>'home','About Us'=>'about','Services'=>'services','Our Clients'=>'industries','Projects'=>'projects','Careers'=>'careers','Contact us'=>'contact'];
        $mobileLinks = '';
        foreach ($mobileLabels as $label => $anchor) {
            $mobileLinks .= '<a href="'.$home.'#'.$anchor.'">'.$label.'</a>';
        }

        $header = '<header class="top request-homepage-header">'
            .'<div class="wrap nav">'
            .'<a class="brand-link" href="'.$home.'">'
            .'<span class="site-logo-frame"><img class="site-logo" src="'.route('brand.logo').'" alt="UNIFCO"></span>'
            .'<span class="brand-copy"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span>'
            .'</a>'
            .'<nav class="nav-links">'.$navInner.'</nav>'
            .'<div class="nav-actions">'
            .'<a class="btn red" id="requestCenterAction" href="#requestForm">'.$requestLabel.'</a>'
            .'<a class="lang" href="'.$languageUrl.'">'.$languageLabel.'</a>'
            .'</div>'
            .'<button class="menu-toggle" type="button" aria-label="Menu" onclick="document.getElementById(\'requestMobileMenu\').classList.toggle(\'open\')">☰</button>'
            .'</div>'
            .'<div class="wrap mobile-menu" id="requestMobileMenu">'.$mobileLinks.'</div>'
            .'</header>';

        $html = preg_replace('/<header class="top">.*?<\/header>/s', $header, $html, 1) ?? $html;

        $style = <<<'HTML'
<style id="public-request-home-header-match">
.request-homepage-header.top{position:sticky!important;top:0!important;z-index:60!important;height:auto!important;min-height:0!important;background:rgba(255,255,255,.97)!important;border-bottom:1px solid #e7ebf0!important;backdrop-filter:blur(14px)!important}
.request-homepage-header .wrap{width:min(1280px,94%)!important;margin-inline:auto!important}
.request-homepage-header .nav{min-height:76px!important;height:auto!important;display:flex!important;align-items:center!important;gap:20px!important;direction:ltr!important;position:relative!important}
.request-homepage-header .brand-link{display:flex!important;align-items:center!important;gap:9px!important;margin-right:auto!important;direction:ltr!important;flex:0 0 auto!important;text-decoration:none!important}
.request-homepage-header .site-logo-frame{width:42px!important;height:50px!important;overflow:hidden!important;flex:0 0 42px!important;display:block!important}
.request-homepage-header .site-logo{display:block!important;width:42px!important;height:65px!important;max-width:none!important;object-fit:cover!important;object-position:center top!important;margin:0!important;padding:0!important}
.request-homepage-header .brand-copy{display:flex!important;flex-direction:column!important;line-height:.88!important;direction:ltr!important}
.request-homepage-header .brand-copy strong{font-family:Inter,Arial,sans-serif!important;font-size:24px!important;font-weight:700!important;letter-spacing:.03em!important;line-height:.88!important;color:#071f4d!important;white-space:nowrap!important}
.request-homepage-header .brand-copy small{margin-top:5px!important;font-family:Inter,Arial,sans-serif!important;font-size:6px!important;font-weight:900!important;letter-spacing:.16em!important;line-height:1!important;color:#ce122d!important;white-space:nowrap!important}
.request-homepage-header .nav-links{display:flex!important;align-items:center!important;gap:23px!important;direction:rtl!important;margin:0!important;min-width:0!important}
html[dir="ltr"] .request-homepage-header .nav-links{direction:ltr!important}
.request-homepage-header .nav-links a{position:relative!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:4px!important;min-width:48px!important;padding:7px 1px!important;color:#25354d!important;text-decoration:none!important;font-size:10px!important;font-weight:800!important;line-height:1.15!important;white-space:nowrap!important}
.request-homepage-header .nav-links .ico{display:grid!important;place-items:center!important;width:20px!important;height:20px!important}
.request-homepage-header .nav-links svg{display:block!important;width:20px!important;height:20px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.7!important;stroke-linecap:round!important;stroke-linejoin:round!important}
.request-homepage-header .nav-links a:first-child:after,.request-homepage-header .nav-links a:hover:after{content:""!important;position:absolute!important;inset-inline:0!important;bottom:-7px!important;height:2px!important;background:#ce122d!important}
.request-homepage-header .nav-actions{display:flex!important;align-items:center!important;gap:9px!important;direction:ltr!important;flex:0 0 auto!important}
.request-homepage-header .btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:8px!important;min-height:42px!important;height:auto!important;padding:10px 18px!important;border:1px solid transparent!important;border-radius:6px!important;color:#fff!important;font-size:11px!important;font-weight:900!important;text-decoration:none!important;box-shadow:none!important}
.request-homepage-header .btn.red{background:#ce122d!important}
.request-homepage-header #requestCenterAction{position:absolute!important;left:50%!important;top:50%!important;transform:translate(-50%,-50%)!important;z-index:3!important;margin:0!important;white-space:nowrap!important}
.request-homepage-header .lang{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:auto!important;min-width:0!important;min-height:42px!important;height:auto!important;padding:9px 13px!important;border:1px solid #ccd4df!important;border-radius:6px!important;font-family:inherit!important;font-size:10px!important;font-weight:900!important;color:#071f4d!important;background:#fff!important;text-decoration:none!important;box-shadow:none!important}
.request-homepage-header .menu-toggle{display:none!important;border:0!important;background:#071f4d!important;color:#fff!important;width:42px!important;height:42px!important;border-radius:7px!important;font-size:20px!important;cursor:pointer!important}
.request-homepage-header .mobile-menu{display:none!important;padding:0 0 14px!important}
.request-homepage-header .mobile-menu.open{display:grid!important;grid-template-columns:1fr 1fr!important;gap:7px!important}
.request-homepage-header .mobile-menu a{padding:10px 12px!important;background:#f5f7fa!important;border-radius:7px!important;color:#071f4d!important;font-size:11px!important;font-weight:800!important;text-decoration:none!important}
@media(max-width:1060px){.request-homepage-header .nav-links{gap:12px!important}.request-homepage-header .nav-links a{min-width:42px!important;font-size:9px!important}}
@media(max-width:850px){.request-homepage-header .nav-links{display:none!important}.request-homepage-header .menu-toggle{display:block!important}.request-homepage-header .brand-link{margin-right:auto!important}.request-homepage-header .nav{gap:12px!important}}
@media(max-width:700px){.request-homepage-header .site-logo-frame{width:38px!important;height:46px!important;flex-basis:38px!important}.request-homepage-header .site-logo{width:38px!important;height:59px!important}.request-homepage-header .brand-copy strong{font-size:21px!important}.request-homepage-header .brand-copy small{font-size:5.5px!important}.request-homepage-header #requestCenterAction{display:none!important}.request-homepage-header .nav{min-height:70px!important}}
</style>
HTML;

        $html = str_replace('</head>', $style."\n</head>", $html);
        $response->setContent($html);
        return $response;
    }
}
