<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicUnifiedTicketSubmissionPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="maintenance-form"')) {
            return $response;
        }

        $script = <<<'HTML'
<script id="unifco-unified-ticket-submit-v2">
(()=>{
  const $=id=>document.getElementById(id);
  const form=$('maintenance-form');
  if(!form)return;

  const ensureHidden=(name,value)=>{
    let el=form.querySelector(`input[name="${name}"]`);
    if(!el){el=document.createElement('input');el.type='hidden';el.name=name;form.appendChild(el)}
    el.value=value??'';
    return el;
  };

  const firstVisibleValue=selector=>{
    const els=[...form.querySelectorAll(selector)];
    const el=els.find(x=>x.offsetParent!==null && String(x.value||'').trim()) || els.find(x=>String(x.value||'').trim());
    return el?String(el.value||'').trim():'';
  };

  const syncTicketFields=()=>{
    const service=$('service-type')?.value||'maintenance';
    const subtype=$('service-subtype')?.value||'routine';
    let intent='SERVICE_REQUEST', requestSubtype='ROUTINE_MAINTENANCE', category='MAINTENANCE', urgency='NORMAL', serviceOther='';

    if(service==='maintenance'){
      intent='SERVICE_REQUEST';
      if(subtype==='urgent') { requestSubtype='URGENT_MAINTENANCE'; urgency='EMERGENCY'; }
      else requestSubtype='ROUTINE_MAINTENANCE';
    } else if(service==='quotation'){
      intent='QUOTATION'; category='QUOTATION';
      if(subtype==='contract') requestSubtype='MAINTENANCE_CONTRACT_QUOTE';
      else { requestSubtype='SPARE_PARTS_QUOTE'; if(subtype==='visit') serviceOther='زيارة فنية'; }
    } else {
      intent='CONSULTATION'; category='CONSULTATION'; requestSubtype='TECHNICAL_CONSULTATION';
    }

    ensureHidden('request_intent',intent);
    ensureHidden('request_subtype',requestSubtype);
    ensureHidden('service_category',category);
    ensureHidden('urgency',urgency);
    if(serviceOther) ensureHidden('service_other',serviceOther);

    const visibleDetails=firstVisibleValue('#uf-detail-fields textarea, #uf-detail-fields input[type="text"], textarea[name="details"]');
    const namedDetails=form.querySelector('[name="details"]');
    if(!namedDetails || !String(namedDetails.value||'').trim()) ensureHidden('details',visibleDetails||'تفاصيل الطلب');

    const date=firstVisibleValue('#uf-visit-reception input[type="date"], input[name="requested_date"]');
    const time=firstVisibleValue('#uf-visit-reception input[type="time"], input[name="requested_time"]');
    ensureHidden('requested_date',date||new Date().toISOString().slice(0,10));
    ensureHidden('requested_time',time||'09:00');

    form.action='/service-requests';
    form.method='post';
    form.enctype='multipart/form-data';
  };

  $('service-type')?.addEventListener('change',()=>setTimeout(syncTicketFields,0));
  $('service-subtype')?.addEventListener('change',()=>setTimeout(syncTicketFields,0));

  form.addEventListener('submit',e=>{
    syncTicketFields();
    if(!form.checkValidity()){
      e.preventDefault();
      form.reportValidity();
      return;
    }
    // Prevent legacy submit listeners from cancelling a valid unified request.
    // Do not preventDefault here: the browser continues with the normal POST.
    e.stopImmediatePropagation();
  },true);

  syncTicketFields();
})();
</script>
HTML;

        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
