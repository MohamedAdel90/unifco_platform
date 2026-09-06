<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicRequestSelectorBar
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '') {
            return $response;
        }

        $styles = <<<'HTML'
<style id="unifco-request-selector-bar-style">
.request-selector-bar,
#maintenance-form > .panel:first-of-type{
  position:sticky!important;
  top:0!important;
  z-index:60!important;
  margin-top:0!important;
  margin-bottom:12px!important;
  border-radius:0 0 14px 14px!important;
  box-shadow:0 8px 24px rgba(7,31,77,.12)!important;
}
.request-selector-bar{
  background:#fff;
  border:1px solid #e2e8f0;
  padding:14px 18px;
}
.request-selector-grid{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:10px;align-items:end}
.request-selector-field label{display:block;font-size:11px;font-weight:900;color:#071f4d;margin-bottom:6px}
.request-selector-field select,.request-selector-field .selector-static{width:100%;height:42px;border:1px solid #ccd7e5;border-radius:7px;background:#fff;padding:8px 11px;color:#233754;font-family:inherit;font-size:11px}
.request-selector-field .selector-static{display:flex;align-items:center;font-weight:800;background:#fff8f9;border-color:#e3132c;color:#c7162d}
.request-selector-note{grid-column:1/-1;font-size:9px;color:#748198;margin-top:-2px}
#spareForm{margin-top:0!important}
#spareForm > .shell{margin-top:12px!important}
@media(max-width:850px){.request-selector-grid{grid-template-columns:1fr 1fr}.request-selector-grid .customer-selector{grid-column:1/-1}}
@media(max-width:560px){.request-selector-grid{grid-template-columns:1fr}.request-selector-grid .customer-selector{grid-column:auto}.request-selector-bar,#maintenance-form > .panel:first-of-type{top:0!important;border-radius:0 0 10px 10px!important}}
</style>
HTML;

        $html = str_replace('</head>', $styles.'</head>', $html);

        if ($request->routeIs('public.current-maintenance.spare-parts')) {
            $lang = $request->query('lang', 'ar') === 'en' ? 'en' : 'ar';
            $isAr = $lang === 'ar';
            $maintenanceUrl = route('public.current-maintenance');
            $spareUrl = route('public.current-maintenance.spare-parts', ['lang' => $lang]);

            $bar = '<section class="request-selector-bar" id="request-selector-bar"><div class="request-selector-grid">'
                .'<div class="request-selector-field customer-selector"><label>'.($isAr ? 'نوع العميل' : 'Customer Type').'</label><div class="selector-static">✓ '.($isAr ? 'عميل حالي' : 'Existing Customer').'</div></div>'
                .'<div class="request-selector-field"><label>'.($isAr ? 'نوع الخدمة المطلوبة' : 'Required Service').'</label><select id="persistent-service-type"><option value="maintenance">'.($isAr ? 'صيانة' : 'Maintenance').'</option><option value="quotation" selected>'.($isAr ? 'عرض سعر' : 'Quotation').'</option><option value="consultation">'.($isAr ? 'استشارة فنية' : 'Technical Consultation').'</option></select></div>'
                .'<div class="request-selector-field"><label>'.($isAr ? 'نوع الطلب' : 'Request Type').'</label><select id="persistent-service-subtype"><option value="parts" selected>'.($isAr ? 'عرض سعر قطع غيار' : 'Spare Parts Quotation').'</option></select></div>'
                .'<div class="request-selector-note">'.($isAr ? 'يتم عرض بيانات الطلب المختار مباشرة أسفل هذا الشريط دون تكرار بيانات الاختيار.' : 'The selected request data starts directly below this bar without repeating the selection fields.').'</div>'
                .'</div></section>';

            $script = '<script id="unifco-request-selector-bar-script">(()=>{const service=document.getElementById("persistent-service-type");if(!service)return;service.addEventListener("change",()=>{if(service.value==="maintenance")window.location.assign('.json_encode($maintenanceUrl).');else if(service.value==="quotation")window.location.assign('.json_encode($spareUrl).');});})();</script>';

            $html = str_replace('<form id="spareForm"', $bar.'<form id="spareForm"', $html);
            $html = str_replace('</body>', $script.'</body>', $html);
        }

        $response->setContent($html);

        return $response;
    }
}
