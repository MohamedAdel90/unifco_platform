<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenanceFormEnhancements
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance', 'public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $styles = <<<'HTML'
<style id="unifco-current-maintenance-form-enhancements">
#routine-form .lookup-row,.uf-customer-card .lookup-row{display:flex!important;align-items:center!important;gap:10px!important;width:100%!important;max-width:none!important}
#routine-form #customer_number,.uf-customer-card #customer_number{width:50%!important;flex:0 0 50%!important;min-width:0!important}
#routine-form #customer-lookup,.uf-customer-card #customer-lookup{width:auto!important;min-width:138px!important;flex:0 0 auto!important;background:#1769c2!important;border:1px solid #1769c2!important;color:#fff!important;box-shadow:0 4px 12px rgba(23,105,194,.16)!important}
#routine-form #customer-lookup:hover,.uf-customer-card #customer-lookup:hover{background:#105aa9!important;border-color:#105aa9!important}

/* Persistent current-customer card - visible before and after lookup */
.uf-customer-card{background:#fff!important;border-color:#e1e8f0!important;overflow:hidden!important}
.uf-customer-summary{margin-top:16px;background:#f3fbf7;border:1px solid #bfe4cf;border-radius:14px;overflow:hidden;color:#082d62}
.uf-customer-summary-head{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center;padding:18px 20px;border-bottom:1px solid #d6ece0}
.uf-customer-main{display:flex;align-items:center;gap:12px;min-width:0}
.uf-customer-logo{width:56px;height:56px;border-radius:11px;background:#e2efff;color:#0b5fc0;display:grid;place-items:center;flex:0 0 auto}
.uf-customer-logo svg{width:28px;height:28px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.uf-customer-name{font-size:15px;font-weight:900;line-height:1.4;min-height:21px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.uf-customer-code{display:inline-flex;margin-top:6px;padding:3px 10px;border-radius:999px;background:#dfeafb;color:#2a5f9e;font-size:10px;font-weight:900;min-width:38px;justify-content:center}
.uf-customer-verify{display:flex;align-items:center;gap:10px;color:#15945d;min-width:205px}
.uf-customer-check{width:40px;height:40px;border-radius:50%;background:#17ad72;color:#fff;display:grid;place-items:center;font-size:22px;font-weight:900;box-shadow:0 6px 14px rgba(23,173,114,.18)}
.uf-customer-verify b{display:block;font-size:13px}.uf-customer-verify small{display:block;margin-top:3px;color:#7692a9;font-size:9px;font-weight:600}
.uf-customer-summary.waiting .uf-customer-check{background:#9fb1c3;box-shadow:none}.uf-customer-summary.waiting .uf-customer-verify{color:#627b92}
.uf-customer-summary.error .uf-customer-check{background:#d64a5c}.uf-customer-summary.error .uf-customer-verify{color:#b72c3f}
.uf-customer-details{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));padding:0 20px 14px}
.uf-customer-detail{display:flex;align-items:center;gap:10px;min-height:84px;padding:14px 12px;border-bottom:1px solid #dbe9e2}
.uf-customer-detail:not(:nth-child(3n)){border-left:1px solid #dbe9e2}
.uf-customer-detail:nth-last-child(-n+2){border-bottom:0}
.uf-customer-detail.address{grid-column:span 2}
.uf-detail-icon{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;flex:0 0 auto;background:#e7f1ff;color:#1769c2}
.uf-detail-icon.mail{background:#fff1dd;color:#ee8a19}.uf-detail-icon.city{background:#ffe9ec;color:#ee3150}.uf-detail-icon.address{background:#f2e8ff;color:#8256cc}.uf-detail-icon.person{background:#e8f7ff;color:#1d72b8}
.uf-detail-icon svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.uf-detail-text{min-width:0}.uf-detail-text span{display:block;font-size:9px;font-weight:800;color:#6f8298;margin-bottom:4px}.uf-detail-text b{display:block;font-size:11px;color:#082d62;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-height:16px}
.uf-customer-summary.waiting .uf-detail-text b{color:#a0afbd}.uf-customer-summary.waiting .uf-customer-name{color:#8194a7}.uf-customer-summary.waiting .uf-customer-code{background:#e8eef4;color:#8a9bad}
#customer-status{margin-top:9px!important}

/* semantic label icons */
.uf-field-label{display:flex!important;align-items:center!important;gap:6px!important}
.uf-field-icon{width:22px;height:22px;min-width:22px;border-radius:6px;display:inline-grid;place-items:center;background:#eef5ff;color:#1769c2;border:1px solid #d8e7fa}
.uf-field-icon svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}

@media(max-width:850px){
 .uf-customer-summary-head{grid-template-columns:1fr}.uf-customer-verify{min-width:0}.uf-customer-details{grid-template-columns:1fr 1fr}.uf-customer-detail:not(:nth-child(3n)){border-left:0}.uf-customer-detail:nth-child(odd){border-left:1px solid #dbe9e2}.uf-customer-detail.address{grid-column:span 2}
}
@media(max-width:700px){
 #routine-form .lookup-row,.uf-customer-card .lookup-row{align-items:stretch!important;flex-wrap:wrap!important}
 #routine-form #customer_number,.uf-customer-card #customer_number{width:100%!important;flex:1 1 100%!important}
 #routine-form #customer-lookup,.uf-customer-card #customer-lookup{width:100%!important;min-width:0!important}
 .uf-customer-details{grid-template-columns:1fr}.uf-customer-detail,.uf-customer-detail:nth-child(odd){border-left:0!important;border-bottom:1px solid #dbe9e2!important}.uf-customer-detail.address{grid-column:auto}.uf-customer-detail:last-child{border-bottom:0!important}
}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-current-maintenance-form-enhancements-script">
(function(){
  function ready(fn){ if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded',fn); else fn(); }
  ready(function(){
    const $ = id => document.getElementById(id);
    const customerInput = $('customer_number');
    const lookupButton = $('customer-lookup');
    const status = $('customer-status');
    if (!customerInput || !lookupButton) return;

    lookupButton.textContent = 'جلب البيانات';

    /* Find the actual customer panel by the customer input, not by a fragile form hierarchy. */
    const customerCard = customerInput.closest('section.panel, .panel, section, .card');
    if (customerCard) customerCard.classList.add('uf-customer-card');

    const svg = {
      building:'<path d="M4 21h16M6 21V7h8v14M14 11h4v10M9 10h2M9 14h2M9 18h2"/>',
      person:'<circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/>',
      phone:'<path d="M7 3h3l1.5 4-2 1.5a16 16 0 0 0 6 6l1.5-2 4 1.5v3c0 2-1.5 4-4 4C9 20 4 15 3 7c0-2.5 2-4 4-4Z"/>',
      mail:'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
      city:'<path d="M12 21s7-5.3 7-12a7 7 0 1 0-14 0c0 6.7 7 12 7 12Z"/><circle cx="12" cy="9" r="2.3"/>',
      address:'<path d="M5 21h14M7 21V8h10v13M10 12h1M13 12h1M10 16h1M13 16h1"/>'
    };

    function icon(name, cls){ return '<span class="uf-detail-icon '+(cls||'')+'"><svg viewBox="0 0 24 24">'+svg[name]+'</svg></span>'; }

    let summary = document.querySelector('.uf-customer-summary');
    if (!summary && customerCard) {
      summary = document.createElement('div');
      summary.className = 'uf-customer-summary waiting';
      summary.innerHTML =
        '<div class="uf-customer-summary-head">'+
          '<div class="uf-customer-main"><div class="uf-customer-logo"><svg viewBox="0 0 24 24">'+svg.building+'</svg></div><div><div class="uf-customer-name" id="uf-s-name">بيانات العميل ستظهر هنا</div><span class="uf-customer-code" id="uf-s-code">—</span></div></div>'+
          '<div class="uf-customer-verify"><span class="uf-customer-check" id="uf-s-check">✓</span><div><b id="uf-s-state">بانتظار التحقق من العميل</b><small id="uf-s-state-sub">أدخل رقم العميل ثم اضغط جلب البيانات</small></div></div>'+
        '</div>'+
        '<div class="uf-customer-details">'+
          '<div class="uf-customer-detail">'+icon('person','person')+'<div class="uf-detail-text"><span>مسؤول التواصل</span><b id="uf-s-person">—</b></div></div>'+
          '<div class="uf-customer-detail">'+icon('phone','')+'<div class="uf-detail-text"><span>رقم الجوال</span><b id="uf-s-mobile">—</b></div></div>'+
          '<div class="uf-customer-detail">'+icon('mail','mail')+'<div class="uf-detail-text"><span>البريد الإلكتروني</span><b id="uf-s-email">—</b></div></div>'+
          '<div class="uf-customer-detail">'+icon('city','city')+'<div class="uf-detail-text"><span>المدينة</span><b id="uf-s-city">—</b></div></div>'+
          '<div class="uf-customer-detail address">'+icon('address','address')+'<div class="uf-detail-text"><span>العنوان المختصر</span><b id="uf-s-address">—</b></div></div>'+
        '</div>';
      const anchor = status || lookupButton.closest('.field') || lookupButton.parentElement;
      if (anchor && anchor.parentNode) anchor.parentNode.insertBefore(summary, anchor.nextSibling);
      else customerCard.appendChild(summary);
    }

    const val = id => ($(id)?.value || '').trim();
    function syncSummary(){
      if (!summary) return;
      const name = val('company_name');
      const code = customerInput.value.trim();
      const hasData = !!name;
      $('uf-s-name').textContent = hasData ? name : 'بيانات العميل ستظهر هنا';
      $('uf-s-code').textContent = code || '—';
      $('uf-s-person').textContent = val('responsible_person') || '—';
      $('uf-s-mobile').textContent = val('mobile') || '—';
      $('uf-s-email').textContent = val('email') || '—';
      $('uf-s-city').textContent = val('customer_city') || '—';
      $('uf-s-address').textContent = val('customer_address') || '—';
      if (hasData) {
        summary.className = 'uf-customer-summary';
        $('uf-s-state').textContent = 'تم التحقق من العميل';
        $('uf-s-state-sub').textContent = 'تم جلب بيانات العميل بنجاح';
        $('uf-s-check').textContent = '✓';
      } else if (status && status.classList.contains('bad')) {
        summary.className = 'uf-customer-summary error';
        $('uf-s-state').textContent = 'تعذر التحقق من العميل';
        $('uf-s-state-sub').textContent = status.textContent || 'راجع رقم العميل وحاول مرة أخرى';
        $('uf-s-check').textContent = '!';
      } else {
        summary.className = 'uf-customer-summary waiting';
        $('uf-s-state').textContent = 'بانتظار التحقق من العميل';
        $('uf-s-state-sub').textContent = code ? 'اضغط جلب البيانات لإظهار التفاصيل' : 'أدخل رقم العميل ثم اضغط جلب البيانات';
        $('uf-s-check').textContent = '✓';
      }
    }

    syncSummary();
    customerInput.addEventListener('input', syncSummary);
    lookupButton.addEventListener('click', function(){ setTimeout(syncSummary,150); setTimeout(syncSummary,700); setTimeout(syncSummary,1400); });
    if (status) new MutationObserver(syncSummary).observe(status,{childList:true,subtree:true,attributes:true});

    /* Keep customer card visible when request type/subtype changes. */
    const routine = $('routine-form');
    const moveOutsideRoutine = function(){
      if (routine && customerCard && routine.contains(customerCard)) routine.parentNode.insertBefore(customerCard, routine);
    };
    moveOutsideRoutine();
    ['service-type','service-subtype'].forEach(function(id){ const el=$(id); if(el) el.addEventListener('change', function(){ moveOutsideRoutine(); setTimeout(syncSummary,0); }); });

    /* Automatic lookup after typing / Enter / blur. */
    let timer=null,last='';
    function runLookup(force){ const v=customerInput.value.trim(); clearTimeout(timer); if(!v){last='';syncSummary();return;} if(!force&&v===last)return; last=v; lookupButton.click(); }
    customerInput.addEventListener('input',function(){ clearTimeout(timer); const v=customerInput.value.trim(); if(!v)return; timer=setTimeout(function(){runLookup(false)},700); });
    customerInput.addEventListener('blur',function(){runLookup(false)});
    customerInput.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();runLookup(true)}});

    /* Add lightweight icons to labels, preserving the existing layout. */
    document.querySelectorAll('.uf-customer-card .field > label').forEach(function(label){
      if(label.querySelector('.uf-field-icon')) return;
      const s=document.createElement('span'); s.className='uf-field-icon'; s.setAttribute('aria-hidden','true'); s.innerHTML='<svg viewBox="0 0 24 24">'+svg.person+'</svg>'; label.classList.add('uf-field-label'); label.insertBefore(s,label.firstChild);
    });
  });
})();
</script>
HTML;

        $html = str_replace('</head>', $styles.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
