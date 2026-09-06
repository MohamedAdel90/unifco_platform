<?php

namespace App\Http\Middleware;

use App\Services\HomepageContentService;
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

        if (str_contains($html, 'id="unifco-request-chrome"')) {
            return $response;
        }

        $lang = $request->query('lang', 'ar') === 'en' ? 'en' : 'ar';
        $isAr = $lang === 'ar';
        $isSpare = $request->routeIs('public.current-maintenance.spare-parts');
        $home = app(HomepageContentService::class)->getContent($lang);

        $maintenanceUrl = route('public.current-maintenance', ['lang' => $lang]);
        $spareUrl = route('public.current-maintenance.spare-parts', ['lang' => $lang]);
        $homeUrl = route($isAr ? 'public.home' : 'public.home.en');
        $loginUrl = route('login');
        $requestUrl = route('public.request-service');
        $brandLogo = route('brand.logo');

        $styles = <<<'HTML'
<style id="unifco-request-selector-bar-style">
body > header.top,body > .page-head{display:none!important}
#maintenance-form > .panel:first-of-type{display:none!important}
#unifco-request-chrome{width:100%;background:#fff;position:relative;z-index:50}
.uf-request-nav{position:sticky;top:0;z-index:60;background:rgba(255,255,255,.97);border-bottom:1px solid #e7ebf0;backdrop-filter:blur(14px)}
.uf-request-nav-inner{width:min(1280px,94%);margin-inline:auto;min-height:76px;display:flex;align-items:center;gap:20px;direction:ltr}
.uf-brand-link{display:flex;align-items:center;gap:9px;margin-right:auto;direction:ltr;text-decoration:none}
.uf-logo-frame{width:42px;height:50px;overflow:hidden;flex:0 0 42px}.uf-logo{width:42px;height:65px;object-fit:cover;object-position:center top;display:block}
.uf-brand-copy{display:flex;flex-direction:column;line-height:.88}.uf-brand-copy strong{font-family:Inter,Arial,sans-serif;font-size:24px;letter-spacing:.03em;color:#071f4d}.uf-brand-copy small{margin-top:5px;font-family:Inter,Arial,sans-serif;font-size:6px;font-weight:900;letter-spacing:.16em;color:#ce122d}
.uf-request-links{display:flex;align-items:center;gap:23px;direction:rtl;white-space:nowrap}.uf-request-links a{position:relative;font-size:12px;font-weight:800;color:#25354d;text-decoration:none}.uf-request-links a:first-child:after,.uf-request-links a:hover:after{content:"";position:absolute;inset-inline:0;bottom:-12px;height:2px;background:#ce122d}
.uf-request-actions{display:flex;align-items:center;gap:9px;direction:ltr}.uf-lang{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:9px 13px;border:1px solid #ccd4df;border-radius:6px;font-size:10px;font-weight:900;color:#071f4d;background:#fff;text-decoration:none}
.uf-login,.uf-service-cta{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:10px 18px;border:1px solid transparent;border-radius:6px;color:#fff;font-size:11px;font-weight:900;text-decoration:none}.uf-login{background:#071f4d}.uf-service-cta{background:#ce122d}.uf-login:hover,.uf-service-cta:hover{transform:translateY(-1px);box-shadow:0 7px 18px rgba(7,31,77,.18)}
.uf-menu-toggle{display:none;border:0;background:#071f4d;color:#fff;width:42px;height:42px;border-radius:7px;font-size:20px;cursor:pointer}.uf-mobile-menu{display:none;width:min(1280px,94%);margin-inline:auto;padding:0 0 14px;direction:rtl}.uf-mobile-menu.open{display:grid;grid-template-columns:1fr 1fr;gap:7px}.uf-mobile-menu a{padding:10px 12px;background:#f5f7fa;border-radius:7px;color:#071f4d;font-size:11px;font-weight:800;text-decoration:none}
.uf-request-hero{background:linear-gradient(105deg,#0a234f 0%,#0b376d 58%,#082a5b 100%);border-bottom:4px solid #e3132c;color:#fff;min-height:112px;display:flex;align-items:center;position:relative;overflow:hidden}.uf-request-hero-inner{width:min(1480px,96%);margin:auto;text-align:right;padding:19px 0}.uf-request-hero h1{margin:0;font-size:31px;font-weight:900;line-height:1.25}.uf-request-hero p{margin:7px 0 0;font-size:11px;line-height:1.8;opacity:.9;max-width:920px}
.request-selector-wrap{background:#f4f7fa;padding:0 0 12px}.request-selector-bar{width:min(1480px,96%);margin:0 auto;background:#fff;border:1px solid #e0e7ef;border-top:0;border-radius:0 0 14px 14px;padding:14px 18px;box-shadow:0 8px 24px rgba(7,31,77,.08);position:sticky;top:0;z-index:65}.request-selector-grid{display:grid;grid-template-columns:1.18fr 1fr 1fr;gap:10px;align-items:end}.request-selector-field label{display:block;font-size:11px;font-weight:900;color:#071f4d;margin-bottom:6px}.request-selector-field select{width:100%;height:44px;border:1px solid #ccd7e5;border-radius:7px;background:#fff;padding:8px 11px;color:#233754;font-family:inherit;font-size:11px;outline:none}.uf-customer-options{display:grid;grid-template-columns:1fr 1fr;gap:8px}.uf-customer-option{height:44px;border:1px solid #ccd7e5;border-radius:7px;background:#fff;color:#19365e;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900}.uf-customer-option.active{border-color:#e3132c;background:#fff7f8;color:#df0b27}.request-selector-note{grid-column:1/-1;font-size:9px;color:#748198;margin-top:-1px}#maintenance-form,#spareForm{margin-top:0!important}#maintenance-form{padding-top:0!important}#spareForm > .shell{margin-top:12px!important}body > main.wrap{margin-top:12px!important}
@media(max-width:1100px){.uf-request-links{display:none}.uf-menu-toggle{display:block}.uf-request-actions{margin-left:auto}.request-selector-grid{grid-template-columns:1fr 1fr}.customer-selector{grid-column:1/-1}}
@media(max-width:700px){.uf-request-nav-inner{min-height:70px}.uf-logo-frame{width:38px;height:46px;flex-basis:38px}.uf-logo{width:38px;height:59px}.uf-brand-copy strong{font-size:21px}.uf-request-actions .uf-lang{display:none}.uf-request-hero{min-height:96px}.uf-request-hero h1{font-size:24px}.uf-request-hero p{font-size:9px}.request-selector-bar{width:96%;padding:11px}.request-selector-grid{grid-template-columns:1fr}.customer-selector{grid-column:auto}}
@media(max-width:450px){.uf-request-nav-inner,.uf-mobile-menu{width:min(100% - 28px,1280px)}.uf-request-actions{display:none}.uf-mobile-menu.open{display:grid}}
</style>
HTML;

        $targets = ['#home', '#about', '#services', '#industries', '#process', '#projects', '#contact'];
        $navHtml = '';
        $mobileNavHtml = '';
        foreach (($home['nav'] ?? []) as $i => $item) {
            $href = $homeUrl.($targets[$i] ?? '#contact');
            $link = '<a href="'.e($href).'">'.e($item).'</a>';
            $navHtml .= $link;
            $mobileNavHtml .= $link;
        }

        $switchLang = $lang === 'ar' ? 'en' : 'ar';
        $switchLabel = strtoupper($switchLang);
        $langUrl = $isSpare ? route('public.current-maintenance.spare-parts', ['lang' => $switchLang]) : route('public.current-maintenance', ['lang' => $switchLang]);
        $loginLabel = $home['login'] ?? ($isAr ? 'دخول العملاء' : 'Client Login');
        $requestLabel = $home['request'] ?? ($isAr ? 'طلب خدمة' : 'Request Service');

        $serviceOptions = '<option value="maintenance"'.($isSpare ? '' : ' selected').'>'.($isAr ? 'صيانة' : 'Maintenance').'</option><option value="quotation"'.($isSpare ? ' selected' : '').'>'.($isAr ? 'عرض سعر' : 'Quotation').'</option><option value="consultation">'.($isAr ? 'استشارة فنية' : 'Technical Consultation').'</option>';
        $subtypeOptions = $isSpare ? '<option value="parts" selected>'.($isAr ? 'عرض سعر قطع غيار' : 'Spare Parts Quotation').'</option>' : '<option value="routine" selected>'.($isAr ? 'صيانة عادية' : 'Routine Maintenance').'</option><option value="urgent">'.($isAr ? 'صيانة طارئة' : 'Emergency Maintenance').'</option>';

        $chrome = '<div id="unifco-request-chrome"><header class="uf-request-nav"><div class="uf-request-nav-inner">'
            .'<a class="uf-brand-link" href="'.e($homeUrl).'"><span class="uf-logo-frame"><img class="uf-logo" src="'.e($brandLogo).'" alt="UNIFCO"></span><span class="uf-brand-copy"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></a>'
            .'<nav class="uf-request-links">'.$navHtml.'</nav><div class="uf-request-actions"><a class="uf-lang" href="'.e($langUrl).'">'.e($switchLabel).'</a><a class="uf-login" href="'.e($loginUrl).'">'.e($loginLabel).'</a><a class="uf-service-cta" href="'.e($requestUrl).'">'.e($requestLabel).'</a></div><button class="uf-menu-toggle" id="uf-menu-toggle" type="button" aria-label="Menu">☰</button></div>'
            .'<nav class="uf-mobile-menu" id="uf-mobile-menu">'.$mobileNavHtml.'<a href="'.e($loginUrl).'">'.e($loginLabel).'</a><a href="'.e($requestUrl).'">'.e($requestLabel).'</a><a href="'.e($langUrl).'">'.e($switchLabel).'</a></nav></header>'
            .'<section class="uf-request-hero"><div class="uf-request-hero-inner"><h1>'.($isAr ? 'خدمة أسرع تبدأ بطلب أوضح' : 'A faster service starts with a clearer request').'</h1><p>'.($isAr ? 'خطوات بسيطة تساعد فريق UNIFCO على فهم الخدمة المطلوبة بشكل أسرع، وربط طلب العميل الحالي بعقده وموقعه ومعداته المسجلة.' : 'Simple steps help the UNIFCO team understand the requested service faster and link the customer request to the relevant contract, site and registered assets.').'</p></div></section>'
            .'<div class="request-selector-wrap"><section class="request-selector-bar" id="request-selector-bar"><div class="request-selector-grid"><div class="request-selector-field customer-selector"><label>'.($isAr ? 'نوع العميل' : 'Customer Type').'</label><div class="uf-customer-options"><div class="uf-customer-option active">✓ '.($isAr ? 'عميل حالي' : 'Existing Customer').'</div><div class="uf-customer-option">＋ '.($isAr ? 'عميل جديد' : 'New Customer').'</div></div></div><div class="request-selector-field"><label>'.($isAr ? 'نوع الخدمة المطلوبة' : 'Required Service').'</label><select id="persistent-service-type">'.$serviceOptions.'</select></div><div class="request-selector-field"><label>'.($isAr ? 'نوع الطلب' : 'Request Type').'</label><select id="persistent-service-subtype">'.$subtypeOptions.'</select></div><div class="request-selector-note">'.($isAr ? 'الجزء العلوي ثابت؛ يتم تغيير بيانات الطلب فقط أسفل هذا الشريط حسب نوع الخدمة ونوع الطلب.' : 'The upper section stays unchanged; only the request details below this bar change with the selected service and request type.').'</div></div></section></div></div>';

        $maintenanceJson = json_encode($maintenanceUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $spareJson = json_encode($spareUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $isSpareJson = $isSpare ? 'true' : 'false';
        $arJson = $isAr ? 'true' : 'false';
        $script = <<<HTML
<script id="unifco-request-selector-bar-script">
(function(){
 const isSpare={$isSpareJson},isAr={$arJson},maintenanceUrl={$maintenanceJson},spareUrl={$spareJson};
 function ready(fn){if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',fn);else fn()}
 ready(function(){
  const toggle=document.getElementById('uf-menu-toggle'),menu=document.getElementById('uf-mobile-menu');toggle?.addEventListener('click',()=>menu.classList.toggle('open'));menu?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>menu.classList.remove('open')));
  const service=document.getElementById('persistent-service-type'),subtype=document.getElementById('persistent-service-subtype');if(!service||!subtype)return;
  function fill(){const current=subtype.value;if(service.value==='maintenance'){subtype.innerHTML='<option value="routine">'+(isAr?'صيانة عادية':'Routine Maintenance')+'</option><option value="urgent">'+(isAr?'صيانة طارئة':'Emergency Maintenance')+'</option>';if(current==='urgent')subtype.value='urgent'}else if(service.value==='quotation'){subtype.innerHTML='<option value="parts">'+(isAr?'عرض سعر قطع غيار':'Spare Parts Quotation')+'</option>'}else{subtype.innerHTML='<option value="technical">'+(isAr?'استشارة فنية':'Technical Consultation')+'</option>'}}
  function apply(){if(service.value==='quotation'&&subtype.value==='parts'){if(!isSpare)location.assign(spareUrl);return}if(service.value==='maintenance'){if(isSpare){location.assign(maintenanceUrl);return}const oldService=document.getElementById('service-type'),oldSubtype=document.getElementById('service-subtype');if(oldService){oldService.value='maintenance';oldService.dispatchEvent(new Event('change',{bubbles:true}))}setTimeout(()=>{if(oldSubtype){oldSubtype.value=subtype.value;oldSubtype.dispatchEvent(new Event('change',{bubbles:true}))}},0);return}if(service.value==='consultation'&&!isSpare){const oldService=document.getElementById('service-type');if(oldService){oldService.value='consultation';oldService.dispatchEvent(new Event('change',{bubbles:true}))}}}
  service.addEventListener('change',()=>{fill();apply()});subtype.addEventListener('change',apply);
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
