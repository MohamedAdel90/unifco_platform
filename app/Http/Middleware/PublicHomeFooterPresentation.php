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
        $quoteUrl = route('public.quote');
        $requestUrl = route('public.request-service');
        $loginUrl = route('login');
        $logoUrl = route('brand.logo');
        $linkedinUrl = 'https://www.linkedin.com/company/unifco-fm/about/?viewAsMember=true';

        $benefits = $isArabic
            ? '<div class="final-benefits">'
                .'<span>'.$this->icon('gear').'<b>إدارة متكاملة للمرافق</b></span>'
                .'<span>'.$this->icon('shield').'<b>رفع الكفاءة وتقليل التكاليف</b></span>'
                .'<span>'.$this->icon('chart').'<b>استجابة سريعة وموثوقة</b></span>'
                .'<span>'.$this->icon('team').'<b>فرق مدربة ومعتمدة</b></span>'
              .'</div>'
            : '<div class="final-benefits">'
                .'<span>'.$this->icon('gear').'<b>Integrated facility management</b></span>'
                .'<span>'.$this->icon('shield').'<b>Higher efficiency and lower cost</b></span>'
                .'<span>'.$this->icon('chart').'<b>Fast and reliable response</b></span>'
                .'<span>'.$this->icon('team').'<b>Trained and certified teams</b></span>'
              .'</div>';

        $html = preg_replace(
            '/<section class="final(?: home-final-redesign)?"><div class="wrap final-cta"><div(?: class="final-copy")?>(.*?)<\/div><div class="final-actions">(.*?)<\/div>(?:<div class="final-benefits">.*?<\/div>)?<\/div><\/section>/s',
            '<section class="final home-final-redesign"><div class="wrap final-cta"><div class="final-copy">$1</div><div class="final-actions">$2</div>'.$benefits.'</div></section>',
            $html,
            1
        ) ?? $html;

        $footer = $isArabic
            ? '<footer class="footer footer-reference" id="contact"><div class="wrap footer-grid">'
                .'<div class="footer-about-col"><div class="footer-brand"><span class="site-logo-frame"><img class="site-logo" src="'.$logoUrl.'" alt="UNIFCO"></span><span class="footer-wordmark"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></div><p>حلول الأنظمة الكهربائية والميكانيكية وإدارة المرافق<br>لضمان بيئة خدمة واحدة متكاملة وفعالة.</p><div class="socials"><a class="social-linkedin" href="'.$linkedinUrl.'" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">in</a><span aria-label="X">𝕏</span><span aria-label="Facebook">f</span><span aria-label="YouTube">▶</span></div></div>'
                .'<div class="footer-col"><h4>الشركة</h4><nav class="footer-links"><a href="#about">من نحن</a><a href="#projects">مشاريعنا</a><a href="#careers">وظائف</a><a href="#contact">تواصل معنا</a></nav></div>'
                .'<div class="footer-col"><h4>خدماتنا</h4><nav class="footer-links"><a href="#services">إدارة المرافق</a><a href="#services">الصيانة والتشغيل</a><a href="#services">خدمات MEP</a><a href="#services">أنظمة HVAC</a><a href="#services">الخدمات الكهربائية</a><a href="#services">إدارة الأصول</a></nav></div>'
                .'<div class="footer-col footer-contact-col"><h4>تواصل معنا</h4><div class="footer-contact-list">'
                  .'<a href="tel:+966123456789">'.$this->icon('phone').'<span dir="ltr">+966 12 345 6789</span></a>'
                  .'<a href="https://wa.me/966501234567" target="_blank" rel="noopener noreferrer">'.$this->icon('whatsapp').'<span dir="ltr">+966 50 123 4567</span></a>'
                  .'<a href="mailto:info@unifco.com.sa">'.$this->icon('mail').'<span dir="ltr">info@unifco.com.sa</span></a>'
                  .'<span>'.$this->icon('pin').'<b>جدة، المملكة العربية السعودية</b></span>'
                .'</div></div>'
              .'</div><div class="wrap copyright">جميع الحقوق محفوظة. © '.date('Y').' UNIFCO — ONE FACILITY SHOP</div></footer>'
            : '<footer class="footer footer-reference" id="contact"><div class="wrap footer-grid">'
                .'<div class="footer-about-col"><div class="footer-brand"><span class="site-logo-frame"><img class="site-logo" src="'.$logoUrl.'" alt="UNIFCO"></span><span class="footer-wordmark"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></div><p>Electrical, mechanical and facility management solutions<br>for one integrated and efficient service environment.</p><div class="socials"><a class="social-linkedin" href="'.$linkedinUrl.'" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">in</a><span aria-label="X">𝕏</span><span aria-label="Facebook">f</span><span aria-label="YouTube">▶</span></div></div>'
                .'<div class="footer-col"><h4>Company</h4><nav class="footer-links"><a href="#about">About us</a><a href="#projects">Our projects</a><a href="#careers">Careers</a><a href="#contact">Contact us</a></nav></div>'
                .'<div class="footer-col"><h4>Our Services</h4><nav class="footer-links"><a href="#services">Facility Management</a><a href="#services">Maintenance & Operations</a><a href="#services">MEP Services</a><a href="#services">HVAC Systems</a><a href="#services">Electrical Services</a><a href="#services">Asset Management</a></nav></div>'
                .'<div class="footer-col footer-contact-col"><h4>Contact us</h4><div class="footer-contact-list">'
                  .'<a href="tel:+966123456789">'.$this->icon('phone').'<span>+966 12 345 6789</span></a>'
                  .'<a href="https://wa.me/966501234567" target="_blank" rel="noopener noreferrer">'.$this->icon('whatsapp').'<span>+966 50 123 4567</span></a>'
                  .'<a href="mailto:info@unifco.com.sa">'.$this->icon('mail').'<span>info@unifco.com.sa</span></a>'
                  .'<span>'.$this->icon('pin').'<b>Jeddah, Saudi Arabia</b></span>'
                .'</div></div>'
              .'</div><div class="wrap copyright">All rights reserved. © '.date('Y').' UNIFCO — ONE FACILITY SHOP</div></footer>';

        $html = preg_replace('/<footer class="footer" id="contact">.*?<\/footer>/s', $footer, $html, 1) ?? $html;

        $style = <<<'HTML'
