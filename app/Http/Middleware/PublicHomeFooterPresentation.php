<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeFooterPresentation
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

        $isArabic = str_contains($html, '<html lang="ar"');
        $benefits = $isArabic
            ? '<div class="final-benefits"><span>⚙ <b>إدارة متكاملة للمرافق</b></span><span>♢ <b>رفع الكفاءة وتقليل التكاليف</b></span><span>↗ <b>استجابة سريعة وموثوقة</b></span><span>👥 <b>فريق مؤهل ومتخصص</b></span></div>'
            : '<div class="final-benefits"><span>⚙ <b>Integrated facility management</b></span><span>♢ <b>Higher efficiency and lower cost</b></span><span>↗ <b>Fast, reliable response</b></span><span>👥 <b>Qualified specialist team</b></span></div>';

        $html = preg_replace(
            '/<section class="final"><div class="wrap final-cta"><div>(.*?)<\/div><div class="final-actions">(.*?)<\/div><\/div><\/section>/s',
            '<section class="final home-final-redesign"><div class="wrap final-cta"><div class="final-copy">$1</div><div class="final-actions">$2</div>'.$benefits.'</div></section>',
            $html,
            1
        ) ?? $html;

        $style = <<<'HTML'
<style id="unifco-home-footer-final">
.home-final-redesign{position:relative;padding:0!important;background:linear-gradient(135deg,#071f4d 0%,#0b2f5d 58%,#123e6b 100%)!important;color:#fff!important;overflow:hidden!important;border-radius:14px 14px 0 0!important}
.home-final-redesign:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 87% 18%,rgba(255,255,255,.08),transparent 32%),linear-gradient(135deg,transparent 0 62%,rgba(255,255,255,.025) 62% 63%,transparent 63% 100%);pointer-events:none}
.home-final-redesign .final-cta{position:relative;z-index:1;display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;grid-template-areas:"copy actions" "benefits benefits"!important;gap:18px 32px!important;align-items:center!important;padding:34px 24px 0!important;background:transparent!important;border-radius:0!important;box-shadow:none!important;color:#fff!important;direction:rtl!important}
.home-final-redesign .final-copy{grid-area:copy!important;text-align:right!important;max-width:620px!important;justify-self:start!important}
.home-final-redesign .final-copy h2{margin:0 0 6px!important;color:#fff!important;font-size:clamp(26px,2.6vw,40px)!important;line-height:1.2!important;font-weight:900!important;letter-spacing:-.02em!important}
.home-final-redesign .final-copy p{margin:0!important;color:#d7e2f0!important;font-size:12px!important;line-height:1.7!important;max-width:560px!important}
.home-final-redesign .final-actions{grid-area:actions!important;display:flex!important;gap:10px!important;align-items:center!important;justify-content:flex-start!important;direction:rtl!important;margin:0!important}
.home-final-redesign .final-actions .btn{min-width:150px!important;min-height:44px!important;padding:9px 16px!important;border-radius:8px!important;font-size:11px!important;font-weight:900!important;box-shadow:none!important}
.home-final-redesign .final-actions .btn.red{background:#df1731!important;border-color:#df1731!important}
.home-final-redesign .final-actions .btn.outline{background:rgba(7,31,77,.2)!important;border:1px solid rgba(255,255,255,.72)!important;color:#fff!important}
.home-final-redesign .final-benefits{grid-area:benefits!important;display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;border-top:1px solid rgba(255,255,255,.18)!important;margin-top:6px!important;direction:rtl!important}
.home-final-redesign .final-benefits span{min-height:62px!important;display:flex!important;align-items:center!important;justify-content:center!important;gap:8px!important;padding:12px 14px!important;color:#fff!important;font-size:11px!important;text-align:center!important;border-inline-start:1px solid rgba(255,255,255,.16)!important}
.home-final-redesign .final-benefits span:first-child{border-inline-start:0!important}
.home-final-redesign .final-benefits b{font-weight:800!important}

.footer{position:relative!important;background:linear-gradient(180deg,#06214a 0%,#041a3c 100%)!important;color:#fff!important;padding:0!important;border:0!important}
.footer .footer-grid{display:grid!important;grid-template-columns:1.15fr .85fr .9fr .95fr!important;gap:0!important;padding:28px 24px 18px!important;direction:ltr!important;align-items:start!important}
.footer .footer-grid>div{min-height:185px!important;padding:0 24px!important;direction:rtl!important;text-align:right!important;border-inline-start:1px solid rgba(255,255,255,.18)!important}
.footer .footer-grid>div:first-child{border-inline-start:0!important;padding-left:0!important;direction:ltr!important;text-align:left!important}
.footer .footer-brand{display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:10px!important;margin-bottom:12px!important;direction:ltr!important}
.footer .footer-brand .site-logo-frame{width:54px!important;height:58px!important;flex:0 0 54px!important;border-radius:8px!important;background:#fff!important;display:grid!important;place-items:center!important;overflow:hidden!important;padding:5px!important}
.footer .footer-brand .site-logo{width:100%!important;height:100%!important;object-fit:contain!important}
.footer .footer-brand strong{display:block!important;color:#fff!important;font-family:Inter,Arial,sans-serif!important;font-size:20px!important;letter-spacing:.04em!important;line-height:1!important}
.footer .footer-brand small{display:block!important;color:#ef263e!important;font-family:Inter,Arial,sans-serif!important;font-size:6px!important;font-weight:900!important;letter-spacing:.15em!important;margin-top:5px!important}
.footer .footer-grid>div:first-child>p{max-width:330px!important;color:#bfcddd!important;font-size:11px!important;line-height:1.8!important;margin:0 0 14px!important;direction:rtl!important;text-align:right!important}
.footer h4{position:relative!important;color:#fff!important;font-size:15px!important;font-weight:900!important;margin:0 0 14px!important;padding-bottom:9px!important}
.footer h4:after{content:"";position:absolute!important;right:0!important;bottom:0!important;width:36px!important;height:2px!important;border-radius:99px!important;background:#ce122d!important}
.footer .footer-grid>div p{color:#c8d3e1!important;font-size:11px!important;line-height:1.8!important;margin:0!important}
.footer .footer-grid a{color:#d5dfeb!important;font-size:11px!important;line-height:1.8!important;transition:.18s!important}
.footer .footer-grid a:hover{color:#fff!important}
.footer .socials{display:flex!important;gap:9px!important;justify-content:flex-start!important;direction:ltr!important}
.footer .socials span,.footer .socials a{width:38px!important;height:38px!important;border-radius:50%!important;border:1px solid rgba(255,255,255,.32)!important;display:grid!important;place-items:center!important;color:#fff!important;background:rgba(255,255,255,.025)!important;font-size:12px!important}
.footer .footer-contact{display:grid!important;gap:8px!important;margin-bottom:10px!important}
.footer .footer-contact span{display:block!important;color:#d3deea!important;font-size:11px!important;line-height:1.8!important}
.footer .copyright{width:min(1280px,94%)!important;margin:0 auto!important;padding:14px 16px 18px!important;border-top:1px solid rgba(255,255,255,.14)!important;color:#94a8c1!important;text-align:center!important;font-size:9px!important}

@media(max-width:1050px){
 .home-final-redesign .final-cta{grid-template-columns:1fr!important;grid-template-areas:"copy" "actions" "benefits"!important;padding:30px 22px 0!important;gap:16px!important}
 .home-final-redesign .final-copy{justify-self:center!important;text-align:center!important}.home-final-redesign .final-copy p{margin-inline:auto!important}.home-final-redesign .final-actions{justify-content:center!important}
 .footer .footer-grid{grid-template-columns:1fr 1fr!important;row-gap:26px!important}.footer .footer-grid>div{min-height:0!important;border-inline-start:0!important;padding:0 22px!important}.footer .footer-grid>div:nth-child(odd){border-inline-end:1px solid rgba(255,255,255,.16)!important}
}
@media(max-width:700px){
 .home-final-redesign{border-radius:10px 10px 0 0!important}.home-final-redesign .final-copy h2{font-size:28px!important}.home-final-redesign .final-copy p{font-size:11px!important}.home-final-redesign .final-actions{display:grid!important;grid-template-columns:1fr 1fr!important;width:100%!important}.home-final-redesign .final-actions .btn{min-width:0!important;width:100%!important;min-height:46px!important}.home-final-redesign .final-benefits{grid-template-columns:1fr 1fr!important}.home-final-redesign .final-benefits span{min-height:60px!important;border-bottom:1px solid rgba(255,255,255,.12)!important;font-size:10px!important}
 .footer .footer-grid{grid-template-columns:1fr!important;padding:26px 20px 18px!important}.footer .footer-grid>div,.footer .footer-grid>div:nth-child(odd){padding:18px 0!important;border:0!important;border-bottom:1px solid rgba(255,255,255,.12)!important;text-align:center!important;direction:rtl!important}.footer .footer-grid>div:first-child{direction:ltr!important;text-align:center!important}.footer .footer-brand,.footer .socials{justify-content:center!important}.footer .footer-grid>div:first-child>p{margin-inline:auto!important;text-align:center!important}.footer h4:after{right:50%!important;transform:translateX(50%)!important}
}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));
        return $response;
    }
}
