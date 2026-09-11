<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCustomerVerificationStatePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $style = <<<'HTML'
<style id="unifco-customer-verification-state-v3">
#customer-status.bad,
#customer-status.unifco-not-verified{
    display:flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:8px!important;
    min-height:40px!important;
    padding:8px 14px!important;
    border:1px solid #ef4444!important;
    border-radius:10px!important;
    background:#fff1f2!important;
    color:#dc2626!important;
    font-weight:800!important;
    white-space:nowrap!important;
}
#customer-status .unifco-status-x{
    width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
    background:#dc2626;color:#fff;font-size:20px;line-height:1;font-weight:900;flex:0 0 26px;
}
</style>
HTML;
        $html = str_replace('</head>', $style.'</head>', $html);

        $script = <<<'HTML'
<script id="unifco-customer-verification-state-script-v3">
(()=>{
  const status=document.getElementById('customer-status');
  const button=document.getElementById('customer-lookup');
  const input=document.getElementById('customer_number');
  if(!status)return;

  const isFailureText=(text)=>{
    const t=(text||'').replace(/\s+/g,' ').trim();
    return t.includes('لم يتم العثور على العميل') ||
           t.includes('العميل غير موجود') ||
           t.includes('رقم العميل غير صحيح') ||
           t.includes('لم يتم التحقق');
  };

  const normalize=()=>{
    const text=status.textContent||'';
    if(isFailureText(text)){
      if(!status.classList.contains('unifco-not-verified') || !status.querySelector('.unifco-status-x')){
        status.classList.remove('ok');
        status.classList.add('bad','unifco-not-verified');
        status.innerHTML='<span class="unifco-status-x" aria-hidden="true">×</span><span>لم يتم التحقق</span>';
      }
      return;
    }
    if(status.classList.contains('ok')){
      status.classList.remove('bad','unifco-not-verified');
    }
  };

  const scheduleChecks=()=>{
    [0,120,300,650,1200,2200].forEach(delay=>setTimeout(normalize,delay));
  };

  button?.addEventListener('click',scheduleChecks,{passive:true});
  input?.addEventListener('change',scheduleChecks,{passive:true});
  input?.addEventListener('blur',scheduleChecks,{passive:true});
  scheduleChecks();
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);

        $response->setContent($html);
        return $response;
    }
}
