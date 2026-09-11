<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentCustomerSummaryCardPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="customer-status"') || ! str_contains($html, '</body>')) {
            return $response;
        }

        $card = <<<'HTML'
<div class="uf-customer-profile-card is-empty" id="uf-current-customer-card" aria-live="polite">
    <div class="uf-customer-profile-head">
        <div class="uf-customer-company">
            <span class="uf-company-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 21h16M6 21V7l6-4 6 4v14M9 10h2M13 10h2M9 14h2M13 14h2M10 21v-4h4v4"/></svg></span>
            <div class="uf-company-copy">
                <div class="uf-company-name" id="uf-current-customer-name"></div>
                <span class="uf-company-code" id="uf-current-customer-code"></span>
            </div>
        </div>
        <div class="uf-customer-verified">
            <span class="uf-verified-check">✓</span>
            <div><b>تم التحقق من العميل</b><small>تم جلب بيانات العميل بنجاح</small></div>
        </div>
    </div>

    <div class="uf-customer-profile-body">
        <div class="uf-customer-info-item" id="uf-current-customer-contact-wrap">
            <span class="uf-info-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 20v-1.5A6.5 6.5 0 0 1 12 12a6.5 6.5 0 0 1 7 6.5V20"/></svg></span>
            <div><small>مسؤول التواصل</small><b id="uf-current-customer-contact"></b></div>
        </div>
        <div class="uf-customer-info-item" id="uf-current-customer-mobile-wrap">
            <span class="uf-info-icon"><svg viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.28-1.28a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.9Z"/></svg></span>
            <div><small>رقم الجوال</small><b id="uf-current-customer-mobile"></b></div>
        </div>
        <div class="uf-customer-info-item" id="uf-current-customer-email-wrap">
            <span class="uf-info-icon email"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 7l8 6 8-6"/></svg></span>
            <div><small>البريد الإلكتروني</small><b id="uf-current-customer-email"></b></div>
        </div>
        <div class="uf-customer-info-item" id="uf-current-customer-city-wrap">
            <span class="uf-info-icon location"><svg viewBox="0 0 24 24"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Z"/><circle cx="12" cy="10" r="2"/></svg></span>
            <div><small>المدينة</small><b id="uf-current-customer-city"></b></div>
        </div>
        <div class="uf-customer-info-item uf-address-item" id="uf-current-customer-address-wrap">
            <span class="uf-info-icon address"><svg viewBox="0 0 24 24"><path d="M5 21V6l7-3 7 3v15M9 9h2M13 9h2M9 13h2M13 13h2"/></svg></span>
            <div><small>العنوان المختصر</small><b id="uf-current-customer-address"></b></div>
        </div>
    </div>

    <div class="uf-customer-profile-foot">
        <button type="button" class="uf-change-customer" id="uf-change-customer"><span aria-hidden="true">↻</span> تغيير العميل</button>
    </div>
</div>
HTML;

        $html = str_replace('<div class="status" id="customer-status"></div>', '<div class="status" id="customer-status"></div>'.$card, $html);

        $style = <<<'HTML'