<style id="unifco-home-footer-final">
.home-final-redesign{position:relative;padding:0!important;background:linear-gradient(135deg,#071f4d 0%,#0b2f5d 58%,#123e6b 100%)!important;color:#fff!important;overflow:hidden!important;border-radius:14px 14px 0 0!important}
.home-final-redesign:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 87% 18%,rgba(255,255,255,.08),transparent 32%),linear-gradient(135deg,transparent 0 62%,rgba(255,255,255,.025) 62% 63%,transparent 63% 100%);pointer-events:none}
.home-final-redesign .final-cta{position:relative;z-index:1;display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;grid-template-areas:"copy actions" "benefits benefits"!important;gap:18px 32px!important;align-items:center!important;padding:34px 24px 0!important;background:transparent!important;border-radius:0!important;box-shadow:none!important;color:#fff!important;direction:rtl!important}
.home-final-redesign .final-copy{grid-area:copy!important;text-align:right!important;max-width:680px!important;justify-self:start!important}
.home-final-redesign .final-copy h2{margin:0 0 6px!important;color:#fff!important;font-size:clamp(28px,2.8vw,42px)!important;line-height:1.2!important;font-weight:900!important;letter-spacing:-.02em!important}
.home-final-redesign .final-copy p{margin:0!important;color:#d7e2f0!important;font-size:12px!important;line-height:1.7!important;max-width:590px!important}
.home-final-redesign .final-actions{grid-area:actions!important;display:flex!important;gap:10px!important;align-items:center!important;justify-content:flex-start!important;direction:rtl!important;margin:0!important}
.home-final-redesign .final-actions .btn{min-width:155px!important;min-height:44px!important;padding:9px 16px!important;border-radius:8px!important;font-size:11px!important;font-weight:900!important;box-shadow:none!important}
.home-final-redesign .final-actions .btn.red{background:#df1731!important;border-color:#df1731!important}
.home-final-redesign .final-actions .btn.outline{background:rgba(7,31,77,.2)!important;border:1px solid rgba(255,255,255,.72)!important;color:#fff!important}
.home-final-redesign .final-benefits{grid-area:benefits!important;display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;border-top:1px solid rgba(255,255,255,.18)!important;margin-top:8px!important;direction:rtl!important}
.home-final-redesign .final-benefits span{min-height:66px!important;display:flex!important;align-items:center!important;justify-content:center!important;gap:10px!important;padding:13px 16px!important;color:#fff!important;font-size:11px!important;text-align:center!important;border-inline-start:1px solid rgba(255,255,255,.16)!important}
.home-final-redesign .final-benefits span:first-child{border-inline-start:0!important}
.home-final-redesign .final-benefits b{font-weight:800!important}.home-final-redesign .final-benefits svg{width:23px;height:23px;flex:0 0 23px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}

.footer.footer-reference{position:relative!important;background:linear-gradient(180deg,#061f46 0%,#041936 100%)!important;color:#fff!important;padding:0!important;border:0!important}
.footer-reference .footer-grid{display:grid!important;grid-template-columns:1.35fr .68fr .88fr 1.05fr!important;gap:0!important;padding:32px 24px 24px!important;direction:ltr!important;align-items:start!important}
.footer-reference .footer-grid>div{min-height:215px!important;padding:0 34px!important;direction:rtl!important;text-align:right!important;border-inline-start:1px solid rgba(255,255,255,.18)!important}
.footer-reference .footer-grid>div:first-child{border-inline-start:0!important;padding-left:0!important;direction:ltr!important;text-align:left!important}
.footer-reference .footer-brand{display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:12px!important;margin-bottom:15px!important;direction:ltr!important}
.footer-reference .footer-brand .site-logo-frame{width:60px!important;height:64px!important;flex:0 0 60px!important;border-radius:8px!important;background:#fff!important;display:grid!important;place-items:center!important;overflow:hidden!important;padding:5px!important}
.footer-reference .footer-brand .site-logo{width:100%!important;height:100%!important;object-fit:contain!important}
.footer-reference .footer-wordmark strong{display:block!important;color:#fff!important;font-family:Inter,Arial,sans-serif!important;font-size:24px!important;letter-spacing:.04em!important;line-height:1!important}
.footer-reference .footer-wordmark small{display:block!important;color:#ef263e!important;font-family:Inter,Arial,sans-serif!important;font-size:6px!important;font-weight:900!important;letter-spacing:.15em!important;margin-top:6px!important}
.footer-reference .footer-about-col>p{max-width:390px!important;color:#bfcddd!important;font-size:11px!important;line-height:1.95!important;margin:0 0 16px!important;direction:rtl!important;text-align:right!important}
.footer-reference h4{position:relative!important;color:#fff!important;font-size:16px!important;font-weight:900!important;margin:0 0 18px!important;padding-bottom:11px!important}
.footer-reference h4:after{content:"";position:absolute!important;right:0!important;bottom:0!important;width:38px!important;height:2px!important;border-radius:99px!important;background:#ce122d!important}
.footer-reference .footer-links{display:grid!important;gap:8px!important}.footer-reference .footer-links a{color:#d2ddea!important;font-size:11px!important;line-height:1.65!important;transition:.18s!important}.footer-reference .footer-links a:hover{color:#fff!important;transform:translateX(-2px)}
.footer-reference .socials{display:flex!important;gap:10px!important;justify-content:flex-start!important;direction:ltr!important}
.footer-reference .socials span,.footer-reference .socials a{width:40px!important;height:40px!important;border-radius:50%!important;border:1px solid rgba(255,255,255,.34)!important;display:grid!important;place-items:center!important;color:#fff!important;background:rgba(255,255,255,.025)!important;font-size:12px!important;font-weight:800!important;transition:.18s!important}.footer-reference .socials a:hover,.footer-reference .socials span:hover{background:rgba(255,255,255,.08)!important;transform:translateY(-2px)!important}
.footer-reference .footer-contact-list{display:grid!important;gap:10px!important}.footer-reference .footer-contact-list>a,.footer-reference .footer-contact-list>span{display:flex!important;align-items:center!important;gap:11px!important;color:#d5dfeb!important;font-size:11px!important;line-height:1.55!important}.footer-reference .footer-contact-list svg{width:22px;height:22px;flex:0 0 22px;fill:none;stroke:#fff;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.footer-reference .footer-contact-list b{font-weight:500!important}.footer-reference .footer-contact-list a:hover{color:#fff!important}
.footer-reference .copyright{width:min(1280px,94%)!important;margin:0 auto!important;padding:16px 16px 20px!important;border-top:1px solid rgba(255,255,255,.14)!important;color:#9db0c7!important;text-align:center!important;font-size:9px!important;direction:rtl!important}
@media(max-width:1050px){.home-final-redesign .final-cta{grid-template-columns:1fr!important;grid-template-areas:"copy" "actions" "benefits"!important;padding:30px 22px 0!important;gap:16px!important}.home-final-redesign .final-copy{justify-self:center!important;text-align:center!important}.home-final-redesign .final-copy p{margin-inline:auto!important}.home-final-redesign .final-actions{justify-content:center!important}.footer-reference .footer-grid{grid-template-columns:1fr 1fr!important;row-gap:26px!important}.footer-reference .footer-grid>div{min-height:0!important;border-inline-start:0!important;padding:0 24px!important}.footer-reference .footer-grid>div:nth-child(odd){border-inline-end:1px solid rgba(255,255,255,.16)!important}}
@media(max-width:700px){.home-final-redesign{border-radius:10px 10px 0 0!important}.home-final-redesign .final-copy h2{font-size:28px!important}.home-final-redesign .final-copy p{font-size:11px!important}.home-final-redesign .final-actions{display:grid!important;grid-template-columns:1fr 1fr!important;width:100%!important}.home-final-redesign .final-actions .btn{min-width:0!important;width:100%!important;min-height:46px!important}.home-final-redesign .final-benefits{grid-template-columns:1fr 1fr!important}.home-final-redesign .final-benefits span{min-height:60px!important;border-bottom:1px solid rgba(255,255,255,.12)!important;font-size:10px!important}.footer-reference .footer-grid{grid-template-columns:1fr!important;padding:26px 20px 18px!important}.footer-reference .footer-grid>div,.footer-reference .footer-grid>div:nth-child(odd){padding:18px 0!important;border:0!important;border-bottom:1px solid rgba(255,255,255,.12)!important;text-align:center!important;direction:rtl!important}.footer-reference .footer-grid>div:first-child{direction:ltr!important;text-align:center!important}.footer-reference .footer-brand,.footer-reference .socials{justify-content:center!important}.footer-reference .footer-about-col>p{margin-inline:auto!important;text-align:center!important}.footer-reference h4:after{right:50%!important;transform:translateX(50%)!important}.footer-reference .footer-contact-list>a,.footer-reference .footer-contact-list>span{justify-content:center!important}}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));
        return $response;
    }

    private function icon(string $name): string
    {
        return match ($name) {
            'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.28-1.28a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.9z"/></svg>',
            'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.5 8.5 0 0 1-12.55 7.47L3 20.5l1.53-5.3A8.5 8.5 0 1 1 21 11.5z"/><path d="M8.2 8.1c.2-.5.4-.5.7-.5h.5c.2 0 .4.1.5.4l.8 1.8c.1.3.1.5-.1.7l-.6.8c-.2.2-.1.4 0 .6.7 1.3 1.7 2.3 3 3 .2.1.4.2.6 0l.9-1c.2-.2.4-.3.7-.2l1.8.8c.3.1.4.3.4.5 0 .4-.2 1.5-1 2-.7.5-1.6.7-2.7.4-1.4-.4-3.1-1.3-4.7-2.8-1.3-1.2-2.2-2.7-2.6-4-.4-1.1-.1-2 .4-2.5.4-.4.8-.5 1.4-.5z"/></svg>',
            'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
            'pin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="2.5"/></svg>',
            'gear' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V21h-4v-.09A1.7 1.7 0 0 0 8.97 19.35a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.03H3v-4h.09A1.7 1.7 0 0 0 4.65 8.94a1.7 1.7 0 0 0-.34-1.88L4.25 7l2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.57h.02A1.7 1.7 0 0 0 10.05 3H10V3h4v.09A1.7 1.7 0 0 0 15.03 4.65a1.7 1.7 0 0 0 1.88-.34l.06-.06L19.8 7l-.06.06a1.7 1.7 0 0 0-.34 1.88v.02A1.7 1.7 0 0 0 21 10v4h-.09A1.7 1.7 0 0 0 19.4 15z"/></svg>',
            'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>',
            'chart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10h4v10M10 20V4h4v16M16 20v-7h4v7"/><path d="m5 7 4-3 4 3 6-5"/></svg>',
            'team' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M14 15a5 5 0 0 1 7 4.6V20"/></svg>',
            default => '',
        };
    }
}
