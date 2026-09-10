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

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="maintenance-form"')) {
            return $response;
        }

        $script = <<<'HTML'
<script id="unifco-unified-ticket-submit-v3">
(()=>{
  const $=id=>document.getElementById(id);
  const form=$('maintenance-form');
  if(!form)return;

  const ensureHidden=(name,value)=>{
    let el=form.querySelector(`input[type="hidden"][name="${name}"]`);
    if(!el){el=document.createElement('input');el.type='hidden';el.name=name;form.appendChild(el)}
    el.value=value??'';
    return el;
  };

  const valueOf=(selector)=>{
    const els=[...form.querySelectorAll(selector)];
    const visible=els.find(x=>x.offsetParent!==null && String(x.value||'').trim());
    const any=els.find(x=>String(x.value||'').trim());
    return String((visible||any)?.value||'').trim();
  };

  const syncTicketFields=()=>{
    const service=$('service-type')?.value||'maintenance';
    const subtype=$('service-subtype')?.value||'routine';
    let intent='SERVICE_REQUEST', requestSubtype='ROUTINE_MAINTENANCE', category='MAINTENANCE', urgency='NORMAL', serviceOther='';

    if(service==='maintenance'){
      if(subtype==='urgent') { requestSubtype='URGENT_MAINTENANCE'; urgency='EMERGENCY'; }
    }else if(service==='quotation'){
      intent='QUOTATION'; category='QUOTATION';
      if(subtype==='contract') requestSubtype='MAINTENANCE_CONTRACT_QUOTE';
      else { requestSubtype='SPARE_PARTS_QUOTE'; if(subtype==='visit') serviceOther='زيارة فنية'; }
    }else{
      intent='CONSULTATION'; category='CONSULTATION'; requestSubtype='TECHNICAL_CONSULTATION';
    }

    ensureHidden('unified_public_request','1');
    ensureHidden('request_intent',intent);
    ensureHidden('request_subtype',requestSubtype);
    ensureHidden('service_category',category);
    ensureHidden('urgency',urgency);
    if(serviceOther) ensureHidden('service_other',serviceOther);

    const details=valueOf('#uf-detail-fields textarea,#uf-detail-fields input[type="text"],textarea[name="details"]');
    ensureHidden('details',details||'تفاصيل الطلب');

    const date=valueOf('#uf-visit-reception input[type="date"],input[name="requested_date"]');
    const time=valueOf('#uf-visit-reception input[type="time"],input[name="requested_time"]');
    ensureHidden('requested_date',date||new Date().toISOString().slice(0,10));
    ensureHidden('requested_time',time||'09:00');

    form.action='/service-requests';
    form.method='post';
    form.enctype='multipart/form-data';

    // Legacy presentation layers leave hidden controls marked required. They must not
    // prevent the unified form from reaching the server; server validation remains authoritative.
    form.querySelectorAll('[required]').forEach(el=>{
      const hidden=el.offsetParent===null || el.closest('.uf-old-workspace-source') || el.closest('.hidden');
      if(hidden) el.required=false;
    });
  };

  $('service-type')?.addEventListener('change',()=>setTimeout(syncTicketFields,0));
  $('service-subtype')?.addEventListener('change',()=>setTimeout(syncTicketFields,0));

  form.addEventListener('submit',e=>{
    if(form.dataset.ufNativeSubmitting==='1')return;
    e.preventDefault();
    e.stopImmediatePropagation();
    syncTicketFields();
    form.dataset.ufNativeSubmitting='1';
    // Bypass every legacy submit listener. The POST then reaches Laravel where all
    // required request data is normalized and validated before ticket issuance.
    HTMLFormElement.prototype.submit.call(form);
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
