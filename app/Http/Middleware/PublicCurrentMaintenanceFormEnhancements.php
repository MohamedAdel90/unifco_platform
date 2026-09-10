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

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $styles = <<<'HTML'
<style id="unifco-current-maintenance-form-enhancements">
#routine-form .lookup-row,.uf-customer-card .lookup-row{display:flex!important;align-items:center!important;gap:10px!important;width:100%!important;max-width:none!important}
#routine-form #customer_number,.uf-customer-card #customer_number{width:50%!important;flex:0 0 50%!important;min-width:0!important}
#routine-form #customer-lookup,.uf-customer-card #customer-lookup{width:auto!important;min-width:138px!important;flex:0 0 auto!important;background:#1769c2!important;border:1px solid #1769c2!important;color:#fff!important;box-shadow:0 4px 12px rgba(23,105,194,.16)!important}
#routine-form #customer-lookup:hover,.uf-customer-card #customer-lookup:hover{background:#105aa9!important;border-color:#105aa9!important}

/* Current customer card inside the unified request form */
.uf-customer-card{background:#eefbf3!important;border:1px solid #a8deb9!important;overflow:hidden!important;min-height:300px!important}
.uf-customer-card .section-title{color:#08752c!important}
.uf-customer-card>.grid{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:10px!important;align-items:end!important}
.uf-customer-card>.grid>.field{display:grid!important;visibility:visible!important;opacity:1!important}
.uf-customer-card input[readonly]{background:#fff!important;color:#20324e!important;border-color:#c9dfd1!important}
.uf-customer-card #customer-status{display:block!important;min-height:38px!important;margin-top:9px!important;background:#e6f7eb!important;border:1px solid #bee2c9!important;color:#26713d!important}
.uf-customer-card #customer-status:empty:before{content:'بانتظار إدخال رقم العميل';font-size:10px;font-weight:800}

/* The retrieved customer information fills the previously empty area. */
.uf-customer-summary{display:none!important}
.uf-current-customer-details{grid-column:1/-1!important;display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:10px!important;margin-top:2px!important}
.uf-current-customer-details .field{display:grid!important;gap:6px!important}
.uf-current-customer-details .field.address{grid-column:span 2!important}

/* semantic label icons */
.uf-field-label{display:flex!important;align-items:center!important;gap:6px!important}
.uf-field-icon{width:22px;height:22px;min-width:22px;border-radius:6px;display:inline-grid;place-items:center;background:#eef5ff;color:#1769c2;border:1px solid #d8e7fa}
.uf-field-icon svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}

@media(max-width:850px){
 .uf-customer-card>.grid,.uf-current-customer-details{grid-template-columns:1fr 1fr!important}
 .uf-current-customer-details .field.address{grid-column:span 2!important}
}
@media(max-width:700px){
 #routine-form .lookup-row,.uf-customer-card .lookup-row{align-items:stretch!important;flex-wrap:wrap!important}
 #routine-form #customer_number,.uf-customer-card #customer_number{width:100%!important;flex:1 1 100%!important}
 #routine-form #customer-lookup,.uf-customer-card #customer-lookup{width:100%!important;min-width:0!important}
 .uf-customer-card>.grid,.uf-current-customer-details{grid-template-columns:1fr!important}
 .uf-current-customer-details .field.address{grid-column:auto!important}
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

    const customerCard = customerInput.closest('section.panel, .panel, section, .card');
    if (!customerCard) return;
    customerCard.classList.add('uf-customer-card');

    /* Keep the customer card in the unified form regardless of service/request type. */
    const routine = $('routine-form');
    if (routine && routine.contains(customerCard)) {
      routine.parentNode.insertBefore(customerCard, routine);
    }

    /* Remove legacy generated summary; use the approved inline customer fields instead. */
    document.querySelectorAll('.uf-customer-summary').forEach(function(el){ el.remove(); });

    const grid = customerCard.querySelector(':scope > .grid');
    if (grid) {
      const lookupField = customerInput.closest('.field');
      const nativeFields = Array.from(grid.querySelectorAll(':scope > .field')).filter(function(field){ return field !== lookupField; });
      if (nativeFields.length) {
        let details = customerCard.querySelector('.uf-current-customer-details');
        if (!details) {
          details = document.createElement('div');
          details.className = 'uf-current-customer-details';
          nativeFields.forEach(function(field){
            if (field.querySelector('#customer_address')) field.classList.add('address');
            details.appendChild(field);
          });
          grid.appendChild(details);
        }
      }
      Array.from(grid.children).forEach(function(el){
        if (el.tagName === 'DIV' && !el.classList.contains('field') && !el.classList.contains('uf-current-customer-details')) {
          if (!el.textContent.trim()) el.remove();
        }
      });
    }

    const svg = {
      person:'<circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/>'
    };
    document.querySelectorAll('.uf-customer-card .field > label').forEach(function(label){
      if(label.querySelector('.uf-field-icon')) return;
      const s=document.createElement('span');
      s.className='uf-field-icon';
      s.setAttribute('aria-hidden','true');
      s.innerHTML='<svg viewBox="0 0 24 24">'+svg.person+'</svg>';
      label.classList.add('uf-field-label');
      label.insertBefore(s,label.firstChild);
    });

    function keepVisible(){
      customerCard.style.display='block';
      customerCard.style.visibility='visible';
      customerCard.style.opacity='1';
    }
    keepVisible();
    ['service-type','service-subtype'].forEach(function(id){
      const el=$(id);
      if(el) el.addEventListener('change', function(){ keepVisible(); });
    });

    /* Automatic lookup after typing / Enter / blur. */
    let timer=null,last='';
    function runLookup(force){
      const v=customerInput.value.trim();
      clearTimeout(timer);
      if(!v){last='';return;}
      if(!force&&v===last)return;
      last=v;
      lookupButton.click();
    }
    customerInput.addEventListener('input',function(){
      clearTimeout(timer);
      const v=customerInput.value.trim();
      if(!v)return;
      timer=setTimeout(function(){runLookup(false)},700);
    });
    customerInput.addEventListener('blur',function(){runLookup(false)});
    customerInput.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();runLookup(true)}});
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
