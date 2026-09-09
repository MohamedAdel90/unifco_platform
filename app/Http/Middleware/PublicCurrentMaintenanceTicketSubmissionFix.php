<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenanceTicketSubmissionFix
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
<script id="unifco-current-maintenance-ticket-submission-fix-v1">
(()=>{
  const normalize=()=>{
    const form=document.getElementById('maintenance-form');
    const visit=document.getElementById('uf-visit-reception');
    if(!form||!visit)return false;

    const date=visit.querySelector('input[name="visit_date"],input[name="requested_date"]');
    const from=visit.querySelector('input[name="visit_from_time"],input[name="requested_time"]');
    if(date) date.name='requested_date';
    if(from) from.name='requested_time';

    form.querySelectorAll('input[name="requested_date"],input[name="requested_time"]').forEach(el=>{
      if(!visit.contains(el)) el.removeAttribute('name');
    });

    form.action='/service-requests';
    form.method='post';
    return true;
  };

  const ensureBeforeSubmit=e=>{
    normalize();
    const form=e.target;
    if(form?.id!=='maintenance-form')return;
    const date=form.querySelector('#uf-visit-reception input[name="requested_date"]');
    const time=form.querySelector('#uf-visit-reception input[name="requested_time"]');
    if(date&&!date.value){e.preventDefault();date.reportValidity();return;}
    if(time&&!time.value){e.preventDefault();time.reportValidity();}
  };

  document.addEventListener('submit',ensureBeforeSubmit,true);
  if(normalize())return;
  let attempts=0;
  const timer=setInterval(()=>{attempts++;if(normalize()||attempts>=80)clearInterval(timer);},50);
})();
</script>
HTML;

        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
