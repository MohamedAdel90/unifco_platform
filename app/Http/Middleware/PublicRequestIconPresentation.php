<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicRequestIconPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, '<html') || str_contains($html, 'unifco-public-request-icons-v1')) {
            return $response;
        }

        $payload = <<<'HTML'
<style id="unifco-public-request-icons-v1">
.uf-title-icon,.uf-label-icon{position:absolute!important;z-index:2!important;width:16px!important;height:16px!important;min-width:16px!important;max-width:16px!important;color:#2477c9!important;pointer-events:none!important;overflow:visible!important}
.uf-title-icon{width:18px!important;height:18px!important;min-width:18px!important;max-width:18px!important}
.uf-title-icon svg,.uf-label-icon svg,.detail-icon svg{display:block!important;width:100%!important;height:100%!important;max-width:none!important}
.section-title.uf-icon-anchor,.field>label.uf-icon-anchor,.request-selector-panel label.uf-icon-anchor{position:relative!important}
</style>
<script id="unifco-public-request-icons-script-v1">
(()=>{
const NS='http://www.w3.org/2000/svg';
const paths={
 user:'<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
 id:'<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M13 10h5M13 14h5"/>',
 phone:'<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"/>',
 mail:'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
 map:'<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
 mapcheck:'<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><path d="m9 10 2 2 4-4"/>',
 mapline:'<path d="m9 18-6 3V6l6-3 6 3 6-3v15l-6 3-6-3Z"/><path d="M9 3v15M15 6v15"/>',
 file:'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h6"/>',
 signature:'<path d="M3 17c3-5 5-5 6-1 1 3 3 3 5 0 2-3 4-2 7-1"/><path d="M5 7h14M5 11h8"/>',
 shield:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
 building:'<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 7h2M14 7h2M8 11h2M14 11h2M8 15h2M14 15h2M10 21v-3h4v3"/>',
 settings:'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V21h-4v-.08A1.7 1.7 0 0 0 8.94 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.52-1.03H3v-4h.08A1.7 1.7 0 0 0 4.6 8.94a1.7 1.7 0 0 0-.34-1.88L4.2 7l2.83-2.83.06.06A1.7 1.7 0 0 0 8.97 4.6 1.7 1.7 0 0 0 10 3.08V3h4v.08A1.7 1.7 0 0 0 15.06 4.6a1.7 1.7 0 0 0 1.88-.34L17 4.2 19.83 7l-.06.06a1.7 1.7 0 0 0-.34 1.88A1.7 1.7 0 0 0 20.92 10H21v4h-.08A1.7 1.7 0 0 0 19.4 15Z"/>',
 package:'<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 7 9 5 9-5M3 7v10l9 5 9-5V7M12 12v10"/>',
 calendar:'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/><circle cx="12" cy="15" r="2"/><path d="m12 13v2l1 1"/>',
 alert:'<path d="M10.3 2.9 1.8 17a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 2.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
 clip:'<path d="m21.4 11.6-8.9 8.9a6 6 0 0 1-8.5-8.5l9.6-9.6a4 4 0 0 1 5.7 5.7l-9.6 9.6a2 2 0 0 1-2.8-2.8l8.9-8.9"/>',
 clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 tag:'<path d="M20.6 13.6 11 23.2 1 13.2V3h10.2l9.4 9.4a1.7 1.7 0 0 1 0 1.2Z" transform="scale(.9) translate(1 0)"/><circle cx="7.5" cy="7.5" r="1.5"/>',
 barcode:'<path d="M3 5v14M7 5v14M10 5v14M14 5v14M18 5v14M21 5v14"/>',
 pencil:'<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
 list:'<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>'
};
const iconMap={
'بيانات العميل':'user','بيانات العميل الحالي':'user','بيانات العميل الجديد':'user','Customer Information':'user','Existing Customer Information':'user','New Customer Information':'user',
'بيانات العقد':'file','Contract Information':'file','الموقع والتواصل':'map','بيانات الموقع والتواصل':'map','Site & Contact':'map','Site & Contact Information':'map',
'المعدة وبياناتها':'settings','بيانات المعدة':'settings','Equipment Information':'settings','موعد الزيارة':'calendar','Visit Schedule':'calendar',
'وصف سريع للعطل':'alert','تفاصيل المشكلة':'alert','Brief Issue Description':'alert','Issue Details':'alert','مرفقات الطلب':'clip','المرفقات':'clip','Request Attachments':'clip','Attachments':'clip',
'تفاصيل قطع الغيار والمرفقات':'package','قطع الغيار':'package','Spare Parts & Attachments Details':'package','Spare Parts':'package',
'نوع العميل':'user','Customer Type':'user','نوع الخدمة المطلوبة':'settings','Required Service':'settings','نوع الطلب':'file','Request Type':'file',
'رقم العميل':'id','Customer Number':'id','مسؤول التواصل':'user','اسم مسؤول التواصل':'user','Contact Person':'user','Contact Name':'user','رقم الجوال':'phone','Mobile Number':'phone','البريد الإلكتروني':'mail','Email Address':'mail',
'المدينة':'map','City':'map','العنوان المختصر':'mapline','العنوان المختصر / الوطني':'mapline','Short Address':'mapline','Short / National Address':'mapline','المنطقة':'map','Region':'map','الحي':'mapline','District':'mapline',
'رقم العقد':'file','Contract Number':'file','نوع / عنوان العقد':'signature','Contract Type / Title':'signature','حالة العقد':'shield','Contract Status':'shield',
'الموقع المسجل':'mapcheck','Registered Site':'mapcheck','اسم الموقع':'building','Site Name':'building','العنوان':'mapline','العنوان التفصيلي':'mapline','Address':'mapline','Detailed Address':'mapline','مسؤول الموقع':'user','Site Contact':'user','رقم التواصل':'phone','Contact Number':'phone',
'اسم المعدة':'settings','Equipment Name':'settings','نوع المعدة':'settings','Equipment Type':'settings','الشركة المصنعة':'building','Manufacturer':'building','الماركة':'tag','Brand':'tag','الموديل':'tag','Model':'tag','السيريال نمبر':'barcode','Serial Number':'barcode','رقم الأصل':'id','رقم الأصل إن وجد':'id','Asset Number':'id','Asset Number (if available)':'id','حالة المعدة':'shield','Equipment Status':'shield','الحالة التشغيلية':'shield','Operating Status':'shield',
'تاريخ الزيارة المطلوب':'calendar','Preferred Visit Date':'calendar','الوقت المطلوب':'clock','Preferred Time':'clock','الأولوية':'alert','Priority':'alert','وصف المشكلة':'pencil','Issue Description':'pencil',
'صور المشكلة':'clip','Issue Photos':'clip','صور المعدة':'clip','Equipment Photos':'clip','تقرير سابق (إن وجد)':'file','Previous Report (if available)':'file'
};
function cleanText(el){return (el.textContent||'').replace(/[\*]/g,'').replace(/^[\s\d٠-٩]+/,'').replace(/\s+/g,' ').trim()}
function svg(name){const s=document.createElementNS(NS,'svg');s.setAttribute('viewBox','0 0 24 24');s.setAttribute('fill','none');s.setAttribute('stroke','currentColor');s.setAttribute('stroke-width','1.8');s.setAttribute('stroke-linecap','round');s.setAttribute('stroke-linejoin','round');s.innerHTML=paths[name]||paths.list;return s}
function firstTextNode(el){return [...el.childNodes].find(n=>n.nodeType===3&&n.nodeValue.trim())||null}
function placeAbsoluteIcon(el,name,title=false){if(!el||el.dataset.ufIconDone)return;const textNode=firstTextNode(el);if(!textNode)return;el.dataset.ufIconDone='1';el.classList.add('uf-icon-anchor');const span=document.createElement('span');span.className=title?'uf-title-icon':'uf-label-icon';span.setAttribute('aria-hidden','true');span.appendChild(svg(name));el.appendChild(span);const position=()=>{const r=document.createRange();r.selectNodeContents(textNode);const tr=r.getBoundingClientRect(),er=el.getBoundingClientRect();if(!tr.width&&!tr.height)return;const size=title?18:16;const rtl=getComputedStyle(el).direction==='rtl';span.style.top=Math.max(0,tr.top-er.top+(tr.height-size)/2)+'px';span.style.left=(rtl?Math.max(0,tr.left-er.left-size-5):Math.min(Math.max(0,el.clientWidth-size),tr.right-er.left+5))+'px'};requestAnimationFrame(position);window.addEventListener('resize',position,{passive:true})}
function enhance(){
 document.querySelectorAll('.section-title').forEach(el=>{const key=cleanText(el),name=iconMap[key];if(name)placeAbsoluteIcon(el,name,true)});
 document.querySelectorAll('.field>label,.request-selector-panel label').forEach(el=>{const key=cleanText(el),name=iconMap[key];if(name)placeAbsoluteIcon(el,name,false)});
 document.querySelectorAll('.customer-detail').forEach(row=>{const label=row.querySelector('.detail-label'),box=row.querySelector('.detail-icon');if(!label||!box)return;const name=iconMap[cleanText(label)];if(!name||box.dataset.ufIconDone)return;box.dataset.ufIconDone='1';box.textContent='';box.appendChild(svg(name))});
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',enhance,{once:true});else enhance();
new MutationObserver(()=>enhance()).observe(document.documentElement,{childList:true,subtree:true});
})();
</script>
HTML;

        $html = str_replace('</body>', $payload.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
