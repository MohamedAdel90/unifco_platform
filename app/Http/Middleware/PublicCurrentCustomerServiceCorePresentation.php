<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentCustomerServiceCorePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="service-type"') || ! str_contains($html, 'id="routine-form"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-current-customer-service-core-v1">
.uf-service-core-note{margin:10px 0 0;padding:10px 12px;border:1px solid #dbe5f0;border-radius:9px;background:#f8fbff;color:#5e718d;font-size:10px;font-weight:700;line-height:1.75}
.uf-service-core-note strong{color:#0b2c59}
.uf-service-tail-placeholder{display:none;margin:0 0 14px;padding:16px 18px;border:1px dashed #c8d6e7;border-radius:12px;background:#fbfdff;color:#60738f;font-size:11px;line-height:1.9;text-align:right}
.uf-service-tail-placeholder.show{display:block}
.uf-service-tail-placeholder b{display:block;color:#0b2c59;font-size:13px;margin-bottom:3px}
#routine-form.uf-core-only>section.panel.uf-service-tail-section{display:none!important}
#routine-form.uf-current-customer-active{display:block!important}
#future-box.uf-core-hidden{display:none!important}
@media(max-width:700px){.uf-service-core-note,.uf-service-tail-placeholder{font-size:10px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-current-customer-service-core-script-v1">
(()=>{
    const service=document.getElementById('service-type');
    const subtype=document.getElementById('service-subtype');
    const routine=document.getElementById('routine-form');
    const future=document.getElementById('future-box');
    const selector=document.querySelector('.request-selector-panel') || service?.closest('section.panel');
    if(!service||!subtype||!routine)return;

    const currentButton=document.querySelector('[data-customer-kind="current"]');
    const newButton=document.querySelector('[data-customer-kind="new"]');
    const customerPanel=document.getElementById('new-customer-panel');

    const choices={
        maintenance:[['routine','صيانة عادية'],['urgent','صيانة طارئة']],
        quotation:[['parts','عرض سعر قطع غيار'],['visit','عرض سعر زيارة فنية'],['contract','عرض سعر عقد صيانة']],
        consultation:[['technical','استشارة فنية']],
    };

    const serviceLabels={maintenance:'صيانة',quotation:'عرض سعر',consultation:'استشارة فنية'};
    const subtypeLabels={routine:'صيانة عادية',urgent:'صيانة طارئة',parts:'قطع غيار',visit:'زيارة فنية',contract:'عقد صيانة',technical:'استشارة فنية'};

    let note=selector?.querySelector('.uf-service-core-note');
    if(!note&&selector){
        note=document.createElement('div');
        note.className='uf-service-core-note';
        selector.appendChild(note);
    }

    let placeholder=document.getElementById('uf-service-tail-placeholder');
    if(!placeholder){
        placeholder=document.createElement('div');
        placeholder.id='uf-service-tail-placeholder';
        placeholder.className='uf-service-tail-placeholder';
        routine.appendChild(placeholder);
    }

    const panels=[...routine.children].filter(el=>el.matches?.('section.panel'));
    const assetIndex=panels.findIndex(el=>el.id==='asset-section');
    if(assetIndex>=0){
        panels.slice(assetIndex+1).forEach(el=>el.classList.add('uf-service-tail-section'));
    }

    const isCurrent=()=>currentButton?.classList.contains('active') || !customerPanel?.classList.contains('show');

    const setChoices=(keep=true)=>{
        const previous=keep?subtype.value:'';
        const list=choices[service.value]||choices.maintenance;
        subtype.innerHTML=list.map(([value,label])=>`<option value="${value}">${label}</option>`).join('');
        if(previous&&list.some(([value])=>value===previous)) subtype.value=previous;
    };

    const sync=()=>{
        if(!isCurrent())return;
        routine.classList.add('uf-current-customer-active');
        routine.classList.remove('hidden');
        future?.classList.add('uf-core-hidden');
        if(future)future.style.display='none';

        const routineMaintenance=service.value==='maintenance'&&subtype.value==='routine';
        routine.classList.toggle('uf-core-only',!routineMaintenance);

        if(note){
            note.innerHTML=`<strong>النموذج الموحد للعميل الحالي:</strong> بيانات العميل والعقد والموقع والمعدة ثابتة لجميع أنواع الطلبات. الجزء التالي سيتغير لاحقًا حسب نوع الطلب المختار.`;
        }

        if(placeholder){
            const label=`${serviceLabels[service.value]||''} — ${subtypeLabels[subtype.value]||''}`;
            placeholder.classList.toggle('show',!routineMaintenance);
            placeholder.innerHTML=`<b>${label}</b>تم تفعيل نفس النموذج الأساسي لهذا النوع. سيتم تجهيز تفاصيل الطلب والمرفقات الخاصة به بعد اعتماد بطاقة المعدة وبياناتها.`;
        }
    };

    const onServiceChange=()=>{
        const desired=service.value;
        setTimeout(()=>{
            if(service.value!==desired)return;
            setChoices(false);
            sync();
        },0);
    };

    service.addEventListener('change',onServiceChange);
    subtype.addEventListener('change',()=>setTimeout(sync,0));
    currentButton?.addEventListener('click',()=>setTimeout(()=>{setChoices(true);sync()},0));
    newButton?.addEventListener('click',()=>setTimeout(()=>{
        routine.classList.remove('uf-current-customer-active','uf-core-only');
        placeholder?.classList.remove('show');
    },0));

    // Existing legacy selector code hides the form for anything except routine maintenance.
    // Keep the shared current-customer core visible after those handlers run.
    const observer=new MutationObserver(()=>{
        if(isCurrent()&&routine.classList.contains('hidden'))routine.classList.remove('hidden');
        if(isCurrent()&&future&&future.style.display!=='none')future.style.display='none';
    });
    observer.observe(routine,{attributes:true,attributeFilter:['class']});
    if(future)observer.observe(future,{attributes:true,attributeFilter:['style','class']});

    setChoices(true);
    sync();
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
