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
<style id="unifco-customer-lookup-state-v5">
/* Customer data card only: keep the original lookup status as a data source,
   but never render its legacy text/button in the UI. */
#customer-status{display:none!important;visibility:hidden!important;width:0!important;height:0!important;overflow:hidden!important}

.lookup-row{
 display:grid!important;
 grid-template-columns:minmax(0,1.55fr) minmax(118px,.82fr) minmax(132px,1fr)!important;
 align-items:center!important;
 gap:8px!important;
 width:100%!important;
 max-width:100%!important;
 min-width:0!important;
 overflow:hidden!important;
 box-sizing:border-box!important;
 direction:rtl!important;
}
#customer_number{
 grid-column:1!important;
 min-width:0!important;
 width:100%!important;
 max-width:100%!important;
 height:42px!important;
 box-sizing:border-box!important;
}
.uf-lookup-actions{display:contents!important}
#customer-lookup,
#customer-lookup-state-indicator{
 min-width:0!important;
 width:100%!important;
 max-width:100%!important;
 height:42px!important;
 min-height:42px!important;
 padding:0 10px!important;
 border-radius:8px!important;
 display:flex!important;
 align-items:center!important;
 justify-content:center!important;
 gap:5px!important;
 font-family:Cairo,Arial,sans-serif!important;
 font-size:clamp(8px,.9vw,10px)!important;
 font-weight:900!important;
 line-height:1!important;
 white-space:nowrap!important;
 word-break:keep-all!important;
 overflow:hidden!important;
 text-overflow:clip!important;
 box-sizing:border-box!important;
}
#customer-lookup{
 grid-column:2!important;
 background:#1769c2!important;
 border:1px solid #1769c2!important;
 color:#fff!important;
 box-shadow:0 4px 12px rgba(23,105,194,.16)!important;
}
#customer-lookup:hover{background:#105aa9!important;border-color:#105aa9!important}
#customer-lookup .lookup-refresh{font-size:13px!important;line-height:1!important;flex:0 0 auto!important}
#customer-lookup-state-indicator{
 grid-column:3!important;
 box-shadow:none!important;
 transition:background .18s ease,border-color .18s ease,color .18s ease!important;
 pointer-events:none!important;
}
#customer-lookup-state-indicator .uf-lookup-state-icon{
 width:14px!important;
 height:14px!important;
 flex:0 0 14px!important;
 display:inline-grid!important;
 place-items:center!important;
 font-size:13px!important;
 line-height:1!important;
}
#customer-lookup-state-indicator .uf-lookup-state-label{
 display:block!important;
 min-width:0!important;
 max-width:100%!important;
 white-space:nowrap!important;
 line-height:1!important;
 overflow:hidden!important;
 text-overflow:clip!important;
}
#customer-lookup-state-indicator.is-empty{background:#fff7df!important;border:1px solid #e7bd58!important;color:#9a6700!important}
#customer-lookup-state-indicator.is-valid{background:#eaf8ef!important;border:1px solid #73c88f!important;color:#16753c!important}
#customer-lookup-state-indicator.is-invalid{background:#fff0f1!important;border:1px solid #e0525d!important;color:#b4232d!important}

@media(max-width:700px){
 .lookup-row{grid-template-columns:minmax(0,1.42fr) minmax(96px,.78fr) minmax(112px,1fr)!important;gap:5px!important}
 #customer_number,#customer-lookup,#customer-lookup-state-indicator{height:40px!important;min-height:40px!important}
 #customer-lookup,#customer-lookup-state-indicator{padding:0 6px!important;gap:3px!important;font-size:clamp(7px,1.65vw,8.8px)!important}
 #customer-lookup .lookup-refresh{font-size:11px!important}
 #customer-lookup-state-indicator .uf-lookup-state-icon{width:12px!important;height:12px!important;flex-basis:12px!important;font-size:11px!important}
}
@media(max-width:430px){
 .lookup-row{grid-template-columns:minmax(0,1.3fr) minmax(82px,.74fr) minmax(96px,1.02fr)!important;gap:4px!important}
 #customer-lookup,#customer-lookup-state-indicator{padding:0 4px!important;font-size:clamp(6.4px,2vw,7.8px)!important}
 #customer-lookup-state-indicator .uf-lookup-state-icon{width:10px!important;height:10px!important;flex-basis:10px!important;font-size:10px!important}
}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-customer-lookup-state-script-v5">
(()=>{
  const ready=fn=>document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn):fn();
  ready(()=>{
    const input=document.getElementById('customer_number');
    const button=document.getElementById('customer-lookup');
    const status=document.getElementById('customer-status');
    if(!input||!button)return;

    const isEnglish=()=>String(document.documentElement.lang||'').toLowerCase().startsWith('en') || document.documentElement.dir==='ltr';
    const text=(ar,en)=>isEnglish()?en:ar;

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

    const setState=(state)=>{
      indicator.dataset.ufLookupState=state;
      indicator.classList.remove('is-empty','is-valid','is-invalid');
      indicator.classList.add('is-'+state);

      let icon='!';
      let label=text('يرجى إدخال الرقم','Enter customer number');
      if(state==='valid'){
        icon='✓'; label=text('تم التحقق','Verified');
      }else if(state==='invalid'){
        icon='×'; label=text('العميل غير موجود','Customer not found');
      }
      indicator.innerHTML='<span class="uf-lookup-state-icon" aria-hidden="true">'+icon+'</span><span class="uf-lookup-state-label">'+label+'</span>';
      indicator.setAttribute('aria-label',label);
      indicator.title=label;
      requestAnimationFrame(fitText);
    };

    const inferState=()=>{
      const value=input.value.trim();
      if(!value){setState('empty');return;}
      if(status?.classList.contains('ok')){setState('valid');return;}
      if(status?.classList.contains('bad')){setState('invalid');return;}
      /* Until lookup resolves, keep the agreed neutral prompt instead of
         exposing any legacy intermediate message. */
      setState('empty');
    };

    const fitOne=(el,min=6.4,max=10)=>{
      if(!el)return;
      let size=max;
      el.style.fontSize=size+'px';
      while(size>min && el.scrollWidth>el.clientWidth){
        size-=.25;
        el.style.fontSize=size+'px';
      }
    };
    function fitText(){
      fitOne(button,6.4,10);
      fitOne(indicator,6.4,10);
    }

    input.addEventListener('input',()=>{
      if(status){status.className='status';status.textContent=''}
      requestAnimationFrame(()=>{inferState();fitText()});
    });
    input.addEventListener('blur',()=>setTimeout(()=>{inferState();fitText()},0));
    button.addEventListener('click',()=>setTimeout(()=>{inferState();fitText()},0));

    if(status){
      new MutationObserver(()=>requestAnimationFrame(()=>{inferState();fitText()})).observe(status,{attributes:true,childList:true,subtree:true,characterData:true});
    }
    const row=input.closest('.lookup-row');
    if(row && window.ResizeObserver){new ResizeObserver(fitText).observe(row)}

    inferState();
    fitText();
    setTimeout(()=>{inferState();fitText()},300);
    setTimeout(()=>{inferState();fitText()},900);
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
