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

            $html = str_replace(
                'href="/request-service">طلب صيانة طارئة</a>',
                'href="'.e($lockedEmergencyUrl).'">طلب صيانة طارئة</a>',
                $html
            );

            // Compatibility with any alternate rendering of the same CTA.
            $html = preg_replace(
                '~href=("|\')/emergency-maintenance\1([^>]*>\s*طلب صيانة طارئة\s*</a>)~u',
                'href="'.e($lockedEmergencyUrl).'"$2',
                $html
            ) ?? $html;

            $response->setContent($html);
            return $response;
        }

        if (! $request->routeIs('public.current-maintenance') || ! $request->boolean('emergency')) {
            return $response;
        }

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
   // Lock only after the unified workspace has received the change events.
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
 const start=()=>{
   apply();
   [120,350,800,1500].forEach(ms=>setTimeout(apply,ms));
 };
 if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start,{once:true}); else start();
})();
</script>
HTML;

        $html = str_replace('</body>', $lock.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
