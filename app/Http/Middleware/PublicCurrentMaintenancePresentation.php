<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenancePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $styles = <<<'HTML'
<style id="unifco-current-maintenance-v4">
:root{--unifco-navy:#08295d;--unifco-red:#e3132c;--ufm-text:#0b2854;--ufm-muted:#6f819c}
.current-service-nav{position:sticky;top:0;z-index:60;background:rgba(255,255,255,.97);border-bottom:1px solid #e7ebf0;backdrop-filter:blur(14px)}
.current-service-nav .nav-wrap{width:min(1280px,94%);min-height:76px;margin-inline:auto;display:flex;align-items:center;gap:20px;direction:ltr}
.current-service-nav .brand-link{display:flex;align-items:center;gap:9px;margin-right:auto;direction:ltr;text-decoration:none}
.current-service-nav .site-logo-frame{width:42px;height:50px;overflow:hidden;flex:0 0 42px}
.current-service-nav .site-logo{width:42px;height:65px;object-fit:cover;object-position:center top;display:block}
.current-service-nav .brand-copy{display:flex;flex-direction:column;line-height:.88}
.current-service-nav .brand-copy strong{font-family:Inter,Arial,sans-serif;font-size:24px;letter-spacing:.03em;color:#071f4d;font-weight:800}
.current-service-nav .brand-copy small{margin-top:5px;font-family:Inter,Arial,sans-serif;font-size:7px;font-weight:900;letter-spacing:.16em;color:#ce122d}
.current-service-nav .primary-nav{display:flex;align-items:center;gap:23px;direction:rtl}
.current-service-nav .primary-nav a{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;color:#25354d;text-decoration:none;font-size:13px;line-height:1.25;font-weight:800;white-space:nowrap;min-width:68px}
.current-service-nav .primary-nav a:first-child:after,.current-service-nav .primary-nav a:hover:after{content:"";position:absolute;inset-inline:5px;bottom:-13px;height:2px;background:#ce122d}
.current-service-nav .nav-icon{display:grid;place-items:center;height:23px;color:#203759}
.current-service-nav .nav-icon svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round}
.current-service-nav .nav-actions{display:flex;align-items:center;gap:9px;direction:ltr}
.current-service-nav .lang{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 13px;border:1px solid #ccd4df;border-radius:6px;font-size:11px;font-weight:900;color:#071f4d;background:#fff;text-decoration:none}
.current-service-nav .request-btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 18px;border-radius:6px;background:#ce122d;color:#fff;text-decoration:none;font-size:12px;font-weight:900}
.current-service-nav .menu-toggle{display:none;border:0;background:#071f4d;color:#fff;width:42px;height:42px;border-radius:7px;font-size:20px;cursor:pointer}

/* Professional typography scale for the entire service request */
body{color:var(--ufm-text)}
.request-clarity-eyebrow{font-size:11px!important;line-height:1.4!important;font-weight:800!important;letter-spacing:.09em!important}
.request-clarity-hero h1{font-size:36px!important;line-height:1.35!important;font-weight:900!important;letter-spacing:-.015em!important}
.request-clarity-hero p{font-size:13px!important;line-height:1.9!important;font-weight:500!important}
.request-ticket-note{font-size:12px!important;line-height:1.75!important;font-weight:650!important}
main.wrap{font-size:14px!important}
.panel{color:var(--ufm-text)}
.panel .section-title,.section-title{font-size:18px!important;line-height:1.45!important;font-weight:900!important;letter-spacing:-.01em!important}
.section-title .num,.panel .num{font-size:11px!important;line-height:26px!important;font-weight:900!important}
.panel label,.field label,label{font-size:12px!important;line-height:1.45!important;font-weight:800!important;color:#102b55!important}
.panel input,.panel select,.panel textarea,.field input,.field select,.field textarea,input,select,textarea{font-size:13px!important;line-height:1.5!important;font-weight:600!important;color:#18345e!important}
.panel input::placeholder,.panel textarea::placeholder,input::placeholder,textarea::placeholder{font-size:12px!important;font-weight:500!important;color:#95a3b6!important}
.hint,.helper,.help-text,.muted,.note{font-size:11px!important;line-height:1.7!important;font-weight:500!important;color:var(--ufm-muted)!important}
.customer-kind{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:13px}
.customer-kind-card{height:46px;border:1px solid #cdd8e6;border-radius:8px;background:#fff;color:#17345e;display:flex;align-items:center;justify-content:center;gap:9px;font-size:13px;font-weight:850;line-height:1.2}
.customer-kind-card.active{border:1.5px solid #e3132c;color:#e3132c;background:#fff8f9;box-shadow:inset 0 0 0 1px rgba(227,19,44,.05)}
.customer-kind-card.disabled{opacity:.55}
button,.button,.btn{font-weight:800}
.submit-reference-row{display:flex;align-items:center;justify-content:space-between;gap:24px;margin:18px 0 24px;padding:18px 0 0;border-top:1px solid #eef2f7;direction:ltr}
.submit-reference-row .submit{width:190px;min-width:190px;height:64px;margin:0;border-radius:14px;font-size:16px!important;line-height:1.2!important;font-weight:900!important;box-shadow:none;direction:rtl}
.ticket-promise-inline{margin:0;color:#667b98;font-size:12px!important;line-height:1.75!important;text-align:right;font-weight:600;direction:rtl}
.ticket-promise-inline .check{color:#57789e;margin-left:4px}

@media(max-width:1080px){.current-service-nav .primary-nav{display:none}.current-service-nav .menu-toggle{display:grid;place-items:center}.request-clarity-hero h1{font-size:32px!important}}
@media(max-width:850px){.current-service-nav .request-btn{display:none}.customer-kind{grid-template-columns:1fr}.panel .section-title,.section-title{font-size:17px!important}}
@media(max-width:700px){.current-service-nav .nav-wrap{min-height:70px}.current-service-nav .site-logo-frame{width:38px;height:46px;flex-basis:38px}.current-service-nav .site-logo{width:38px;height:59px}.current-service-nav .brand-copy strong{font-size:21px}.current-service-nav .lang{display:none}.request-clarity-hero h1{font-size:27px!important}.request-clarity-hero p{font-size:12px!important}.request-ticket-note{font-size:11px!important}.panel label,.field label,label{font-size:11.5px!important}.panel input,.panel select,.panel textarea,.field input,.field select,.field textarea,input,select,textarea{font-size:12.5px!important}}
@media(max-width:620px){.submit-reference-row{flex-direction:column-reverse;align-items:stretch;gap:12px;direction:rtl}.submit-reference-row .submit{width:100%;min-width:0;height:54px}.ticket-promise-inline{text-align:center}.customer-kind-card{font-size:12px}}
</style>
HTML;
        $html = str_replace('</head>', $styles.'</head>', $html);

        $oldHeader = '<header class="top"><div class="wrap"><a href="'.route('public.home').'"><img class="logo" src="/brand/unifco-logo-v2.webp" alt="UNIFCO"></a><a class="back" href="'.route('public.home').'">العودة للرئيسية ←</a></div></header>';

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

        $newHeader = '<header class="current-service-nav"><div class="nav-wrap">'
            .'<a class="brand-link" href="'.route('public.home').'"><span class="site-logo-frame"><img class="site-logo" src="'.route('brand.logo').'" alt="UNIFCO"></span><span class="brand-copy"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></a>'
            .'<nav class="primary-nav" aria-label="التنقل الرئيسي">'.$nav.'</nav>'
            .'<div class="nav-actions"><a class="lang" href="'.route('public.home.en').'">EN</a><a class="request-btn" href="'.route('public.current-maintenance').'">طلب خدمة</a></div>'
            .'<button class="menu-toggle" type="button" aria-label="القائمة">☰</button>'
            .'</div></header>';

        if (str_contains($html, $oldHeader)) {
            $html = str_replace($oldHeader, $newHeader, $html);
        }

        $customerPanel = '<section class="panel"><h2 class="section-title"><span class="num">1</span> بيانات العميل الحالي</h2>';
        $customerKind = '<section class="panel"><div class="section-title" style="margin-bottom:10px">نوع العميل</div><div class="customer-kind"><div class="customer-kind-card active">✓ عميل حالي</div><div class="customer-kind-card disabled">＋ عميل جديد</div></div><div class="hint">هذا المسار مخصص حاليًا لعملاء UNIFCO المسجلين. سيتم توفير نموذج العميل الجديد ضمن نفس رحلة الطلب لاحقًا.</div></section>';
        if (str_contains($html, $customerPanel)) {
            $html = str_replace($customerPanel, $customerKind.$customerPanel, $html);
        }

        $submit = '<button class="submit" type="submit">إرسال طلب الخدمة</button>';
        $replacement = '<div class="submit-reference-row"><button class="submit" type="submit">إرسال الطلب</button><p class="ticket-promise-inline"><span class="check">✓</span> سيتم إصدار رقم تذكرة مرجعي فور إرسال الطلب.</p></div>';
        if (str_contains($html, $submit)) {
            $html = str_replace($submit, $replacement, $html);
        }

        $response->setContent($html);
        return $response;
    }
}
