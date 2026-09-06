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
        if ($html === '' || ! str_contains($html, '</head>') || ! str_contains($html, '<body')) {
            return $response;
        }

        // This middleware can be reached through more than one presentation layer.
        // Never inject the shared request chrome twice into the same response.
        if (str_contains($html, 'id="unifco-request-chrome"')) {
            return $response;
        }

        $lang = $request->query('lang', 'ar') === 'en' ? 'en' : 'ar';
        $isAr = $lang === 'ar';
        $isSpare = $request->routeIs('public.current-maintenance.spare-parts');

        $maintenanceUrl = route('public.current-maintenance', ['lang' => $lang]);
        $spareUrl = route('public.current-maintenance.spare-parts', ['lang' => $lang]);
        $homeUrl = route('public.home');

        $styles = <<<'HTML'
<style id="unifco-request-selector-bar-style">
body > header.top,
body > .page-head{display:none!important}
#maintenance-form > .panel:first-of-type{display:none!important}
#unifco-request-chrome{width:100%;background:#fff;position:relative;z-index:50}
.uf-request-nav{height:72px;background:#fff;border-bottom:1px solid #e5eaf0;display:flex;align-items:center}
.uf-request-nav-inner{width:min(1480px,96%);margin:auto;display:flex;align-items:center;gap:22px}
.uf-request-logo{width:116px;height:52px;object-fit:contain;flex:0 0 auto}
.uf-request-links{display:flex;align-items:center;justify-content:center;gap:23px;flex:1;white-space:nowrap}
.uf-request-links a{color:#1c3150;text-decoration:none;font-size:11px;font-weight:800}
.uf-request-links a:hover{color:#df0b27}
.uf-request-actions{display:flex;align-items:center;gap:10px;flex:0 0 auto}
.uf-lang{height:38px;min-width:46px;border:1px solid #d3dce7;border-radius:7px;background:#fff;color:#19365e;text-decoration:none;display:grid;place-items:center;font-size:10px;font-weight:900}
.uf-service-cta{height:42px;padding:0 28px;border-radius:7px;background:#df0b27;color:#fff!important;text-decoration:none;display:flex;align-items:center;font-size:11px;font-weight:900;box-shadow:0 4px 12px rgba(223,11,39,.18)}
.uf-request-hero{background:linear-gradient(105deg,#0a234f 0%,#0b376d 58%,#082a5b 100%);border-bottom:4px solid #e3132c;color:#fff;min-height:112px;display:flex;align-items:center;position:relative;overflow:hidden}
.uf-request-hero:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.025),transparent);pointer-events:none}
.uf-request-hero-inner{width:min(1480px,96%);margin:auto;text-align:right;position:relative;z-index:1;padding:19px 0}
html[dir="ltr"] .uf-request-hero-inner{text-align:left}
.uf-request-hero h1{margin:0;font-size:31px;font-weight:900;line-height:1.25}
.uf-request-hero p{margin:7px 0 0;font-size:11px;line-height:1.8;opacity:.9;max-width:920px}
html[dir="rtl"] .uf-request-hero p{margin-right:0;margin-left:auto}
.request-selector-wrap{background:#f4f7fa;padding:0 0 12px}
.request-selector-bar{width:min(1480px,96%);margin:0 auto;background:#fff;border:1px solid #e0e7ef;border-top:0;border-radius:0 0 14px 14px;padding:14px 18px;box-shadow:0 8px 24px rgba(7,31,77,.08);position:sticky;top:0;z-index:65}
.request-selector-grid{display:grid;grid-template-columns:1.18fr 1fr 1fr;gap:10px;align-items:end}
.request-selector-field label{display:block;font-size:11px;font-weight:900;color:#071f4d;margin-bottom:6px}
.request-selector-field select{width:100%;height:44px;border:1px solid #ccd7e5;border-radius:7px;background:#fff;padding:8px 11px;color:#233754;font-family:inherit;font-size:11px;outline:none}
.request-selector-field select:focus{border-color:#6f91bd;box-shadow:0 0 0 3px rgba(56,110,174,.09)}
.uf-customer-options{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.uf-customer-option{height:44px;border:1px solid #ccd7e5;border-radius:7px;background:#fff;color:#19365e;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900}
.uf-customer-option.active{border-color:#e3132c;background:#fff7f8;color:#df0b27;box-shadow:0 0 0 1px rgba(227,19,44,.06)}
.request-selector-note{grid-column:1/-1;font-size:9px;color:#748198;margin-top:-1px}
#maintenance-form,#spareForm{margin-top:0!important}
#maintenance-form{padding-top:0!important}
#spareForm > .shell{margin-top:12px!important}
body > main.wrap{margin-top:12px!important}
@media(max-width:1050px){.uf-request-links{gap:13px}.uf-request-links a{font-size:9px}.uf-service-cta{padding:0 18px}.request-selector-grid{grid-template-columns:1fr 1fr}.customer-selector{grid-column:1/-1}}
@media(max-width:760px){.uf-request-nav{height:auto;min-height:66px}.uf-request-nav-inner{padding:7px 0}.uf-request-links{display:none}.uf-request-logo{width:96px;height:46px}.uf-request-actions{margin-inline-start:auto}.uf-request-hero{min-height:96px}.uf-request-hero h1{font-size:24px}.uf-request-hero p{font-size:9px}.request-selector-bar{width:96%;padding:11px}.request-selector-grid{grid-template-columns:1fr}.customer-selector{grid-column:auto}.request-selector-note{font-size:8px}.uf-service-cta{height:38px;padding:0 15px}}
</style>
HTML;

        $serviceOptions = '<option value="maintenance"'.($isSpare ? '' : ' selected').'>'.($isAr ? 'صيانة' : 'Maintenance').'</option>'
            .'<option value="quotation"'.($isSpare ? ' selected' : '').'>'.($isAr ? 'عرض سعر' : 'Quotation').'</option>'
            .'<option value="consultation">'.($isAr ? 'استشارة فنية' : 'Technical Consultation').'</option>';

        $subtypeOptions = $isSpare
            ? '<option value="parts" selected>'.($isAr ? 'عرض سعر قطع غيار' : 'Spare Parts Quotation').'</option>'
            : '<option value="routine" selected>'.($isAr ? 'صيانة عادية' : 'Routine Maintenance').'</option><option value="urgent">'.($isAr ? 'صيانة طارئة' : 'Emergency Maintenance').'</option>';

        $switchLang = $lang === 'ar' ? 'en' : 'ar';
        $switchLabel = strtoupper($switchLang);
        $langUrl = $isSpare
            ? route('public.current-maintenance.spare-parts', ['lang' => $switchLang])
            : route('public.current-maintenance', ['lang' => $switchLang]);

        $chrome = '<div id="unifco-request-chrome">'
            .'<header class="uf-request-nav"><div class="uf-request-nav-inner">'
            .'<a href="'.e($homeUrl).'"><img class="uf-request-logo" src="/brand/unifco-logo-v2.webp" alt="UNIFCO"></a>'
            .'<nav class="uf-request-links" aria-label="'.($isAr ? 'التنقل الرئيسي' : 'Main navigation').'">'
            .'<a href="/">'.($isAr ? 'الرئيسية' : 'Home').'</a>'
            .'<a href="/about">'.($isAr ? 'من نحن' : 'About').'</a>'
            .'<a href="/services">'.($isAr ? 'الخدمات' : 'Services').'</a>'
            .'<a href="/sectors">'.($isAr ? 'القطاعات' : 'Sectors').'</a>'
            .'<a href="/projects">'.($isAr ? 'المشاريع' : 'Projects').'</a>'
            .'<a href="/clients">'.($isAr ? 'عملاؤنا' : 'Clients').'</a>'
            .'<a href="/careers">'.($isAr ? 'الوظائف' : 'Careers').'</a>'
            .'<a href="/contact">'.($isAr ? 'تواصل معنا' : 'Contact').'</a>'
            .'</nav>'
            .'<div class="uf-request-actions"><a class="uf-lang" href="'.e($langUrl).'">'.e($switchLabel).'</a><a class="uf-service-cta" href="'.e($maintenanceUrl).'">'.($isAr ? 'طلب خدمة' : 'Request Service').'</a></div>'
            .'</div></header>'
            .'<section class="uf-request-hero"><div class="uf-request-hero-inner"><h1>'.($isAr ? 'خدمة أسرع تبدأ بطلب أوضح' : 'A faster service starts with a clearer request').'</h1><p>'.($isAr ? 'خطوات بسيطة تساعد فريق UNIFCO على فهم الخدمة المطلوبة بشكل أسرع، وربط طلب العميل الحالي بعقده وموقعه ومعداته المسجلة.' : 'Simple steps help the UNIFCO team understand the requested service faster and link the customer request to the relevant contract, site and registered assets.').'</p></div></section>'
            .'<div class="request-selector-wrap"><section class="request-selector-bar" id="request-selector-bar"><div class="request-selector-grid">'
            .'<div class="request-selector-field customer-selector"><label>'.($isAr ? 'نوع العميل' : 'Customer Type').'</label><div class="uf-customer-options"><div class="uf-customer-option active">✓ '.($isAr ? 'عميل حالي' : 'Existing Customer').'</div><div class="uf-customer-option">＋ '.($isAr ? 'عميل جديد' : 'New Customer').'</div></div></div>'
            .'<div class="request-selector-field"><label>'.($isAr ? 'نوع الخدمة المطلوبة' : 'Required Service').'</label><select id="persistent-service-type">'.$serviceOptions.'</select></div>'
            .'<div class="request-selector-field"><label>'.($isAr ? 'نوع الطلب' : 'Request Type').'</label><select id="persistent-service-subtype">'.$subtypeOptions.'</select></div>'
            .'<div class="request-selector-note">'.($isAr ? 'الجزء العلوي ثابت؛ يتم تغيير بيانات الطلب فقط أسفل هذا الشريط حسب نوع الخدمة ونوع الطلب.' : 'The upper section stays unchanged; only the request details below this bar change with the selected service and request type.').'</div>'
            .'</div></section></div></div>';

        $maintenanceJson = json_encode($maintenanceUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $spareJson = json_encode($spareUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $isSpareJson = $isSpare ? 'true' : 'false';
        $arJson = $isAr ? 'true' : 'false';

        $script = <<<HTML
<script id="unifco-request-selector-bar-script">
(function(){
  const isSpare={$isSpareJson};
  const isAr={$arJson};
  const maintenanceUrl={$maintenanceJson};
  const spareUrl={$spareJson};
  function ready(fn){ if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',fn); else fn(); }
  ready(function(){
    const chromeNodes=Array.from(document.querySelectorAll('#unifco-request-chrome'));
    chromeNodes.slice(1).forEach(function(node){ node.remove(); });

    const service=document.getElementById('persistent-service-type');
    const subtype=document.getElementById('persistent-service-subtype');
    if(!service||!subtype)return;

    function fillSubtypes(){
      const current=subtype.value;
      if(service.value==='maintenance'){
        subtype.innerHTML='<option value="routine">'+(isAr?'صيانة عادية':'Routine Maintenance')+'</option><option value="urgent">'+(isAr?'صيانة طارئة':'Emergency Maintenance')+'</option>';
        if(current==='urgent') subtype.value='urgent';
      }else if(service.value==='quotation'){
        subtype.innerHTML='<option value="parts">'+(isAr?'عرض سعر قطع غيار':'Spare Parts Quotation')+'</option>';
      }else{
        subtype.innerHTML='<option value="technical">'+(isAr?'استشارة فنية':'Technical Consultation')+'</option>';
      }
    }

    function applySelection(){
      if(service.value==='quotation' && subtype.value==='parts'){
        if(!isSpare) window.location.assign(spareUrl);
        return;
      }
      if(service.value==='maintenance'){
        if(isSpare){ window.location.assign(maintenanceUrl); return; }
        const oldService=document.getElementById('service-type');
        const oldSubtype=document.getElementById('service-subtype');
        if(oldService){ oldService.value='maintenance'; oldService.dispatchEvent(new Event('change',{bubbles:true})); }
        window.setTimeout(function(){
          if(oldSubtype){ oldSubtype.value=subtype.value; oldSubtype.dispatchEvent(new Event('change',{bubbles:true})); }
        },0);
        return;
      }
      if(service.value==='consultation' && !isSpare){
        const oldService=document.getElementById('service-type');
        if(oldService){ oldService.value='consultation'; oldService.dispatchEvent(new Event('change',{bubbles:true})); }
      }
    }

    service.addEventListener('change',function(){ fillSubtypes(); applySelection(); });
    subtype.addEventListener('change',applySelection);
  });
})();
</script>
HTML;

        $html = str_replace('</head>', $styles.'</head>', $html);
        $html = preg_replace('/<body([^>]*)>/', '<body$1>'.$chrome, $html, 1) ?? $html;
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
