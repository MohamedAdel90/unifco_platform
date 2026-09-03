<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenancePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $styles = <<<'HTML'
<style id="unifco-current-maintenance-v7">
:root{--unifco-navy:#08295d;--unifco-red:#e3132c;--ufm-text:#0b2854;--ufm-muted:#6f819c}
.current-service-nav{position:sticky;top:0;z-index:60;background:rgba(255,255,255,.98);border-bottom:1px solid #e7ebf0;backdrop-filter:blur(14px);box-shadow:0 2px 10px rgba(7,31,77,.04)}
.current-service-nav .nav-wrap{width:min(1560px,96%);min-height:86px;margin-inline:auto;display:flex;align-items:center;gap:16px;direction:ltr}
.current-service-nav .brand-link{display:flex;align-items:center;gap:9px;margin:0!important;flex:0 0 auto;direction:ltr;text-decoration:none}
.current-service-nav .site-logo-frame{width:42px;height:50px;overflow:hidden;flex:0 0 42px}
.current-service-nav .site-logo{width:42px;height:65px;object-fit:cover;object-position:center top;display:block}
.current-service-nav .brand-copy{display:flex;flex-direction:column;line-height:.88}
.current-service-nav .brand-copy strong{font-family:Inter,Arial,sans-serif;font-size:26px;letter-spacing:.03em;color:#071f4d;font-weight:800}
.current-service-nav .brand-copy small{margin-top:5px;font-family:Inter,Arial,sans-serif;font-size:7px;font-weight:900;letter-spacing:.16em;color:#ce122d}
.current-service-nav .nav-actions{display:flex;align-items:center;gap:10px;direction:ltr;flex:0 0 auto;margin-left:8px}
.current-service-nav .lang{display:inline-flex;align-items:center;justify-content:center;width:58px;min-height:48px;padding:9px 12px;border:1px solid #ccd4df;border-radius:7px;font-size:11px;font-weight:900;color:#071f4d;background:#fff;text-decoration:none}
.current-service-nav .request-btn{display:inline-flex;align-items:center;justify-content:center;min-width:126px;min-height:48px;padding:10px 20px;border-radius:7px;background:#ce122d;color:#fff;text-decoration:none;font-size:13px;font-weight:900}
.current-service-nav .primary-nav{display:flex;align-items:center;gap:26px;direction:rtl;margin-left:auto;flex:0 1 auto}
.current-service-nav .primary-nav a{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;color:#25354d;text-decoration:none;font-size:13px;line-height:1.25;font-weight:800;white-space:nowrap;min-width:70px}
.current-service-nav .primary-nav a:first-child:after,.current-service-nav .primary-nav a:hover:after{content:"";position:absolute;inset-inline:5px;bottom:-17px;height:2px;background:#ce122d}
.current-service-nav .nav-icon{display:grid;place-items:center;height:24px;color:#203759}
.current-service-nav .nav-icon svg{width:23px;height:23px;fill:none;stroke:currentColor;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round}
.current-service-nav .menu-toggle{display:none;border:0;background:#071f4d;color:#fff;width:42px;height:42px;border-radius:7px;font-size:20px;cursor:pointer;margin-left:auto}
.request-clarity-inner{width:min(1540px,94%)!important;max-width:none!important}
.request-ticket-note{width:min(1540px,94%)!important;max-width:none!important;margin-top:16px!important}
main.wrap{width:min(1540px,94%)!important;max-width:none!important;margin-top:16px!important}
.panel{padding:22px 24px!important;border-radius:14px!important;color:var(--ufm-text)}
body{color:var(--ufm-text)}
.request-clarity-hero h1{font-size:40px!important;line-height:1.3!important;font-weight:900!important;letter-spacing:-.015em!important}
.request-clarity-hero p{font-size:14px!important;line-height:1.9!important;font-weight:500!important;max-width:820px!important}
.request-ticket-note{font-size:12px!important;line-height:1.75!important;font-weight:650!important}
main.wrap{font-size:14px!important}
.panel .section-title,.section-title{font-size:18px!important;line-height:1.45!important;font-weight:900!important;letter-spacing:-.01em!important}
.section-title .num,.panel .num{font-size:11px!important;line-height:26px!important;font-weight:900!important}
.panel label,.field label,label{font-size:12px!important;line-height:1.45!important;font-weight:800!important;color:#102b55!important}
.panel input,.panel select,.panel textarea,.field input,.field select,.field textarea,input,select,textarea{font-size:13px!important;line-height:1.5!important;font-weight:600!important;color:#18345e!important}
.panel input::placeholder,.panel textarea::placeholder,input::placeholder,textarea::placeholder{font-size:12px!important;font-weight:500!important;color:#95a3b6!important}
.hint,.helper,.help-text,.muted,.note{font-size:11px!important;line-height:1.7!important;font-weight:500!important;color:var(--ufm-muted)!important}
.request-selector-row{display:grid!important;grid-template-columns:1.2fr 1fr 1fr!important;gap:12px!important;align-items:end!important;direction:rtl!important}
.request-selector-customer label{display:block!important;margin-bottom:6px!important}
.customer-kind-inline{height:42px;display:grid;grid-template-columns:1fr 1fr;gap:6px}
.customer-kind-inline .customer-kind-card{height:42px;border:1px solid #cdd8e6;border-radius:7px;background:#fff;color:#17345e;display:flex;align-items:center;justify-content:center;gap:6px;font:850 11px Cairo;line-height:1.2;white-space:nowrap;cursor:pointer}
.customer-kind-inline .customer-kind-card.active{border:1.5px solid #e3132c;color:#e3132c;background:#fff8f9;box-shadow:inset 0 0 0 1px rgba(227,19,44,.04)}
.request-selector-note{margin-top:8px!important;text-align:right!important}
.new-customer-panel{display:none}.new-customer-panel.show{display:block}
.new-customer-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.new-customer-grid .span2{grid-column:span 2}.new-customer-actions{display:flex;align-items:center;gap:9px;margin-top:10px}.location-btn{height:42px;border:1px solid #cdd8e6;background:#fff;color:#0b2854;border-radius:7px;padding:0 16px;font:800 11px Cairo;cursor:pointer}.new-location-status{font-size:10px;color:#6f819c}.new-map{display:none;height:180px;border:1px solid #dce4ee;border-radius:9px;overflow:hidden;margin-top:10px}.new-map.show{display:block}.new-map iframe{width:100%;height:100%;border:0}
button,.button,.btn{font-weight:800}
.submit-reference-row{display:flex;align-items:center;justify-content:space-between;gap:24px;margin:18px 0 24px;padding:18px 0 0;border-top:1px solid #eef2f7;direction:ltr}
.submit-reference-row .submit{width:190px;min-width:190px;height:64px;margin:0;border-radius:14px;font-size:16px!important;line-height:1.2!important;font-weight:900!important;box-shadow:none;direction:rtl}
.ticket-promise-inline{margin:0;color:#667b98;font-size:12px!important;line-height:1.75!important;text-align:right;font-weight:600;direction:rtl}.ticket-promise-inline .check{color:#57789e;margin-left:4px}
@media(max-width:1080px){.current-service-nav .primary-nav{display:none}.current-service-nav .menu-toggle{display:grid;place-items:center}.request-selector-row{grid-template-columns:1fr 1fr!important}.request-selector-customer{grid-column:1/-1}.new-customer-grid{grid-template-columns:1fr 1fr}.new-customer-grid .span2{grid-column:1/-1}}
@media(max-width:700px){.current-service-nav .lang{display:none}.request-selector-row,.new-customer-grid{grid-template-columns:1fr!important}.request-selector-customer,.new-customer-grid .span2{grid-column:auto}.request-clarity-hero h1{font-size:28px!important}.request-clarity-hero p{font-size:12px!important}.new-customer-actions{align-items:stretch;flex-direction:column}.location-btn{width:100%}}
@media(max-width:620px){.submit-reference-row{flex-direction:column-reverse;align-items:stretch;gap:12px;direction:rtl}.submit-reference-row .submit{width:100%;min-width:0;height:54px}.ticket-promise-inline{text-align:center}.customer-kind-inline .customer-kind-card{font-size:10.5px}}
</style>
HTML;
        $html = str_replace('</head>', $styles.'</head>', $html);

        $oldHeader = '<header class="top"><div class="wrap"><a href="'.route('public.home').'"><img class="logo" src="/brand/unifco-logo-v2.webp" alt="UNIFCO"></a><a class="back" href="'.route('public.home').'">العودة للرئيسية ←</a></div></header>';
        $icons = [
            'home' => '<svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5M9 21v-7h6v7"/></svg>',
            'about' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg>',
            'services' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M4 12a8 8 0 0 1 .7-3.3M7 5.2A8 8 0 0 1 12 4m5 1.2A8 8 0 0 1 20 12m-.7 3.3A8 8 0 0 1 12 20m-5-1.2A8 8 0 0 1 4 12"/></svg>',
            'industries' => '<svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V9l5 3V9l5 3V5h4v16"/></svg>',
            'projects' => '<svg viewBox="0 0 24 24"><path d="M4 21V7h6v14M10 21V3h6v18M16 21v-9h4v9"/></svg>',
            'clients' => '<svg viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M2 21v-2a6 6 0 0 1 12 0v2M12 21v-2a6 6 0 0 1 10-4.5"/></svg>',
            'careers' => '<svg viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/></svg>',
            'contact' => '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
        ];
        $item = static fn (string $href, string $label, string $icon): string => '<a href="'.$href.'"><span class="nav-icon">'.$icon.'</span><span>'.$label.'</span></a>';
        $nav = $item(route('public.home'), 'الرئيسية', $icons['home']).$item(route('public.home').'#about', 'تعرف علينا', $icons['about']).$item(route('public.home').'#services', 'الخدمات', $icons['services']).$item(route('public.home').'#industries', 'القطاعات', $icons['industries']).$item(route('public.home').'#projects', 'المشاريع', $icons['projects']).$item(route('public.home').'#clients', 'العملاء', $icons['clients']).$item(route('public.home').'#careers', 'الوظائف', $icons['careers']).$item(route('public.home').'#contact', 'تواصل معنا', $icons['contact']);
        $newHeader = '<header class="current-service-nav"><div class="nav-wrap"><a class="brand-link" href="'.route('public.home').'"><span class="site-logo-frame"><img class="site-logo" src="'.route('brand.logo').'" alt="UNIFCO"></span><span class="brand-copy"><strong>UNIFCO</strong><small>ONE FACILITY SHOP</small></span></a><div class="nav-actions"><a class="lang" href="'.route('public.home.en').'">EN</a><a class="request-btn" href="'.route('public.current-maintenance').'">طلب خدمة</a></div><nav class="primary-nav" aria-label="التنقل الرئيسي">'.$nav.'</nav><button class="menu-toggle" type="button" aria-label="القائمة">☰</button></div></header>';
        if (str_contains($html, $oldHeader)) $html = str_replace($oldHeader, $newHeader, $html);

        $oldSelector = '<section class="panel"><div class="service-line"><div class="field"><label>نوع الخدمة المطلوبة</label><select id="service-type"><option value="maintenance" selected>صيانة</option><option value="quotation">عرض سعر</option><option value="consultation">استشارة فنية</option></select></div><div class="field"><label>نوع الطلب</label><select id="service-subtype"><option value="routine" selected>صيانة عادية</option><option value="urgent">صيانة طارئة</option></select></div><div class="hint">يتم عرض نموذج صيانة عادية حاليًا. بقية النماذج سيتم تجهيزها لاحقًا.</div></div><div class="future" id="future-box">هذا النوع تم اعتماده للتطوير اللاحق. لم يتم تغيير أي نموذج آخر بالموقع.</div></section>';
        $newSelector = '<section class="panel request-selector-panel"><div class="request-selector-row"><div class="request-selector-customer"><label>نوع العميل</label><div class="customer-kind-inline"><button type="button" class="customer-kind-card active" data-customer-kind="current">✓ عميل حالي</button><button type="button" class="customer-kind-card" data-customer-kind="new">＋ عميل جديد</button></div></div><div class="field"><label>نوع الخدمة المطلوبة</label><select id="service-type"><option value="maintenance" selected>صيانة</option><option value="quotation">عرض سعر</option><option value="consultation">استشارة فنية</option></select></div><div class="field"><label>نوع الطلب</label><select id="service-subtype"><option value="routine" selected>صيانة عادية</option><option value="urgent">صيانة طارئة</option></select></div></div><div class="hint request-selector-note">اختر نوع العميل أولًا، ثم الخدمة ونوع الطلب. يتم عرض النموذج المناسب أسفل هذا السطر.</div><div class="future" id="future-box">هذا النوع تم اعتماده للتطوير اللاحق. لم يتم تغيير أي نموذج آخر بالموقع.</div></section>';
        if (str_contains($html, $oldSelector)) $html = str_replace($oldSelector, $newSelector, $html);

        $currentCustomerMarker = '<section class="panel"><h2 class="section-title"><span class="num">1</span> بيانات العميل الحالي</h2>';
        $newCustomerPanel = <<<'HTML'
<section class="panel new-customer-panel" id="new-customer-panel">
<h2 class="section-title"><span class="num">1</span> بيانات العميل الجديد</h2>
<div class="new-customer-grid">
<div class="field"><label>اسم المنشأة / الشركة <span class="req">*</span></label><input id="new_company_name" placeholder="اسم المنشأة"></div>
<div class="field"><label>اسم مسؤول التواصل <span class="req">*</span></label><input id="new_responsible_person" placeholder="الاسم الكامل"></div>
<div class="field"><label>رقم الجوال <span class="req">*</span></label><input id="new_mobile" inputmode="tel" placeholder="+966 5X XXX XXXX"></div>
<div class="field"><label>البريد الإلكتروني <span class="req">*</span></label><input id="new_email" type="email" placeholder="name@company.com"></div>
<div class="field"><label>المنطقة <span class="req">*</span></label><select id="new_region"><option value="">اختر المنطقة</option></select></div>
<div class="field"><label>المدينة <span class="req">*</span></label><select id="new_city" disabled><option value="">اختر المدينة</option></select></div>
<div class="field"><label>الحي</label><input id="new_district" placeholder="اسم الحي"></div>
<div class="field span2"><label>العنوان المختصر / الوطني</label><input id="new_address" placeholder="العنوان أو رقم العنوان المختصر"></div>
</div>
<div class="new-customer-actions"><button type="button" class="location-btn" id="new-use-location">⌖ استخدم موقعي الحالي</button><span class="new-location-status" id="new-location-status">يمكن تحديد الموقع لتسريع تسجيل موقع الخدمة.</span></div>
<div class="new-map" id="new-map"><iframe id="new-map-frame" title="موقع المنشأة" src="about:blank"></iframe></div>
<div class="hint" style="margin-top:10px">سيتم فحص الجوال والبريد تلقائيًا عبر مسار CRM الحالي لتجنب إنشاء سجل مكرر، ثم ربط الطلب بالـLead/Opportunity المناسب.</div>
</section>
HTML;
        if (str_contains($html, $currentCustomerMarker)) {
            $html = str_replace($currentCustomerMarker, $newCustomerPanel.'<section class="panel" id="current-customer-panel"><h2 class="section-title"><span class="num">1</span> بيانات العميل الحالي</h2>', $html);
        }

        $submit = '<button class="submit" type="submit">إرسال طلب الخدمة</button>';
        $replacement = '<div class="submit-reference-row"><button class="submit" type="submit">إرسال الطلب</button><p class="ticket-promise-inline"><span class="check">✓</span> سيتم إصدار رقم تذكرة مرجعي فور إرسال الطلب.</p></div>';
        if (str_contains($html, $submit)) $html = str_replace($submit, $replacement, $html);

        $script = <<<'HTML'
<script id="unifco-new-customer-flow">
(()=>{
const regions={
'الرياض':['الرياض','الخرج','الدرعية','الدوادمي','المجمعة','شقراء','القويعية','وادي الدواسر','الزلفي'],
'مكة المكرمة':['مكة المكرمة','جدة','الطائف','رابغ','القنفذة','الليث','خليص'],
'المدينة المنورة':['المدينة المنورة','ينبع','العلا','بدر','خيبر'],
'المنطقة الشرقية':['الدمام','الخبر','الظهران','الجبيل','الأحساء','القطيف','رأس تنورة','حفر الباطن'],
'القصيم':['بريدة','عنيزة','الرس','البكيرية','المذنب'],
'عسير':['أبها','خميس مشيط','بيشة','محايل عسير','النماص'],
'تبوك':['تبوك','ضباء','الوجه','أملج','تيماء'],
'حائل':['حائل','بقعاء','الغزالة'],
'الحدود الشمالية':['عرعر','رفحاء','طريف'],
'جازان':['جازان','صبيا','أبو عريش','صامطة','بيش'],
'نجران':['نجران','شرورة','حبونا'],
'الباحة':['الباحة','بلجرشي','المخواة','العقيق'],
'الجوف':['سكاكا','دومة الجندل','القريات','طبرجل']};
const $=id=>document.getElementById(id), form=$('maintenance-form');
const currentPanel=$('current-customer-panel'), newPanel=$('new-customer-panel');
const currentSections=[currentPanel,$('contract-section'),$('site-section'),$('asset-section')].filter(Boolean);
const region=$('new_region'),city=$('new_city');
Object.keys(regions).forEach(r=>region?.add(new Option(r,r)));
region?.addEventListener('change',()=>{city.innerHTML='<option value="">اختر المدينة</option>';(regions[region.value]||[]).forEach(c=>city.add(new Option(c,c)));city.disabled=!region.value;syncNew();});
city?.addEventListener('change',syncNew);
function disableWithin(el,disabled){if(!el)return;el.querySelectorAll('input,select,textarea,button').forEach(x=>{if(x.type!=='submit')x.disabled=disabled})}
function syncNew(){
 if(!newPanel?.classList.contains('show'))return;
 const company=$('new_company_name')?.value||'', person=$('new_responsible_person')?.value||'', mobile=$('new_mobile')?.value||'', email=$('new_email')?.value||'', district=$('new_district')?.value||'', address=$('new_address')?.value||'';
 const mapping={company_name:company,responsible_person:person,mobile:mobile,email:email,site_city:city?.value||'',site_area:region?.value||'',site_name:company||'موقع العميل الجديد',site_address:[district,address].filter(Boolean).join(' - '),asset_type:'GENERAL'};
 Object.entries(mapping).forEach(([name,val])=>{let input=form?.querySelector('input[type="hidden"][data-new-field="'+name+'"]');if(!input&&form){input=document.createElement('input');input.type='hidden';input.name=name;input.dataset.newField=name;form.appendChild(input)}if(input)input.value=val});
}
['new_company_name','new_responsible_person','new_mobile','new_email','new_district','new_address'].forEach(id=>$(id)?.addEventListener('input',syncNew));
function setKind(kind){
 document.querySelectorAll('[data-customer-kind]').forEach(b=>b.classList.toggle('active',b.dataset.customerKind===kind));
 const isNew=kind==='new';newPanel?.classList.toggle('show',isNew);currentSections.forEach(s=>{s.style.display=isNew?'none':'';disableWithin(s,isNew)});
 form?.querySelectorAll('[data-new-field]').forEach(x=>x.disabled=!isNew);
 ['new_company_name','new_responsible_person','new_mobile','new_email','new_region','new_city'].forEach(id=>{const x=$(id);if(x)x.required=isNew});
 if(isNew)syncNew();
}
document.querySelectorAll('[data-customer-kind]').forEach(b=>b.addEventListener('click',()=>setKind(b.dataset.customerKind)));
$('new-use-location')?.addEventListener('click',()=>{
 const status=$('new-location-status');if(!navigator.geolocation){status.textContent='المتصفح لا يدعم تحديد الموقع.';return}
 status.textContent='جارٍ تحديد الموقع…';navigator.geolocation.getCurrentPosition(pos=>{const lat=pos.coords.latitude.toFixed(6),lon=pos.coords.longitude.toFixed(6);$('latitude').value=lat;$('longitude').value=lon;const frame=$('new-map-frame');frame.src='https://www.openstreetmap.org/export/embed.html?bbox='+(+lon-.02)+'%2C'+(+lat-.015)+'%2C'+(+lon+.02)+'%2C'+(+lat+.015)+'&layer=mapnik&marker='+lat+'%2C'+lon;$('new-map').classList.add('show');status.textContent='تم تحديد الموقع بنجاح.';},()=>status.textContent='تعذر الوصول للموقع. يمكنك إكمال العنوان يدويًا.',{enableHighAccuracy:true,timeout:10000});
});
form?.addEventListener('submit',e=>{if(newPanel?.classList.contains('show')){syncNew();const required=['new_company_name','new_responsible_person','new_mobile','new_email','new_region','new_city'];if(required.some(id=>!$(id)?.value)){e.preventDefault();alert('يرجى استكمال بيانات العميل الجديد المطلوبة.');}}});
setKind('current');
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);

        $response->setContent($html);
        return $response;
    }
}
