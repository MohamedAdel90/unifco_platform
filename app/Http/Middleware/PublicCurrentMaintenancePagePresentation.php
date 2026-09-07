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
<style id="unifco-current-maintenance-page-v4">
:root{--unifco-navy:#08295d;--unifco-red:#e3132c}
body{padding-top:0!important}
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
@media(max-width:980px){.request-clarity-hero h1{font-size:28px!important}}
@media(max-width:760px){.request-clarity-hero{min-height:100px}.request-clarity-hero h1{font-size:20px!important}.request-clarity-hero p{font-size:10.5px}.request-clarity-inner,.request-ticket-note,main.wrap{width:94%!important}}
</style>
HTML;

        $hero = '<section class="request-clarity-hero"><div class="request-clarity-inner">'
            .'<h1>خدمة أسرع تبدأ بطلب أوضح</h1>'
            .'<p>خطوات بسيطة تساعد فريق UNIFCO على فهم الخدمة المطلوبة بشكل أسرع، وربط طلب العميل الحالي بعقده وموقعه ومعداته المسجلة.</p>'
            .'</div></section>'
            .'<div class="request-ticket-note">✓ بعد تسجيل الطلب بنجاح، يصدر النظام <strong>رقم تذكرة فريد</strong> للطلب وفق آلية الترقيم المعتمدة، ويُعرض للعميل في صفحة تأكيد الاستلام.</div>';

        if (! str_contains($html, 'request-clarity-hero')) {
            $html = str_replace('<main class="wrap">', $hero.'<main class="wrap">', $html);
        }

        $html = str_replace('</head>', $presentation.'</head>', $html);

        $response->setContent($html);
        return $response;
    }
}
