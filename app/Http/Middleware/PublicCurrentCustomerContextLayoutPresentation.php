<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentCustomerContextLayoutPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="routine-form"') || ! str_contains($html, 'id="contract-section"') || ! str_contains($html, 'id="site-section"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-customer-context-layout-v10">
.uf-customer-context-layout{display:grid!important;grid-template-columns:minmax(0,1.08fr) minmax(0,.92fr)!important;grid-template-areas:"details customer"!important;gap:16px!important;align-items:stretch!important;margin-bottom:14px!important;direction:ltr!important}
.uf-customer-context-layout>.uf-context-customer{grid-area:customer!important;display:flex!important;flex-direction:column!important;align-self:start!important;margin:0!important;direction:rtl!important;min-width:0!important;width:100%!important;max-width:none!important;height:auto!important;min-height:0!important;padding:17px 18px!important;box-sizing:border-box!important;box-shadow:0 7px 22px rgba(7,31,77,.045)!important}
.uf-customer-context-layout>.uf-context-customer>*{box-sizing:border-box!important}
.uf-customer-context-layout>.uf-context-details{grid-area:details!important;display:grid!important;grid-template-rows:auto 1fr!important;gap:14px!important;align-self:stretch!important;min-width:0!important;width:100%!important;height:100%!important;direction:rtl!important}
.uf-customer-context-layout>.uf-context-details:only-child{grid-area:auto!important;grid-column:1/-1!important}
.uf-customer-context-layout>.uf-context-details>.panel{display:block!important;margin:0!important;width:100%!important;max-width:none!important;height:auto!important;min-height:0!important;box-sizing:border-box!important;box-shadow:0 7px 22px rgba(7,31,77,.045)!important}
.uf-context-details #site-section{height:100%!important}
.uf-context-customer>.section-title,.uf-context-details .section-title{margin-bottom:14px!important}
.uf-context-customer>.section-title{font-size:16px!important}
.uf-context-details .section-title{font-size:15px!important}
.uf-context-details #contract-section,.uf-context-details #site-section{padding:17px 18px!important}
.uf-context-details #contract-section .grid,.uf-context-details #site-section .grid{display:grid!important;grid-template-columns:1.35fr 1fr 1fr!important;gap:12px!important;width:100%!important;max-width:none!important}
.uf-context-details #contract-section .span2,.uf-context-details #site-section .span2{grid-column:span 1!important}
.uf-context-details #site-section .map,.uf-context-details #site-section .site-map,.uf-context-details #site-section #map{display:none!important;height:0!important;min-height:0!important;max-height:0!important;margin:0!important;padding:0!important;border:0!important;overflow:hidden!important}
.uf-context-details .field{min-width:0!important;max-width:none!important}
.uf-context-details .field label{font-size:11px!important;margin-bottom:6px!important}
.uf-context-details input,.uf-context-details select{width:100%!important;max-width:none!important;height:42px!important;font-size:11px!important;box-sizing:border-box!important}
.uf-context-details .uf-db-address{background:#f3f7fb!important;color:#173a69!important;cursor:not-allowed!important}
.uf-context-details .uf-manual-address{background:#fff!important;cursor:text!important}

.uf-customer-lookup-intro{display:flex!important;align-items:baseline!important;justify-content:space-between!important;gap:12px!important;width:100%!important;margin:-2px 0 7px!important}
.uf-customer-lookup-intro>label{display:block!important;margin:0!important;font-size:11px!important;font-weight:900!important;color:#071f4d!important;white-space:nowrap!important}
.uf-customer-lookup-intro>.lookup-auto-hint{display:block!important;flex:1 1 auto!important;margin:0!important;text-align:start!important;color:#8190a4!important;font-size:9px!important;line-height:1.55!important}
.uf-context-customer>.lookup-row{display:grid!important;grid-template-columns:minmax(0,1fr) 155px!important;gap:10px!important;width:100%!important;max-width:none!important;margin:0!important}
.uf-context-customer>.lookup-row>*{min-width:0!important;max-width:none!important;width:100%!important}
.uf-context-customer #customer_number{width:100%!important;max-width:none!important}
.uf-context-customer>#customer-status{width:100%!important;max-width:none!important;margin:8px 0 0!important;min-height:0!important}
.uf-context-customer>#customer-status:empty{display:none!important}
.uf-context-customer>#customer-status.ok{display:none!important}
.uf-context-customer>#customer-status.uf-verified-status{display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:8px!important;min-height:42px!important;padding:7px 11px!important;background:#eefbf3!important;border:1px solid #b8dfc5!important;border-radius:8px!important;color:#08752c!important;font-size:11px!important;font-weight:900!important;line-height:1.5!important}
.uf-context-customer>#customer-status.uf-verified-status:after{content:'✓'!important;width:27px!important;height:27px!important;display:inline-grid!important;place-items:center!important;flex:0 0 27px!important;border-radius:50%!important;background:#08752c!important;border:1px solid #056522!important;color:#fff!important;font-size:15px!important;font-weight:900!important;line-height:1!important;box-shadow:0 2px 6px rgba(8,117,44,.22)!important}
.uf-context-customer>#customer-summary.uf-legacy-customer-summary{display:block!important;width:100%!important;margin:10px 0 0!important;padding:9px 11px!important;background:#f8fffc!important;border-color:#c9e7dd!important;border-radius:10px!important}
.uf-legacy-customer-summary:not(.is-ok) .customer-summary-head,.uf-legacy-customer-summary:not(.is-ok) .customer-change{display:none!important}
.uf-legacy-customer-summary .customer-state{display:none!important}
.uf-legacy-customer-summary .customer-skeleton{display:none!important}
.uf-legacy-customer-summary .customer-summary-head{padding:0 0 8px!important;margin-bottom:2px!important}
.uf-legacy-customer-summary .customer-icon{width:32px!important;height:32px!important;font-size:15px!important}
.uf-legacy-customer-summary .customer-details{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;margin:0!important}
.uf-legacy-customer-summary .customer-detail{min-height:43px!important;padding:5px 8px!important}
.uf-legacy-customer-summary .detail-icon{width:25px!important;height:25px!important;font-size:12px!important}
.uf-legacy-customer-summary .detail-value{min-height:15px!important}
.uf-legacy-customer-summary .customer-change{padding-top:7px!important}
.uf-context-customer>#uf-current-customer-card{display:flex!important;flex-direction:column!important;flex:0 0 auto!important;width:100%!important;max-width:none!important;margin:12px 0 0!important;justify-self:stretch!important;align-self:stretch!important}
.uf-context-customer>#uf-current-customer-card[hidden]{display:none!important}
.uf-context-customer>#uf-current-customer-card .uf-customer-profile-head{display:flex!important;width:100%!important;min-height:68px!important;padding:10px 13px!important;box-sizing:border-box!important}
.uf-context-customer>#uf-current-customer-card .uf-customer-verified{display:flex!important;visibility:visible!important;opacity:1!important}
.uf-context-customer.uf-customer-loaded #uf-current-customer-card .uf-customer-verified{display:flex!important;visibility:visible!important;opacity:1!important}
.uf-context-customer>#uf-current-customer-card .uf-customer-profile-body{display:grid!important;flex:1 1 auto!important;width:100%!important;max-width:none!important;padding:10px 13px!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;box-sizing:border-box!important}
.uf-context-customer>#uf-current-customer-card .uf-customer-info-item{min-width:0!important;min-height:48px!important;padding:6px 10px!important}
.uf-context-customer>#uf-current-customer-card .uf-customer-profile-foot{width:100%!important;padding:0 13px 11px!important;box-sizing:border-box!important}
.uf-context-customer>#uf-current-customer-card .uf-company-name{max-width:300px!important}
.uf-context-customer>.hint,.uf-context-customer>.helper,.uf-context-customer>p{width:100%!important;max-width:none!important}
.uf-context-customer.uf-customer-loaded .uf-hide-after-load{display:none!important}
.uf-customer-context-layout .disabled-section{opacity:.48}
.uf-customer-context-layout .disabled-section:not(.uf-context-customer){pointer-events:none}
html[dir="ltr"] .uf-customer-context-layout>.uf-context-customer,html[dir="ltr"] .uf-customer-context-layout>.uf-context-details{direction:ltr!important}
@media(max-width:1180px){.uf-customer-context-layout{grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important}.uf-context-customer>#uf-current-customer-card .uf-customer-profile-body{grid-template-columns:repeat(2,minmax(0,1fr))!important}.uf-context-customer .uf-address-item{grid-column:span 1!important}}
@media(max-width:1050px){.uf-customer-context-layout{grid-template-columns:1fr!important;grid-template-areas:"customer" "details"!important;direction:rtl!important}.uf-customer-context-layout>.uf-context-customer{height:auto!important}.uf-customer-context-layout>.uf-context-details{height:auto!important;grid-template-rows:auto auto!important}.uf-context-details #site-section{height:auto!important}.uf-context-details #contract-section .grid,.uf-context-details #site-section .grid{grid-template-columns:repeat(2,1fr)!important}.uf-context-customer>#uf-current-customer-card .uf-customer-profile-body{grid-template-columns:repeat(3,minmax(0,1fr))!important}}
@media(max-width:760px){.uf-legacy-customer-summary .customer-details{grid-template-columns:repeat(2,minmax(0,1fr))!important}.uf-context-customer>#uf-current-customer-card .uf-customer-profile-body{grid-template-columns:repeat(2,minmax(0,1fr))!important}.uf-context-customer .uf-address-item{grid-column:span 1!important}}
@media(max-width:620px){.uf-customer-context-layout{gap:10px!important}.uf-customer-lookup-intro{display:block!important}.uf-customer-lookup-intro>.lookup-auto-hint{margin-top:3px!important}.uf-legacy-customer-summary .customer-details{grid-template-columns:1fr!important}.uf-context-details #contract-section .grid,.uf-context-details #site-section .grid{grid-template-columns:1fr!important}.uf-context-customer>.lookup-row{grid-template-columns:1fr!important}.uf-context-customer>#uf-current-customer-card .uf-customer-profile-body{grid-template-columns:1fr!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-customer-context-layout-script-v10">
(()=>{
    const routine=document.getElementById('routine-form');
    const contract=document.getElementById('contract-section');
    const site=document.getElementById('site-section');
    if(!routine||!contract||!site||routine.querySelector('.uf-customer-context-layout'))return;

    const panels=[...routine.children].filter(el=>el.matches?.('section.panel'));
    const customer=panels.find(el=>el.querySelector('#customer_number'));
    if(!customer)return;

    const setTitle=(section,title)=>{
        const heading=section.querySelector(':scope > .section-title');
        if(!heading)return;
        const num=heading.querySelector('.num');
        const numHtml=num?num.outerHTML:'';
        heading.innerHTML=numHtml+' '+title;
    };

    setTitle(customer,'بيانات العميل');
    setTitle(contract,'بيانات العقد');
    setTitle(site,'بيانات الموقع والتواصل');

    const layout=document.createElement('div');
    layout.className='uf-customer-context-layout';
    const details=document.createElement('div');
    details.className='uf-context-details';

    customer.classList.add('uf-context-customer');
    customer.style.setProperty('display','flex','important');
    customer.style.setProperty('flex-direction','column','important');
    customer.style.setProperty('width','100%','important');
    customer.style.setProperty('max-width','none','important');

    const title=customer.querySelector(':scope > .section-title');
    const input=document.getElementById('customer_number');
    const lookup=input?.closest('.lookup-row') || customer.querySelector('.lookup-row');
    const status=document.getElementById('customer-status');
    const card=document.getElementById('uf-current-customer-card');
    const autoHint=document.getElementById('customer-auto-hint');
    const originalField=input?.closest('.field');
    const originalLabel=originalField?.querySelector(':scope > label');
    const originalHint=originalField?.querySelector(':scope > .hint');
    const legacySummary=document.getElementById('customer-summary');

    const intro=document.createElement('div');
    intro.className='uf-customer-lookup-intro';
    if(originalLabel)intro.appendChild(originalLabel);
    if(autoHint)intro.appendChild(autoHint);
    else if(originalHint)intro.appendChild(originalHint);
    title?.after(intro);

    if(lookup && lookup.parentElement!==customer) intro.after(lookup);
    if(status && status.parentElement!==customer) (lookup||title)?.after(status);
    if(card && card.parentElement!==customer) (status||lookup||title)?.after(card);

    if(originalHint && originalHint!==autoHint)originalHint.remove();
    if(originalField && originalField!==lookup)originalField.remove();
    legacySummary?.classList.add('uf-legacy-customer-summary');
    if(['بانتظار إدخال رقم العميل','Waiting for customer number'].includes(status?.textContent.trim()))status.textContent='';

    const syncVerificationStatus=()=>{
        status?.classList.toggle('uf-verified-status',!!legacySummary?.classList.contains('is-ok'));
    };
    if(legacySummary)new MutationObserver(syncVerificationStatus).observe(legacySummary,{attributes:true,attributeFilter:['class']});
    syncVerificationStatus();

    customer.parentNode.insertBefore(layout,customer);
    layout.appendChild(details);
    layout.appendChild(customer);
    details.appendChild(contract);
    details.appendChild(site);

    site.querySelectorAll('#map,.map,.site-map').forEach(el=>el.remove());

    const siteSelect=document.getElementById('site_id');
    const visibleAddress=document.getElementById('visible_site_address');
    const storedAddress=document.getElementById('site_address');
    const addressInput=(visibleAddress && visibleAddress.type!=='hidden') ? visibleAddress : ((storedAddress && storedAddress.type!=='hidden') ? storedAddress : visibleAddress);

    const setAddressState=(dbValue)=>{
        if(!addressInput)return;
        const value=(dbValue||'').trim();
        if(value){
            addressInput.value=value;
            addressInput.readOnly=true;
            addressInput.classList.add('uf-db-address');
            addressInput.classList.remove('uf-manual-address');
            addressInput.placeholder='';
            addressInput.title='تم تحميل العنوان من بيانات الموقع المسجلة';
        }else{
            addressInput.readOnly=false;
            addressInput.classList.remove('uf-db-address');
            addressInput.classList.add('uf-manual-address');
            addressInput.placeholder='أدخل العنوان يدوياً';
            addressInput.title='لا يوجد عنوان مسجل للموقع؛ يرجى إدخاله يدوياً';
        }
    };

    const syncAddressFromSelectedSite=(attempt=0)=>{
        const stored=(storedAddress?.value||'').trim();
        const visible=(visibleAddress?.value||'').trim();
        const dbValue=stored||visible;
        if(dbValue){
            setAddressState(dbValue);
            return;
        }
        if(attempt<7){
            setTimeout(()=>syncAddressFromSelectedSite(attempt+1),100);
            return;
        }
        setAddressState('');
    };

    if(siteSelect){
        siteSelect.addEventListener('change',()=>{
            if(addressInput){
                addressInput.readOnly=true;
                addressInput.classList.remove('uf-manual-address');
            }
            setTimeout(()=>syncAddressFromSelectedSite(0),0);
        });
        if(siteSelect.value) syncAddressFromSelectedSite(0);
        else if(addressInput && !(addressInput.value||'').trim()) setAddressState('');
    }

    const syncLoadedState=()=>{
        const loaded=!!(status?.classList.contains('ok') || (card && !card.hidden && !card.classList.contains('is-empty')));
        customer.classList.toggle('uf-customer-loaded',loaded);
    };
    if(status) new MutationObserver(syncLoadedState).observe(status,{attributes:true,childList:true,subtree:true});
    if(card) new MutationObserver(syncLoadedState).observe(card,{attributes:true,attributeFilter:['hidden','class','style']});
    input?.addEventListener('input',()=>customer.classList.remove('uf-customer-loaded'));
    syncLoadedState();
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
