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

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $styles = <<<'HTML'
<style id="unifco-current-maintenance-form-enhancements">
/* Customer lookup: compact number field + blue data button */
#routine-form .lookup-row{display:flex!important;align-items:center!important;gap:10px!important;width:100%!important;max-width:none!important}
#routine-form #customer_number{width:50%!important;flex:0 0 50%!important;min-width:0!important}
#routine-form #customer-lookup{width:auto!important;min-width:138px!important;flex:0 0 auto!important;background:#1769c2!important;border:1px solid #1769c2!important;color:#fff!important;box-shadow:0 4px 12px rgba(23,105,194,.16)!important}
#routine-form #customer-lookup:hover{background:#105aa9!important;border-color:#105aa9!important}
#routine-form #customer-lookup:disabled{opacity:.68!important;cursor:wait!important}

/* Colored semantic icons for form sub-headings */
.uf-field-label{display:flex!important;align-items:center!important;gap:6px!important}
.uf-field-icon{width:22px;height:22px;min-width:22px;border-radius:6px;display:inline-grid;place-items:center;background:color-mix(in srgb,var(--uf-icon-color) 12%,white);color:var(--uf-icon-color);border:1px solid color-mix(in srgb,var(--uf-icon-color) 20%,white)}
.uf-field-icon svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}

@media(max-width:700px){
  #routine-form .lookup-row{align-items:stretch!important;flex-wrap:wrap!important}
  #routine-form #customer_number{width:100%!important;flex:1 1 100%!important}
  #routine-form #customer-lookup{width:100%!important;min-width:0!important}
}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-current-maintenance-form-enhancements-script">
(function(){
  function ready(fn){ if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded',fn); else fn(); }
  ready(function(){
    const customerInput = document.getElementById('customer_number');
    const lookupButton = document.getElementById('customer-lookup');
    const status = document.getElementById('customer-status');

    if (lookupButton) lookupButton.textContent = 'جلب البيانات';

    /* Automatic customer lookup after typing, on Enter, and on leaving the field. */
    if (customerInput && lookupButton) {
      let timer = null;
      let lastAutoValue = '';
      let inFlightValue = '';

      const runLookup = function(force){
        const value = customerInput.value.trim();
        clearTimeout(timer);
        if (!value) {
          lastAutoValue = '';
          inFlightValue = '';
          if (status) { status.className = 'status'; status.textContent = ''; }
          return;
        }
        if (!force && (value === lastAutoValue || value === inFlightValue)) return;
        inFlightValue = value;
        lastAutoValue = value;
        lookupButton.click();
        window.setTimeout(function(){ if(inFlightValue === value) inFlightValue = ''; }, 1200);
      };

      customerInput.addEventListener('input', function(){
        const value = customerInput.value.trim();
        if (value !== lastAutoValue) lastAutoValue = '';
        clearTimeout(timer);
        if (!value) return;
        timer = window.setTimeout(function(){ runLookup(false); }, 700);
      });
      customerInput.addEventListener('blur', function(){ runLookup(false); });
      customerInput.addEventListener('keydown', function(e){
        if (e.key === 'Enter') { e.preventDefault(); runLookup(true); }
      });
    }

    const iconPaths = {
      customer:'<circle cx="12" cy="8" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/>',
      company:'<path d="M4 21h16M6 21V7h8v14M14 11h4v10M9 10h2M9 14h2M9 18h2"/>',
      person:'<circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/>',
      phone:'<path d="M7 3h3l1.5 4-2 1.5a16 16 0 0 0 6 6l1.5-2 4 1.5v3c0 2-1.5 4-4 4C9 20 4 15 3 7c0-2.5 2-4 4-4Z"/>',
      mail:'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
      city:'<path d="M4 21h16M6 21V9h5v12M11 21V5h7v16M8 12h1M8 15h1M14 8h1M14 11h1M14 14h1"/>',
      pin:'<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
      contract:'<path d="M6 3h9l3 3v15H6z"/><path d="M15 3v4h4M9 11h6M9 15h6"/>',
      status:'<path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
      site:'<path d="M4 21h16M6 21V8h12v13M9 12h2M13 12h2M9 16h2M13 16h2"/>',
      asset:'<path d="M14.7 6.3a4 4 0 0 0-5 5L4 17l3 3 5.7-5.7a4 4 0 0 0 5-5l-2.4 2.4-3-3z"/>',
      calendar:'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
      clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
      priority:'<path d="M5 21V4M5 5h11l-2 4 2 4H5"/>',
      fault:'<path d="M12 3 2.5 20h19z"/><path d="M12 9v4M12 17h.01"/>',
      upload:'<path d="M12 16V4M8 8l4-4 4 4"/><path d="M4 14v6h16v-6"/>'
    };

    const rules = [
      [/رقم العميل/, 'customer', '#1769c2'], [/اسم الشركة/, 'company', '#6f42c1'], [/اسم مسؤول التواصل|مسؤول الموقع/, 'person', '#0f8b8d'],
      [/رقم التواصل/, 'phone', '#16803c'], [/البريد الإلكتروني/, 'mail', '#d97706'], [/المدينة/, 'city', '#0d6efd'],
      [/العنوان المختصر|العنوان$/, 'pin', '#e3132c'], [/رقم العقد|نوع \/ عنوان العقد/, 'contract', '#6f42c1'], [/حالة العقد/, 'status', '#198754'],
      [/الموقع المسجل|اسم الموقع/, 'site', '#0f8b8d'], [/المعدات المسجلة|اسم المعدة|نوع المعدة|الشركة المصنعة|الموديل|السيريال/, 'asset', '#8a5a00'],
      [/تاريخ الزيارة/, 'calendar', '#0d6efd'], [/الوقت المطلوب/, 'clock', '#6f42c1'], [/الأولوية/, 'priority', '#e3132c'],
      [/وصف العطل|وصف المشكلة|العطل/, 'fault', '#dc3545'], [/مرفق|صورة|تقرير/, 'upload', '#1769c2']
    ];

    document.querySelectorAll('#routine-form .field > label, #routine-form .upload > b').forEach(function(label){
      if (label.querySelector('.uf-field-icon')) return;
      const text = (label.textContent || '').replace(/\*/g,'').trim();
      const rule = rules.find(function(r){ return r[0].test(text); });
      if (!rule) return;
      const span = document.createElement('span');
      span.className = 'uf-field-icon';
      span.style.setProperty('--uf-icon-color', rule[2]);
      span.setAttribute('aria-hidden','true');
      span.innerHTML = '<svg viewBox="0 0 24 24">'+iconPaths[rule[1]]+'</svg>';
      label.classList.add('uf-field-label');
      label.insertBefore(span, label.firstChild);
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
