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
        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) return $response;
        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="customer-lookup"') || ! str_contains($html, 'id="customer_number"')) return $response;

        $style = <<<'HTML'
<style id="unifco-customer-lookup-state-v11">
#customer-status{display:none!important;visibility:hidden!important;width:0!important;height:0!important;overflow:hidden!important}
.customer-lookup-wrap{max-width:none!important;width:100%!important}
.lookup-row{display:grid!important;grid-template-columns:180px 105px 180px minmax(0,1fr)!important;align-items:center!important;gap:8px!important;width:100%!important;max-width:100%!important;min-width:0!important;overflow:hidden!important;box-sizing:border-box!important;direction:rtl!important}
#customer_number{grid-column:1!important;min-width:0!important;width:180px!important;max-width:180px!important;height:42px!important;box-sizing:border-box!important}
.uf-lookup-actions{display:contents!important}
#customer-lookup{grid-column:2!important;width:105px!important;max-width:105px!important;min-width:105px!important}
#customer-lookup-state-indicator{grid-column:3!important;width:180px!important;max-width:180px!important;min-width:180px!important}
#customer-lookup,#customer-lookup-state-indicator{height:42px!important;min-height:42px!important;padding:0 8px!important;border-radius:8px!important;display:flex!important;align-items:center!important;justify-content:center!important;gap:4px!important;font-family:Cairo,Arial,sans-serif!important;font-size:10px!important;font-weight:900!important;line-height:1!important;white-space:nowrap!important;word-break:keep-all!important;overflow:hidden!important;box-sizing:border-box!important}
#customer-lookup{background:#1769c2!important;border:1px solid #1769c2!important;color:#fff!important;box-shadow:0 4px 12px rgba(23,105,194,.16)!important}
#customer-lookup:hover{background:#105aa9!important;border-color:#105aa9!important}
#customer-lookup .lookup-refresh{font-size:12px!important;line-height:1!important;flex:0 0 auto!important}
#customer-lookup-state-indicator{box-shadow:none!important;pointer-events:none!important}
#customer-lookup-state-indicator .uf-lookup-state-icon{width:15px!important;height:15px!important;flex:0 0 15px!important;display:inline-grid!important;place-items:center!important;font-size:14px!important}
#customer-lookup-state-indicator .uf-lookup-state-label{display:block!important;min-width:0!important;max-width:100%!important;white-space:nowrap!important;overflow:hidden!important}
#customer-lookup-state-indicator.is-empty{background:#fff7df!important;border:1px solid #e7bd58!important;color:#9a6700!important}
#customer-lookup-state-indicator.is-valid{background:#eaf8ef!important;border:1px solid #73c88f!important;color:#16753c!important}
#customer-lookup-state-indicator.is-invalid{background:#fff0f1!important;border:1px solid #e0525d!important;color:#b4232d!important}
@media(max-width:700px){.lookup-row{grid-template-columns:150px 92px 160px minmax(0,1fr)!important;gap:5px!important}#customer_number{width:150px!important;max-width:150px!important}#customer-lookup{width:92px!important;max-width:92px!important;min-width:92px!important}#customer-lookup-state-indicator{width:160px!important;max-width:160px!important;min-width:160px!important}#customer_number,#customer-lookup,#customer-lookup-state-indicator{height:40px!important;min-height:40px!important}#customer-lookup,#customer-lookup-state-indicator{padding:0 4px!important;font-size:8px!important}}
@media(max-width:480px){.lookup-row{grid-template-columns:minmax(115px,34%) minmax(78px,23%) minmax(128px,37%) minmax(0,1fr)!important;gap:4px!important}#customer_number,#customer-lookup,#customer-lookup-state-indicator{width:100%!important;max-width:100%!important;min-width:0!important}#customer-lookup,#customer-lookup-state-indicator{padding:0 3px!important;font-size:7px!important}}
</style>
HTML;
        $script = <<<'HTML'
<script id="unifco-customer-lookup-state-script-v11">
(()=>{const ready=fn=>document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn):fn();ready(()=>{const input=document.getElementById('customer_number'),button=document.getElementById('customer-lookup'),status=document.getElementById('customer-status');if(!input||!button)return;const isEnglish=()=>String(document.documentElement.lang||'').toLowerCase().startsWith('en')||document.documentElement.dir==='ltr',text=(ar,en)=>isEnglish()?en:ar;button.disabled=false;button.classList.remove('customer-lookup-state','is-empty','is-ready','is-loading','is-valid','is-invalid');button.innerHTML='<span class="lookup-refresh" aria-hidden="true">↻</span><span>'+text('جلب البيانات','Fetch data')+'</span>';let actions=button.parentElement?.querySelector(':scope > .uf-lookup-actions');if(!actions){actions=document.createElement('div');actions.className='uf-lookup-actions';button.parentNode.insertBefore(actions,button);actions.appendChild(button)}let indicator=document.getElementById('customer-lookup-state-indicator');if(!indicator){indicator=document.createElement('span');indicator.id='customer-lookup-state-indicator';indicator.setAttribute('role','status');indicator.setAttribute('aria-live','polite');actions.appendChild(indicator)}const row=input.closest('.lookup-row');const findCard=()=>{let el=row||input.parentElement;for(let i=0;el&&i<7;i++,el=el.parentElement){const t=(el.innerText||'').replace(/\s+/g,' ').trim();if(t.includes('بيانات العميل')||/customer\s+(data|details)/i.test(t))return el}return row?.parentElement?.parentElement||document.body},card=findCard();let currentState='';const setState=state=>{if(currentState===state)return;currentState=state;indicator.classList.remove('is-empty','is-valid','is-invalid');indicator.classList.add('is-'+state);let icon='!',label=text('يرجى إدخال الرقم','Enter customer number');if(state==='valid'){icon='✓';label=text('تم التحقق','Verified')}else if(state==='invalid'){icon='×';label=text('العميل غير موجود','Customer not found')}indicator.innerHTML='<span class="uf-lookup-state-icon">'+icon+'</span><span class="uf-lookup-state-label">'+label+'</span>'};const norm=s=>String(s||'').replace(/\s+/g,' ').trim(),esc=s=>String(s).replace(/[.*+?^${}()|[\]\\]/g,'\\$&');const infer=()=>{const v=norm(input.value);if(!v){setState('empty');return}const st=norm(status?.textContent);if(status?.classList.contains('bad')||/لم يتم العثور|غير موجود|لا يوجد عميل|customer\s+not\s+found|not\s+found/i.test(st)){setState('invalid');return}if(status?.classList.contains('ok')){setState('valid');return}const ct=norm(card?.innerText),exact=new RegExp('ID\\s*:\\s*'+esc(v)+'(?:\\s|$)','i');if(exact.test(ct)||/ID\s*:\s*[^—\-\s][^\n]*/i.test(ct)){setState('valid');return}setState('empty')};const refresh=()=>[0,120,350,700,1200,2200].forEach(ms=>setTimeout(infer,ms));input.addEventListener('input',()=>{currentState='';setState('empty')});input.addEventListener('blur',refresh);button.addEventListener('click',refresh);if(status)new MutationObserver(infer).observe(status,{attributes:true,childList:true,subtree:true,characterData:true});infer();refresh()})})();
</script>
HTML;
        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);
        return $response;
    }
}
