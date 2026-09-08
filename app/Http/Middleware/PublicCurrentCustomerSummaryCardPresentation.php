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

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="customer-status"') || ! str_contains($html, '</body>')) {
            return $response;
        }

        $card = <<<'HTML'
<div class="uf-current-customer-card" id="uf-current-customer-card" aria-live="polite" hidden>
    <div class="uf-current-customer-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M4 21h16M6 21V7l6-4 6 4v14M9 10h2M13 10h2M9 14h2M13 14h2M10 21v-4h4v4"/></svg>
    </div>
    <div class="uf-current-customer-copy">
        <div class="uf-current-customer-name" id="uf-current-customer-name">—</div>
        <div class="uf-current-customer-code" id="uf-current-customer-code">—</div>
        <div class="uf-current-customer-contact">
            <span id="uf-current-customer-mobile-wrap"><span id="uf-current-customer-mobile">—</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.28-1.28a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.9Z"/></svg></span>
            <span class="uf-current-customer-separator"></span>
            <span id="uf-current-customer-email-wrap"><span id="uf-current-customer-email">—</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM4 6l8 6 8-6"/></svg></span>
        </div>
    </div>
    <div class="uf-current-customer-ok" aria-hidden="true">✓</div>
</div>
HTML;

        $html = str_replace('<div class="status" id="customer-status"></div>', '<div class="status" id="customer-status"></div>'.$card, $html);

        $style = <<<'HTML'
<style id="unifco-current-customer-card-style-v1">
.uf-current-customer-card{margin-top:12px;min-height:92px;border:1px solid #cce8df;border-radius:8px;background:linear-gradient(90deg,#f5fffb 0%,#f1fcfa 55%,#effaf7 100%);display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:14px;padding:14px 16px;direction:rtl;box-shadow:0 2px 8px rgba(18,122,92,.03)}
.uf-current-customer-card[hidden]{display:none!important}
.uf-current-customer-icon{width:38px;height:52px;border-radius:7px;background:#dceeff;display:grid;place-items:center;color:#0b3f83;flex:0 0 auto}
.uf-current-customer-icon svg{width:25px;height:25px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
.uf-current-customer-copy{min-width:0;text-align:right}
.uf-current-customer-name{font-size:12px;font-weight:900;color:#0b3471;line-height:1.5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.uf-current-customer-code{display:inline-flex;margin-top:4px;padding:3px 8px;border-radius:6px;background:#dbe8fb;color:#335b92;font-size:9px;font-weight:800;direction:ltr}
.uf-current-customer-contact{margin-top:9px;display:flex;align-items:center;gap:13px;color:#2f5c91;font-size:9.5px;font-weight:600;direction:ltr;flex-wrap:wrap;justify-content:flex-start}
.uf-current-customer-contact>span:not(.uf-current-customer-separator){display:inline-flex;align-items:center;gap:6px}
.uf-current-customer-contact svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.uf-current-customer-separator{width:1px;height:15px;background:#cbd9e8}
.uf-current-customer-ok{width:22px;height:22px;border-radius:50%;background:#0ea36f;color:#fff;display:grid;place-items:center;font-size:13px;font-weight:900;box-shadow:0 2px 5px rgba(14,163,111,.22)}
@media(max-width:640px){.uf-current-customer-card{grid-template-columns:auto 1fr auto;padding:12px;gap:10px}.uf-current-customer-contact{gap:8px}.uf-current-customer-separator{display:none}.uf-current-customer-icon{width:34px;height:46px}.uf-current-customer-name{font-size:11px}}
</style>
HTML;
        $html = str_replace('</head>', $style.'</head>', $html);

        $script = <<<'HTML'
<script id="unifco-current-customer-card-script-v1">
(()=>{
    const status=document.getElementById('customer-status');
    const input=document.getElementById('customer_number');
    const card=document.getElementById('uf-current-customer-card');
    if(!status||!input||!card)return;

    const field=(id)=>((document.getElementById(id)?.value)||'').trim();
    const setText=(id,value)=>{const el=document.getElementById(id);if(el)el.textContent=value||'—'};
    const sync=()=>{
        if(!status.classList.contains('ok')){card.hidden=true;return;}
        window.setTimeout(()=>{
            if(!status.classList.contains('ok')){card.hidden=true;return;}
            const company=field('company_name')||'عميل UNIFCO';
            const code=field('customer_number')||input.value.trim();
            const mobile=field('mobile');
            const email=field('email');
            setText('uf-current-customer-name',company);
            setText('uf-current-customer-code',code);
            setText('uf-current-customer-mobile',mobile);
            setText('uf-current-customer-email',email);
            const mobileWrap=document.getElementById('uf-current-customer-mobile-wrap');
            const emailWrap=document.getElementById('uf-current-customer-email-wrap');
            const separator=card.querySelector('.uf-current-customer-separator');
            if(mobileWrap)mobileWrap.hidden=!mobile;
            if(emailWrap)emailWrap.hidden=!email;
            if(separator)separator.hidden=!(mobile&&email);
            card.hidden=false;
        },80);
    };

    new MutationObserver(sync).observe(status,{attributes:true,childList:true,subtree:true});
    input.addEventListener('input',()=>{card.hidden=true});
    sync();
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);

        $response->setContent($html);
        return $response;
    }
}
