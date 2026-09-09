<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeEmergencyShortcutPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        if ($request->routeIs('public.home')) {
            $lockedEmergencyUrl = route('public.current-maintenance', ['emergency' => 1]);
            $lockedQuotationUrl = route('public.current-maintenance', ['quotation' => 1]);

            $html = str_replace(
                'href="/request-service">طلب صيانة طارئة</a>',
                'href="'.e($lockedEmergencyUrl).'">طلب صيانة طارئة</a>',
                $html
            );

            $html = preg_replace(
                '~href=("|\')/emergency-maintenance\1([^>]*>\s*طلب صيانة طارئة\s*</a>)~u',
                'href="'.e($lockedEmergencyUrl).'"$2',
                $html
            ) ?? $html;

            $html = preg_replace_callback(
                '~<a\b([^>]*)>(\s*(?:<[^>]+>\s*)*اطلب عرض سعر(?:\s*<[^>]+>)*\s*)</a>~u',
                static function (array $m) use ($lockedQuotationUrl): string {
                    $attrs = preg_replace('~\s+href=("|\')[^"\']*\1~i', '', $m[1]) ?? $m[1];
                    return '<a href="'.e($lockedQuotationUrl).'"'.$attrs.'>'.$m[2].'</a>';
                },
                $html
            ) ?? $html;

            $html = preg_replace(
                '~href=("|\')(?:/request-quote|/request-service\?[^"\']*quote[^"\']*)\1([^>]*>[^<]*اطلب عرض سعر[^<]*</a>)~u',
                'href="'.e($lockedQuotationUrl).'"$2',
                $html
            ) ?? $html;

            $emergencyUrlJson = json_encode($lockedEmergencyUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $homeFix = <<<HTML
<script id="unifco-home-emergency-shortcut-runtime-fix">
(()=>{
 const emergencyUrl={$emergencyUrlJson};
 const isEmergencyAnchor=a=>{
   if(!a) return false;
   const text=(a.textContent||'').replace(/\s+/g,' ').trim();
   return text.includes('طلب صيانة طارئة');
 };
 const apply=()=>{
   document.querySelectorAll('a').forEach(a=>{
     if(isEmergencyAnchor(a)) a.setAttribute('href', emergencyUrl);
   });
 };
 const forceNavigate=event=>{
   const a=event.target?.closest?.('a');
   if(!isEmergencyAnchor(a)) return;
   event.preventDefault();
   event.stopPropagation();
   if(typeof event.stopImmediatePropagation==='function') event.stopImmediatePropagation();
   window.location.assign(emergencyUrl);
 };
 const start=()=>{
   apply();
   document.addEventListener('click',forceNavigate,true);
   document.addEventListener('touchend',forceNavigate,true);
   const observer=new MutationObserver(apply);
   observer.observe(document.documentElement,{childList:true,subtree:true});
   [0,50,150,350,800,1500,3000].forEach(ms=>setTimeout(apply,ms));
   window.addEventListener('pageshow',apply,{passive:true});
 };
 if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start,{once:true}); else start();
})();
</script>
HTML;
            $html = str_replace('</body>', $homeFix.'</body>', $html);

            $response->setContent($html);
            return $response;
        }

        if (! $request->routeIs('public.current-maintenance')) {
            return $response;
        }

        if ($request->boolean('emergency')) {
            $lock = <<<'HTML'
<style id="unifco-locked-emergency-request-style">
#service-type:disabled,#service-subtype:disabled{opacity:1!important;background:#f1f5fa!important;color:#071f4d!important;border-color:#b7c9df!important;cursor:not-allowed!important;font-weight:900!important}
.uf-emergency-entry-note{margin:0 0 12px;padding:10px 13px;border:1px solid #f2b3bc;border-radius:9px;background:#fff5f6;color:#a91027;font-size:10px;font-weight:800;line-height:1.7}
</style>
<script id="unifco-locked-emergency-request-script">
(()=>{
 const apply=()=>{
   const service=document.getElementById('service-type');
   const subtype=document.getElementById('service-subtype');
   const intent=document.querySelector('input[name="request_intent"]');
   const hiddenSubtype=document.querySelector('input[name="request_subtype"]');
   const category=document.querySelector('input[name="service_category"]');
   const urgency=document.querySelector('input[name="urgency"]');
   if(intent) intent.value='SERVICE_REQUEST';
   if(hiddenSubtype) hiddenSubtype.value='URGENT_MAINTENANCE';
   if(category) category.value='MAINTENANCE';
   if(urgency) urgency.value='EMERGENCY';
   if(service){
     if(service.value!=='maintenance'){
       service.value='maintenance';
       service.dispatchEvent(new Event('change',{bubbles:true}));
     }
   }
   if(subtype){
     if(subtype.value!=='urgent'){
       subtype.value='urgent';
       subtype.dispatchEvent(new Event('change',{bubbles:true}));
     }
   }
   queueMicrotask(()=>{
     if(service){service.value='maintenance';service.disabled=true;service.setAttribute('aria-disabled','true');}
     if(subtype){subtype.value='urgent';subtype.disabled=true;subtype.setAttribute('aria-disabled','true');}
   });
   const workspace=document.getElementById('uf-request-workspace');
   if(workspace && !document.getElementById('uf-emergency-entry-note')){
     const note=document.createElement('div');
     note.id='uf-emergency-entry-note';
     note.className='uf-emergency-entry-note';
     note.textContent='تم فتح النموذج كطلب صيانة طارئة من الصفحة الرئيسية. نوع الطلب محدد ولا يمكن تغييره؛ يرجى استكمال بيانات الطلب.';
     workspace.before(note);
   }
 };
 const start=()=>{ apply(); [120,350,800,1500].forEach(ms=>setTimeout(apply,ms)); };
 if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start,{once:true}); else start();
})();
</script>
HTML;
            $html = str_replace('</body>', $lock.'</body>', $html);
            $response->setContent($html);
            return $response;
        }

        if ($request->boolean('quotation')) {
            $lock = <<<'HTML'
<style id="unifco-locked-quotation-request-style">
#service-type:disabled{opacity:1!important;background:#f1f5fa!important;color:#071f4d!important;border-color:#b7c9df!important;cursor:not-allowed!important;font-weight:900!important}
.uf-quotation-entry-note{margin:0 0 12px;padding:10px 13px;border:1px solid #c8dcf5;border-radius:9px;background:#f4f9ff;color:#0c417e;font-size:10px;font-weight:800;line-height:1.7}
</style>
<script id="unifco-locked-quotation-request-script">
(()=>{
 const allowedText=['قطع غيار','زيارة فنية','عقد صيانة'];
 const allowedValue=v=>/spare|parts|visit|contract|quotation/i.test(v||'');
 const syncHidden=()=>{
   const subtype=document.getElementById('service-subtype');
   if(!subtype)return;
   const label=(subtype.selectedOptions[0]?.textContent||'').trim();
   const hiddenSubtype=document.querySelector('input[name="request_subtype"]');
   const intent=document.querySelector('input[name="request_intent"]');
   const category=document.querySelector('input[name="service_category"]');
   const urgency=document.querySelector('input[name="urgency"]');
   if(intent)intent.value='QUOTATION';
   if(urgency)urgency.value='NORMAL';
   if(/قطع غيار/.test(label)){ if(hiddenSubtype)hiddenSubtype.value='SPARE_PARTS_QUOTE'; if(category)category.value='SPARE_PARTS'; }
   else if(/زيارة فنية/.test(label)){ if(hiddenSubtype)hiddenSubtype.value='TECHNICAL_VISIT'; if(category)category.value='TECHNICAL_VISIT'; }
   else if(/عقد صيانة/.test(label)){ if(hiddenSubtype)hiddenSubtype.value='MAINTENANCE_CONTRACT_QUOTE'; if(category)category.value='MAINTENANCE_CONTRACT'; }
 };
 const restrictSubtype=()=>{
   const subtype=document.getElementById('service-subtype');
   if(!subtype)return false;
   [...subtype.options].forEach(o=>{
     const text=(o.textContent||'').trim();
     const keep=allowedText.some(t=>text.includes(t))||allowedValue(o.value);
     o.hidden=!keep;
     o.disabled=!keep;
   });
   const available=[...subtype.options].filter(o=>!o.disabled&&!o.hidden);
   if(available.length && !available.includes(subtype.selectedOptions[0])){
     subtype.value=available[0].value;
     subtype.dispatchEvent(new Event('change',{bubbles:true}));
   }
   syncHidden();
   return available.length>0;
 };
 const apply=()=>{
   const service=document.getElementById('service-type');
   const subtype=document.getElementById('service-subtype');
   if(service){
     service.disabled=false;
     if(service.value!=='quotation'){
       service.value='quotation';
       service.dispatchEvent(new Event('change',{bubbles:true}));
     }
   }
   setTimeout(()=>{
     restrictSubtype();
     if(service){service.value='quotation';service.disabled=true;service.setAttribute('aria-disabled','true');}
     if(subtype){subtype.disabled=false;subtype.removeAttribute('aria-disabled');}
     const workspace=document.getElementById('uf-request-workspace');
     if(workspace && !document.getElementById('uf-quotation-entry-note')){
       const note=document.createElement('div');
       note.id='uf-quotation-entry-note';
       note.className='uf-quotation-entry-note';
       note.textContent='تم فتح النموذج كطلب عرض سعر. نوع الخدمة ثابت، ويمكنك اختيار نوع عرض السعر: قطع غيار، زيارة فنية، أو عقد صيانة.';
       workspace.before(note);
     }
   },20);
 };
 document.addEventListener('change',e=>{if(e.target?.id==='service-subtype')syncHidden()});
 const start=()=>{apply();[120,350,800,1500].forEach(ms=>setTimeout(apply,ms));};
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
</script>
HTML;
            $html = str_replace('</body>', $lock.'</body>', $html);
            $response->setContent($html);
        }

        return $response;
    }
}