<style id="unifco-current-customer-card-style-v4">
#customer-status.ok{display:none!important}
.uf-legacy-customer-field-hidden{display:none!important}
.uf-customer-profile-card{margin-top:10px;border:1px solid #c9e7dd;border-radius:10px;background:linear-gradient(90deg,#f8fffc 0%,#f4fcf9 100%);direction:rtl;overflow:hidden;font-family:inherit;color:#0b2f63;box-shadow:0 2px 7px rgba(16,99,76,.025)}
.uf-customer-profile-card[hidden]{display:none!important}
.uf-customer-profile-card.is-empty .uf-customer-profile-head,.uf-customer-profile-card.is-empty .uf-customer-profile-foot{display:none!important}
.uf-customer-profile-card.is-empty .uf-customer-profile-body{padding:8px 10px!important}
.uf-customer-profile-card.is-empty .uf-customer-info-item{min-height:43px!important;padding-top:4px!important;padding-bottom:4px!important}
.uf-customer-profile-card.is-empty .uf-customer-info-item b{min-height:15px!important}
.uf-customer-profile-head{min-height:70px;padding:11px 14px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:1px solid #deece8}
.uf-customer-company{display:flex;align-items:center;gap:10px;min-width:0}
.uf-company-icon{width:40px;height:40px;border-radius:8px;background:#e2efff;color:#0b4387;display:grid;place-items:center;flex:0 0 40px}
.uf-company-icon svg{width:23px;height:23px;fill:none;stroke:currentColor;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round}
.uf-company-copy{min-width:0;text-align:right}
.uf-company-name{font-size:12px;font-weight:900;line-height:1.45;color:#0b3471;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:520px}
.uf-company-code{display:inline-flex;margin-top:4px;padding:2px 7px;border-radius:6px;background:#dfeafa;color:#486a96;font-size:8.5px;font-weight:800;direction:ltr;line-height:1.5}
.uf-customer-verified{display:flex;align-items:center;gap:8px;color:#0c935f;flex:0 0 auto;text-align:right}
.uf-verified-check{width:27px;height:27px;border-radius:50%;display:grid;place-items:center;background:#10a56d;color:#fff;font-size:15px;font-weight:900;box-shadow:0 2px 6px rgba(16,165,109,.18)}
.uf-customer-verified b{display:block;font-size:10.5px;font-weight:900;line-height:1.5}.uf-customer-verified small{display:block;margin-top:1px;color:#7890a7;font-size:8.5px;font-weight:600}
.uf-customer-profile-body{padding:11px 14px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0}
.uf-customer-info-item{min-height:50px;display:flex;align-items:center;gap:8px;padding:6px 14px;border-left:1px solid #d9e5ee;min-width:0}.uf-customer-info-item:nth-child(3),.uf-customer-info-item:last-child{border-left:0}
.uf-customer-info-item:nth-child(n+4){border-top:1px solid #e3ecef}.uf-customer-info-item>div{min-width:0;text-align:right}.uf-customer-info-item small{display:block;font-size:8.5px;font-weight:800;color:#536b88;line-height:1.4}.uf-customer-info-item b{display:block;margin-top:2px;font-size:10px;font-weight:800;color:#0d356c;line-height:1.55;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.uf-info-icon{width:28px;height:28px;border-radius:7px;background:#e8f3ff;color:#1763af;display:grid;place-items:center;flex:0 0 28px}.uf-info-icon.email{background:#fff3e2;color:#e58319}.uf-info-icon.location{background:#fff0f2;color:#eb3650}.uf-info-icon.address{background:#f1ebff;color:#714dc3}.uf-info-icon svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.uf-address-item{grid-column:span 2}
.uf-customer-profile-foot{padding:0 14px 11px;display:flex;justify-content:flex-start}.uf-change-customer{height:32px;padding:0 13px;border-radius:7px;border:1px solid #1769c2;background:#fff;color:#1769c2;font:800 9px inherit;cursor:pointer;display:inline-flex;align-items:center;gap:6px}.uf-change-customer:hover{background:#f5f9ff}.uf-change-customer span{font-size:13px}
@media(max-width:900px){.uf-customer-profile-body{grid-template-columns:repeat(2,minmax(0,1fr))}.uf-customer-info-item,.uf-customer-info-item:nth-child(3){border-left:0;border-top:1px solid #e3ecef}.uf-customer-info-item:nth-child(odd){border-left:1px solid #d9e5ee}.uf-customer-info-item:nth-child(-n+2){border-top:0}.uf-address-item{grid-column:span 1}}
@media(max-width:620px){.uf-customer-profile-head{align-items:flex-start;flex-direction:column;padding:10px 12px}.uf-customer-profile-body{grid-template-columns:1fr;padding:8px 12px}.uf-customer-info-item,.uf-customer-info-item:nth-child(odd){border-left:0;border-top:1px solid #e3ecef;padding:8px 4px}.uf-customer-info-item:first-child{border-top:0}.uf-company-name{font-size:11px}.uf-customer-verified b{font-size:10px}.uf-customer-profile-foot{padding:0 12px 10px}}
</style>
HTML;
        $html = str_replace('</head>', $style.'</head>', $html);

        $script = <<<'HTML'
<script id="unifco-current-customer-card-script-v4">
(()=>{
    const status=document.getElementById('customer-status');
    const input=document.getElementById('customer_number');
    const card=document.getElementById('uf-current-customer-card');
    const changeButton=document.getElementById('uf-change-customer');
    if(!status||!input||!card)return;

    const field=(id)=>((document.getElementById(id)?.value)||'').trim();
    const setText=(id,value)=>{const el=document.getElementById(id);if(el)el.textContent=value||''};
    const customerValueIds=['uf-current-customer-name','uf-current-customer-code','uf-current-customer-contact','uf-current-customer-mobile','uf-current-customer-email','uf-current-customer-city','uf-current-customer-address'];
    const customerWrapIds=['uf-current-customer-contact-wrap','uf-current-customer-mobile-wrap','uf-current-customer-email-wrap','uf-current-customer-city-wrap','uf-current-customer-address-wrap'];

    const showEmpty=()=>{
        customerValueIds.forEach(id=>setText(id,''));
        customerWrapIds.forEach(id=>{const el=document.getElementById(id);if(el)el.hidden=false});
        card.classList.add('is-empty');
        card.hidden=false;
    };

    const hideLegacyCustomerFields=()=>{
        ['company_name','responsible_person','mobile','email','customer_city','customer_address'].forEach(id=>{
            const el=document.getElementById(id);
            if(!el)return;
            let node=el.parentElement;
            let chosen=null;
            while(node&&node!==document.body){
                if(node.matches?.('form,section'))break;
                const labels=node.querySelectorAll('label');
                const controls=node.querySelectorAll('input,select,textarea');
                if(labels.length&&controls.length<=2){chosen=node;break;}
                node=node.parentElement;
            }
            (chosen||el.parentElement)?.classList.add('uf-legacy-customer-field-hidden');
        });
    };
    hideLegacyCustomerFields();

    const render=()=>{
        if(!status.classList.contains('ok')){showEmpty();return;}
        window.setTimeout(()=>{
            if(!status.classList.contains('ok')){showEmpty();return;}
            const company=field('company_name')||'عميل UNIFCO';
            const code=field('customer_number')||input.value.trim();
            const contact=field('responsible_person');
            const mobile=field('mobile');
            const email=field('email');
            const city=field('customer_city')||field('site_city');
            const address=field('customer_address')||field('visible_site_address')||field('site_address');

            setText('uf-current-customer-name',company);
            setText('uf-current-customer-code',code);
            setText('uf-current-customer-contact',contact);
            setText('uf-current-customer-mobile',mobile);
            setText('uf-current-customer-email',email);
            setText('uf-current-customer-city',city);
            setText('uf-current-customer-address',address);

            customerWrapIds.forEach(id=>{const el=document.getElementById(id);if(el)el.hidden=false});
            hideLegacyCustomerFields();
            card.classList.remove('is-empty');
            card.hidden=false;
        },100);
    };

    new MutationObserver(render).observe(status,{attributes:true,childList:true,subtree:true});
    input.addEventListener('input',showEmpty);
    changeButton?.addEventListener('click',()=>{
        showEmpty();
        input.focus();
        input.select?.();
        input.scrollIntoView({behavior:'smooth',block:'center'});
    });
    render();
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);

        $response->setContent($html);
        return $response;
    }
}
