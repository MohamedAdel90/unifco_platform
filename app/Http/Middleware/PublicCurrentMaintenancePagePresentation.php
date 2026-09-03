<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenancePagePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $presentation = <<<'HTML'
<style id="unifco-current-maintenance-page-v3">
:root{--unifco-navy:#08295d;--unifco-red:#e3132c}
body{padding-top:0!important}
.top{position:fixed!important;inset:0 0 auto 0!important;z-index:100!important;height:76px!important;background:#fff!important;border-bottom:1px solid #e2e8f0!important;box-shadow:0 2px 12px rgba(7,31,77,.06)!important}
.top .wrap{width:min(1280px,94%)!important;height:100%!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:24px!important}
.request-main-nav{display:flex;align-items:center;gap:31px;white-space:nowrap}
.request-main-nav a{position:relative;color:#10294f;text-decoration:none;font-size:12px;font-weight:900;line-height:76px}
.request-main-nav a:first-child:after{content:"";position:absolute;right:0;left:0;bottom:0;height:2px;background:var(--unifco-red)}
.request-brand{display:flex;align-items:center;text-decoration:none}.request-brand img{height:56px;width:auto;display:block}
.request-menu-toggle{display:none;border:1px solid #d7e0eb;background:#fff;color:var(--unifco-navy);border-radius:8px;width:42px;height:42px;font-size:20px;font-weight:900}
.request-mobile-nav{display:none;background:#fff;border-top:1px solid #e7edf4;padding:10px 4%;grid-template-columns:1fr 1fr;gap:5px}.request-mobile-nav a{padding:11px 10px;text-decoration:none;color:#10294f;font-size:11px;font-weight:800;border-radius:7px;background:#f7f9fc;text-align:center}
.page-head{display:none!important}
.request-clarity-hero{position:relative;overflow:hidden;background:linear-gradient(110deg,#061f49 0%,#0a2d60 55%,#1c3c69 100%);min-height:105px;color:#fff;display:flex;align-items:center;border-bottom:4px solid var(--unifco-red)}
.request-clarity-hero:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 22% 25%,rgba(255,255,255,.08),transparent 28%),linear-gradient(90deg,transparent 0 66%,rgba(255,255,255,.035) 66% 67%,transparent 67%)}
.request-clarity-inner{position:relative;width:min(1280px,94%)!important;max-width:1280px!important;margin:auto!important;text-align:right}
.request-clarity-hero h1{margin:0;color:#fff;font-size:28px!important;line-height:1.25;font-weight:900}.request-clarity-hero h1 span{color:#fff}.request-clarity-hero p{margin:5px 0 0;color:#dbe5f1;font-size:11px;line-height:1.7;max-width:760px}
.request-ticket-note{width:min(1280px,94%)!important;max-width:1280px!important;margin:14px auto 0!important;background:#eef5ff;border:1px solid #cfe0f6;border-right:4px solid var(--unifco-navy);border-radius:9px;padding:10px 14px;color:#24456f;font-size:10px;font-weight:800;line-height:1.9}
.request-ticket-note strong{color:var(--unifco-red)}
main.wrap{width:min(1280px,94%)!important;max-width:1280px!important;margin:14px auto 0!important;padding-left:0!important;padding-right:0!important}
main.wrap>.panel,main.wrap>form,main.wrap form>.panel{width:100%!important;max-width:none!important}
@media(min-width:1600px){.request-clarity-inner,.request-ticket-note,main.wrap{width:min(1360px,90%)!important;max-width:1360px!important}}
@media(max-width:980px){.request-main-nav{gap:18px}.request-main-nav a{font-size:10px}.request-brand img{height:50px}.request-clarity-hero h1{font-size:28px!important}}
@media(max-width:760px){.top{height:68px!important}.request-main-nav{display:none}.request-menu-toggle{display:block}.request-mobile-nav.open{display:grid}.request-brand img{height:46px}.request-clarity-hero{min-height:100px}.request-clarity-hero h1{font-size:20px!important}.request-clarity-hero p{font-size:10.5px}.request-clarity-inner,.request-ticket-note,main.wrap{width:94%!important}}
</style>
HTML;

        $nav = '<header class="top"><div class="wrap">'
            .'<nav class="request-main-nav" aria-label="التنقل الرئيسي">'
            .'<a href="'.route('public.home').'#home">الرئيسية</a>'
            .'<a href="'.route('public.home').'#about">تعرف علينا</a>'
            .'<a href="'.route('public.home').'#services">الخدمات</a>'
            .'<a href="'.route('public.home').'#industries">القطاعات</a>'
            .'<a href="'.route('public.home').'#projects">المشاريع</a>'
            .'<a href="'.route('public.home').'#clients">العملاء</a>'
            .'<a href="/careers">الوظائف</a>'
            .'<a href="'.route('public.home').'#contact">تواصل معنا</a>'
            .'</nav>'
            .'<button type="button" class="request-menu-toggle" id="request-menu-toggle" aria-label="فتح القائمة">☰</button>'
            .'<a class="request-brand" href="'.route('public.home').'"><img src="/brand/unifco-logo-v2.webp" alt="UNIFCO"></a>'
            .'</div>'
            .'<nav class="request-mobile-nav" id="request-mobile-nav">'
            .'<a href="'.route('public.home').'#home">الرئيسية</a><a href="'.route('public.home').'#about">تعرف علينا</a><a href="'.route('public.home').'#services">الخدمات</a><a href="'.route('public.home').'#industries">القطاعات</a><a href="'.route('public.home').'#projects">المشاريع</a><a href="'.route('public.home').'#clients">العملاء</a><a href="/careers">الوظائف</a><a href="'.route('public.home').'#contact">تواصل معنا</a>'
            .'</nav></header>';

        $hero = '<section class="request-clarity-hero"><div class="request-clarity-inner">'
            .'<h1>خدمة أسرع تبدأ بطلب أوضح</h1>'
            .'<p>خطوات بسيطة تساعد فريق UNIFCO على فهم الخدمة المطلوبة بشكل أسرع، وربط طلب العميل الحالي بعقده وموقعه ومعداته المسجلة.</p>'
            .'</div></section>'
            .'<div class="request-ticket-note">✓ بعد تسجيل الطلب بنجاح، يصدر النظام <strong>رقم تذكرة فريد</strong> للطلب وفق آلية الترقيم المعتمدة، ويُعرض للعميل في صفحة تأكيد الاستلام.</div>';

        $html = preg_replace('/<header class="top">.*?<\/header>/s', $nav, $html, 1) ?? $html;
        $html = str_replace('<main class="wrap">', $hero.'<main class="wrap">', $html);
        $html = str_replace('</head>', $presentation.'</head>', $html);
        $html = str_replace('</body>', '<script id="request-nav-script">(()=>{const b=document.getElementById("request-menu-toggle"),m=document.getElementById("request-mobile-nav");if(b&&m)b.addEventListener("click",()=>m.classList.toggle("open"));})();</script></body>', $html);

        $response->setContent($html);
        return $response;
    }
}
