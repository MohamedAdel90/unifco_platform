<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCustomerLookupStatePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="customer-lookup"') || ! str_contains($html, 'id="customer_number"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-customer-lookup-state-v1">
.lookup-row{display:grid!important;grid-template-columns:minmax(0,1fr) max-content!important;align-items:center!important;gap:8px!important;width:100%!important;min-width:0!important}
#customer_number{min-width:0!important;width:100%!important}
#customer-lookup.customer-lookup-state{width:auto!important;max-width:100%!important;min-width:118px!important;height:42px!important;min-height:42px!important;padding:0 14px!important;border-radius:8px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;font-family:Cairo,Arial,sans-serif!important;font-size:clamp(9px,1vw,11px)!important;font-weight:900!important;line-height:1!important;white-space:nowrap!important;word-break:keep-all!important;overflow:hidden!important;text-overflow:clip!important;box-sizing:border-box!important;box-shadow:none!important;transition:background .18s ease,border-color .18s ease,color .18s ease,transform .18s ease!important}
#customer-lookup.customer-lookup-state .uf-lookup-state-icon{width:16px!important;height:16px!important;flex:0 0 16px!important;display:inline-grid!important;place-items:center!important;font-size:15px!important;line-height:1!important}
#customer-lookup.customer-lookup-state .uf-lookup-state-label{display:block!important;min-width:0!important;white-space:nowrap!important;line-height:1!important}
#customer-lookup.customer-lookup-state.is-empty{background:#fff7df!important;border:1px solid #e7bd58!important;color:#9a6700!important}
#customer-lookup.customer-lookup-state.is-ready{background:#eef6ff!important;border:1px solid #8ebced!important;color:#155fae!important}
#customer-lookup.customer-lookup-state.is-loading{background:#edf4ff!important;border:1px solid #7daee5!important;color:#174f8c!important}
#customer-lookup.customer-lookup-state.is-valid{background:#eaf8ef!important;border:1px solid #73c88f!important;color:#16753c!important}
#customer-lookup.customer-lookup-state.is-invalid{background:#fff0f1!important;border:1px solid #e1848d!important;color:#bd2632!important}
#customer-lookup.customer-lookup-state:disabled{opacity:1!important;cursor:not-allowed!important}
#customer-lookup.customer-lookup-state:not(:disabled):hover{transform:translateY(-1px)!important}
@media(max-width:700px){
 .lookup-row{grid-template-columns:minmax(0,1fr) max-content!important;gap:6px!important}
 #customer-lookup.customer-lookup-state{min-width:106px!important;height:40px!important;min-height:40px!important;padding:0 10px!important;font-size:clamp(8px,2.5vw,10px)!important;gap:5px!important}
 #customer-lookup.customer-lookup-state .uf-lookup-state-icon{width:14px!important;height:14px!important;flex-basis:14px!important;font-size:13px!important}
}
@media(max-width:420px){
 .lookup-row{grid-template-columns:minmax(0,1fr) max-content!important}
 #customer-lookup.customer-lookup-state{min-width:96px!important;max-width:132px!important;padding:0 8px!important;font-size:8.5px!important}
}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-customer-lookup-state-script-v1">
(()=>{
  const ready=fn=>document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn):fn();
  ready(()=>{
    const input=document.getElementById('customer_number');
    const button=document.getElementById('customer-lookup');
    const status=document.getElementById('customer-status');
    if(!input||!button)return;

    const isEnglish=()=>String(document.documentElement.lang||'').toLowerCase().startsWith('en') || document.documentElement.dir==='ltr';
    const text=(ar,en)=>isEnglish()?en:ar;
    const minimumLength=1;
    let lastState='';

    button.classList.add('customer-lookup-state');

    const setState=(state)=>{
      if(lastState===state && button.dataset.ufLookupState===state)return;
      lastState=state;
      button.dataset.ufLookupState=state;
      button.classList.remove('is-empty','is-ready','is-loading','is-valid','is-invalid');
      button.classList.add('is-'+state);

      let icon='';
      let label='';
      let disabled=false;
      if(state==='empty'){
        icon='!'; label=text('يرجى إدخال الرقم','Enter customer number'); disabled=true;
      }else if(state==='ready'){
        icon='✓'; label=text('تحقق','Verify');
      }else if(state==='loading'){
        icon='…'; label=text('جاري التحقق','Checking');
      }else if(state==='valid'){
        icon='✓'; label=text('تم التحقق','Verified');
      }else{
        icon='×'; label=text('العميل غير موجود','Customer not found');
      }
      button.disabled=disabled;
      button.innerHTML='<span class="uf-lookup-state-icon" aria-hidden="true">'+icon+'</span><span class="uf-lookup-state-label">'+label+'</span>';
      button.setAttribute('aria-label',label);
      button.title=label;
    };

    const inferState=()=>{
      const value=input.value.trim();
      if(value.length<minimumLength){setState('empty');return;}
      if(status?.classList.contains('ok')){setState('valid');return;}
      if(status?.classList.contains('bad')){setState('invalid');return;}
      if(input.classList.contains('lookup-loading')){setState('loading');return;}
      setState('ready');
    };

    input.addEventListener('input',()=>requestAnimationFrame(inferState));
    input.addEventListener('blur',()=>setTimeout(inferState,0));
    button.addEventListener('click',()=>{
      if(input.value.trim().length>=minimumLength)setTimeout(()=>setState('loading'),0);
    });

    if(status){
      new MutationObserver(()=>requestAnimationFrame(inferState)).observe(status,{attributes:true,childList:true,subtree:true,characterData:true});
    }
    new MutationObserver(()=>requestAnimationFrame(inferState)).observe(input,{attributes:true,attributeFilter:['class']});

    inferState();
    setTimeout(inferState,300);
    setTimeout(inferState,900);
  });
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
