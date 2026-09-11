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
<style id="unifco-customer-lookup-state-v2">
.lookup-row{display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;align-items:center!important;gap:8px!important;width:100%!important;min-width:0!important}
#customer_number{min-width:0!important;width:100%!important}
.uf-lookup-actions{display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:8px!important;direction:rtl!important;min-width:0!important;max-width:100%!important}
#customer-lookup{width:auto!important;min-width:126px!important;height:42px!important;min-height:42px!important;padding:0 16px!important;border-radius:8px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:6px!important;font-family:Cairo,Arial,sans-serif!important;font-size:10.5px!important;font-weight:900!important;line-height:1!important;white-space:nowrap!important;box-sizing:border-box!important;background:#1769c2!important;border:1px solid #1769c2!important;color:#fff!important;box-shadow:0 4px 12px rgba(23,105,194,.16)!important}
#customer-lookup:hover{background:#105aa9!important;border-color:#105aa9!important}
#customer-lookup .lookup-refresh{font-size:14px!important;line-height:1!important}
#customer-lookup-state-indicator{width:auto!important;max-width:190px!important;min-width:118px!important;height:42px!important;min-height:42px!important;padding:0 14px!important;border-radius:8px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;font-family:Cairo,Arial,sans-serif!important;font-size:clamp(9px,1vw,11px)!important;font-weight:900!important;line-height:1!important;white-space:nowrap!important;word-break:keep-all!important;overflow:hidden!important;text-overflow:clip!important;box-sizing:border-box!important;box-shadow:none!important;transition:background .18s ease,border-color .18s ease,color .18s ease!important;pointer-events:none!important}
#customer-lookup-state-indicator .uf-lookup-state-icon{width:16px!important;height:16px!important;flex:0 0 16px!important;display:inline-grid!important;place-items:center!important;font-size:15px!important;line-height:1!important}
#customer-lookup-state-indicator .uf-lookup-state-label{display:block!important;min-width:0!important;white-space:nowrap!important;line-height:1!important}
#customer-lookup-state-indicator.is-empty{background:#fff7df!important;border:1px solid #e7bd58!important;color:#9a6700!important}
#customer-lookup-state-indicator.is-ready{background:#eef6ff!important;border:1px solid #8ebced!important;color:#155fae!important}
#customer-lookup-state-indicator.is-loading{background:#edf4ff!important;border:1px solid #7daee5!important;color:#174f8c!important}
#customer-lookup-state-indicator.is-valid{background:#eaf8ef!important;border:1px solid #73c88f!important;color:#16753c!important}
#customer-lookup-state-indicator.is-invalid{background:#fff0f1!important;border:1px solid #e1848d!important;color:#bd2632!important}
@media(max-width:700px){
 .lookup-row{grid-template-columns:minmax(0,1fr) auto!important;gap:6px!important}
 .uf-lookup-actions{gap:6px!important}
 #customer-lookup{min-width:104px!important;height:40px!important;min-height:40px!important;padding:0 10px!important;font-size:9.5px!important}
 #customer-lookup-state-indicator{min-width:104px!important;max-width:150px!important;height:40px!important;min-height:40px!important;padding:0 10px!important;font-size:clamp(8px,2.5vw,10px)!important;gap:5px!important}
 #customer-lookup-state-indicator .uf-lookup-state-icon{width:14px!important;height:14px!important;flex-basis:14px!important;font-size:13px!important}
}
@media(max-width:520px){
 .lookup-row{grid-template-columns:1fr!important}
 .uf-lookup-actions{width:100%!important;justify-content:stretch!important}
 #customer-lookup,#customer-lookup-state-indicator{flex:1 1 0!important;min-width:0!important;max-width:none!important}
}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-customer-lookup-state-script-v2">
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

    // Restore the original manual fetch action and keep it clickable.
    button.disabled=false;
    button.classList.remove('customer-lookup-state','is-empty','is-ready','is-loading','is-valid','is-invalid');
    button.innerHTML='<span class="lookup-refresh" aria-hidden="true">↻</span><span>'+text('جلب البيانات','Fetch data')+'</span>';
    button.setAttribute('aria-label',text('جلب بيانات العميل','Fetch customer data'));
    button.title=text('جلب بيانات العميل','Fetch customer data');

    let actions=button.parentElement?.querySelector(':scope > .uf-lookup-actions');
    if(!actions){
      actions=document.createElement('div');
      actions.className='uf-lookup-actions';
      button.parentNode.insertBefore(actions,button);
      actions.appendChild(button);
    }

    let indicator=document.getElementById('customer-lookup-state-indicator');
    if(!indicator){
      indicator=document.createElement('span');
      indicator.id='customer-lookup-state-indicator';
      indicator.setAttribute('role','status');
      indicator.setAttribute('aria-live','polite');
      actions.appendChild(indicator);
    }

    let lastState='';
    const setState=(state)=>{
      if(lastState===state && indicator.dataset.ufLookupState===state)return;
      lastState=state;
      indicator.dataset.ufLookupState=state;
      indicator.classList.remove('is-empty','is-ready','is-loading','is-valid','is-invalid');
      indicator.classList.add('is-'+state);

      let icon='';
      let label='';
      if(state==='empty'){
        icon='!'; label=text('يرجى إدخال الرقم','Enter customer number');
      }else if(state==='ready'){
        icon='✓'; label=text('جاهز للتحقق','Ready to verify');
      }else if(state==='loading'){
        icon='…'; label=text('جاري التحقق','Checking');
      }else if(state==='valid'){
        icon='✓'; label=text('تم التحقق','Verified');
      }else{
        icon='×'; label=text('العميل غير موجود','Customer not found');
      }
      indicator.innerHTML='<span class="uf-lookup-state-icon" aria-hidden="true">'+icon+'</span><span class="uf-lookup-state-label">'+label+'</span>';
      indicator.setAttribute('aria-label',label);
      indicator.title=label;
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
