<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenanceHeaderExactPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $styles = <<<'HTML'
<style id="unifco-current-maintenance-header-exact">
.current-service-nav{position:sticky!important;top:0!important;z-index:80!important;height:64px!important;min-height:64px!important;background:rgba(255,255,255,.98)!important;border-bottom:1px solid #e7ebf0!important;box-shadow:0 1px 4px rgba(7,31,77,.045)!important;backdrop-filter:blur(12px)!important}
.current-service-nav .nav-wrap{width:min(1280px,94%)!important;max-width:1280px!important;height:64px!important;min-height:64px!important;margin:0 auto!important;padding:0!important;display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:15px!important;direction:ltr!important}
.current-service-nav .brand-link{display:flex!important;align-items:center!important;gap:7px!important;margin:0!important;padding:0!important;direction:ltr!important;text-decoration:none!important;flex:0 0 auto!important}
.current-service-nav .site-logo-frame{width:35px!important;height:42px!important;overflow:hidden!important;flex:0 0 35px!important}
.current-service-nav .site-logo{width:35px!important;height:54px!important;object-fit:cover!important;object-position:center top!important;display:block!important}
.current-service-nav .brand-copy{display:flex!important;flex-direction:column!important;line-height:.88!important;min-width:0!important}
.current-service-nav .brand-copy strong{font-family:Inter,Arial,sans-serif!important;font-size:20px!important;line-height:1!important;letter-spacing:.03em!important;color:#071f4d!important;font-weight:800!important}
.current-service-nav .brand-copy small{margin-top:4px!important;font-family:Inter,Arial,sans-serif!important;font-size:5.8px!important;line-height:1!important;font-weight:900!important;letter-spacing:.15em!important;color:#ce122d!important}
.current-service-nav .nav-actions{width:auto!important;display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:7px!important;direction:ltr!important;flex:0 0 auto!important;margin:0!important;padding:0!important}
.current-service-nav .lang{width:auto!important;min-width:44px!important;height:36px!important;min-height:36px!important;margin:0!important;padding:0 10px!important;border:1px solid #d2d9e3!important;border-radius:6px!important;font-size:9px!important;font-weight:900!important;color:#071f4d!important;background:#fff!important;text-decoration:none!important;display:inline-flex!important;align-items:center!important;justify-content:center!important}
.current-service-nav .request-btn{width:auto!important;min-width:100px!important;height:36px!important;min-height:36px!important;margin:0!important;padding:0 14px!important;border-radius:6px!important;background:#d7142f!important;color:#fff!important;text-decoration:none!important;font-size:10px!important;font-weight:900!important;display:inline-flex!important;align-items:center!important;justify-content:center!important}
.current-service-nav .primary-nav{margin-left:auto!important;display:flex!important;align-items:stretch!important;justify-content:flex-end!important;gap:17px!important;direction:rtl!important;height:64px!important;flex:0 1 auto!important}
.current-service-nav .primary-nav a{position:relative!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:3px!important;color:#20304a!important;text-decoration:none!important;font-size:9.5px!important;line-height:1.15!important;font-weight:800!important;white-space:nowrap!important;min-width:54px!important;height:64px!important;padding:0!important}
.current-service-nav .primary-nav a:first-child:after,.current-service-nav .primary-nav a:hover:after{content:""!important;position:absolute!important;left:5px!important;right:5px!important;bottom:0!important;height:2px!important;background:#d7142f!important}
.current-service-nav .nav-icon{display:grid!important;place-items:center!important;height:19px!important;color:#203759!important}
.current-service-nav .nav-icon svg{width:19px!important;height:19px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.75!important;stroke-linecap:round!important;stroke-linejoin:round!important}
.current-service-nav .menu-toggle{display:none!important;border:0!important;background:#071f4d!important;color:#fff!important;width:38px!important;height:38px!important;border-radius:7px!important;font-size:18px!important}
@media(max-width:1280px){.current-service-nav .nav-wrap{width:96%!important;gap:11px!important}.current-service-nav .primary-nav{gap:9px!important}.current-service-nav .primary-nav a{min-width:48px!important;font-size:9px!important}.current-service-nav .request-btn{min-width:94px!important;padding:0 12px!important}}
@media(max-width:1080px){.current-service-nav .primary-nav{display:none!important}.current-service-nav .menu-toggle{display:grid!important;place-items:center!important;margin-left:auto!important}}
@media(max-width:700px){.current-service-nav{height:60px!important;min-height:60px!important}.current-service-nav .nav-wrap{height:60px!important;min-height:60px!important;width:94%!important}.current-service-nav .brand-copy strong{font-size:18px!important}.current-service-nav .brand-copy small{font-size:5.4px!important}.current-service-nav .site-logo-frame{width:33px!important;height:40px!important;flex:0 0 33px!important}.current-service-nav .site-logo{width:33px!important;height:51px!important}.current-service-nav .lang{display:none!important}.current-service-nav .request-btn{height:36px!important;min-height:36px!important;min-width:96px!important;padding:0 12px!important}.current-service-nav .menu-toggle{width:36px!important;height:36px!important}}
@media(max-width:520px){.current-service-nav .brand-copy{display:none!important}.current-service-nav .nav-wrap{gap:8px!important}.current-service-nav .request-btn{min-width:90px!important;font-size:9.5px!important}}
</style>
HTML;
        $html = str_replace('</head>', $styles.'</head>', $html);

        if (! str_contains($html, 'class="current-service-nav"')) {
            return $response;
        }

        $icons = [
            'home' => '<svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5M9 21v-7h6v7"/></svg>',
            'about' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg>',
            'services' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M4 12a8 8 0 0 1 .7-3.3M7 5.2A8 8 0 0 1 12 4m5 1.2A8 8 0 0 1 20 12m-.7 3.3A8 8 0 0 1 12 20m-5-1.2A8 8 0 0 1 4 12"/></svg>',
            'industries' => '<svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V9l5 3V9l5 3V5h4v16"/></svg>',
            'projects' => '<svg viewBox="0 0 24 24"><path d="M4 21V7h6v14M10 21V3h6v18M16 21v-9h4v9"/></svg>',
            'clients' => '<svg viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M2 21v-2a6 6 0 0 1 12 0v2M12 21v-2a6 6 0 0 1 10-4.5"/></svg>',
            'careers' => '<svg viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/></svg>',
            'contact' => '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
        ];

        $item = static fn (string $href, string $label, string $icon): string => '<a href="'.$href.'"><span class="nav-icon">'.$icon.'</span><span>'.$label.'</span></a>';
        $nav = $item(route('public.home'), 'الرئيسية', $icons['home'])
            .$item(route('public.home').'#about', 'تعرف علينا', $icons['about'])
            .$item(route('public.home').'#services', 'الخدمات', $icons['services'])
            .$item(route('public.home').'#industries', 'القطاعات', $icons['industries'])
            .$item(route('public.home').'#projects', 'المشاريع', $icons['projects'])
            .$item(route('public.home').'#clients', 'العملاء', $icons['clients'])
            .$item(route('public.home').'#careers', 'الوظائف', $icons['careers'])
            .$item(route('public.home').'#contact', 'تواصل معنا', $icons['contact']);

        $header = '<header class="current-service-nav"><div class="nav-wrap">'
            .'<a class="brand-link" href="'.route('public.home').'"><span class="site-logo-frame"><img class="site-logo" src="'.route('brand.logo').'" alt="UNIFCO"></span><span class="brand-copy"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></a>'
            .'<div class="nav-actions"><a class="lang" href="'.route('public.home.en').'">EN</a><a class="request-btn" href="'.route('public.current-maintenance').'">طلب خدمة</a></div>'
            .'<nav class="primary-nav" aria-label="التنقل الرئيسي">'.$nav.'</nav>'
            .'<button class="menu-toggle" type="button" aria-label="القائمة">☰</button>'
            .'</div></header>';

        $html = preg_replace('/<header class="current-service-nav">.*?<\/header>/s', $header, $html, 1) ?? $html;
        $response->setContent($html);

        return $response;
    }
}
