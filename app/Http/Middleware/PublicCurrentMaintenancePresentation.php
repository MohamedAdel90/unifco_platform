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
<style id="unifco-current-maintenance-v2">
.current-service-nav{background:linear-gradient(90deg,#061f4b,#082b63);color:#fff;box-shadow:0 2px 8px rgba(7,31,77,.15)}
.current-service-nav .nav-wrap{width:min(1220px,94%);min-height:74px;margin:auto;display:flex;align-items:center;gap:26px}
.current-service-nav .nav-logo{height:52px;width:auto;margin-left:auto}
.current-service-nav .nav-links{display:flex;align-items:center;gap:27px;flex-wrap:wrap}
.current-service-nav .nav-links a{color:#fff;text-decoration:none;font-size:12px;font-weight:800;opacity:.95}
.current-service-nav .nav-links a:hover{opacity:1;color:#ffd7dc}
.customer-kind{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:13px}
.customer-kind-card{height:46px;border:1px solid #cdd8e6;border-radius:8px;background:#fff;color:#17345e;display:flex;align-items:center;justify-content:center;gap:9px;font-size:11px;font-weight:900}
.customer-kind-card.active{border:1.5px solid #e3132c;color:#e3132c;background:#fff8f9;box-shadow:inset 0 0 0 1px rgba(227,19,44,.05)}
.customer-kind-card.disabled{opacity:.55}
.submit-reference-row{display:flex;align-items:center;justify-content:space-between;gap:24px;margin:18px 0 24px;padding:18px 0 0;border-top:1px solid #eef2f7;direction:ltr}
.submit-reference-row .submit{width:190px;min-width:190px;height:64px;margin:0;border-radius:14px;font-size:16px;box-shadow:none;direction:rtl}
.ticket-promise-inline{margin:0;color:#667b98;font-size:11px;line-height:1.8;text-align:right;font-weight:600;direction:rtl}
.ticket-promise-inline .check{color:#57789e;margin-left:4px}
@media(max-width:850px){.current-service-nav .nav-links{display:none}.current-service-nav .nav-logo{margin-left:0;margin-right:auto}.customer-kind{grid-template-columns:1fr}}
@media(max-width:620px){.submit-reference-row{flex-direction:column-reverse;align-items:stretch;gap:12px;direction:rtl}.submit-reference-row .submit{width:100%;min-width:0;height:54px}.ticket-promise-inline{text-align:center}}
</style>
HTML;
        $html = str_replace('</head>', $styles.'</head>', $html);

        $oldHeader = '<header class="top"><div class="wrap"><a href="'.route('public.home').'"><img class="logo" src="/brand/unifco-logo-v2.webp" alt="UNIFCO"></a><a class="back" href="'.route('public.home').'">العودة للرئيسية ←</a></div></header>';
        $newHeader = '<header class="current-service-nav"><div class="nav-wrap"><a href="'.route('public.home').'"><img class="nav-logo" src="/brand/unifco-logo-v2.webp" alt="UNIFCO"></a><nav class="nav-links" aria-label="التنقل الرئيسي"><a href="'.route('public.home').'">الرئيسية</a><a href="'.route('public.home').'#about">تعرف علينا</a><a href="'.route('public.home').'#services">الخدمات</a><a href="'.route('public.home').'#industries">القطاعات</a><a href="'.route('public.home').'#projects">المشاريع</a><a href="'.route('public.home').'#clients">العملاء</a><a href="'.route('public.home').'#contact">تواصل معنا</a></nav></div></header>';
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
