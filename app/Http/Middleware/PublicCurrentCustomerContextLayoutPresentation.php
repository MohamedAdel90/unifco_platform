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

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="routine-form"') || ! str_contains($html, 'id="contract-section"') || ! str_contains($html, 'id="site-section"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-customer-context-layout-v1">
.uf-customer-context-layout{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(420px,.92fr);grid-template-areas:"details customer";gap:14px;align-items:stretch;margin-bottom:13px;direction:ltr}
.uf-customer-context-layout>.uf-context-customer{grid-area:customer;margin:0!important;direction:rtl;min-width:0;height:100%}
.uf-customer-context-layout>.uf-context-details{grid-area:details;display:flex;flex-direction:column;gap:13px;min-width:0;direction:rtl}
.uf-customer-context-layout>.uf-context-details>.panel{margin:0!important;box-shadow:0 7px 22px rgba(7,31,77,.045)}
.uf-context-customer>.section-title,.uf-context-details .section-title{margin-bottom:13px}
.uf-context-customer>.section-title{font-size:15px}
.uf-context-details .section-title{font-size:14px}
.uf-context-details #contract-section,.uf-context-details #site-section{padding:16px 17px!important}
.uf-context-details #contract-section .grid{grid-template-columns:1.35fr 1fr 1fr;gap:10px}
.uf-context-details #contract-section .span2{grid-column:span 1}
.uf-context-details #site-section .grid{grid-template-columns:1.35fr 1fr 1fr;gap:10px}
.uf-context-details #site-section .span2{grid-column:span 1}
.uf-context-details #site-section .map{height:135px;margin-top:10px}
.uf-context-details .field label{font-size:10.5px;margin-bottom:5px}
.uf-context-details input,.uf-context-details select{height:40px;font-size:10.5px}
.uf-context-customer .lookup-row{grid-template-columns:1fr 150px}
.uf-context-customer #customer-status.ok{display:none!important}
.uf-context-customer .uf-customer-profile-card{margin-top:11px}
.uf-context-customer .uf-customer-profile-head{min-height:64px;padding:9px 12px}
.uf-context-customer .uf-customer-profile-body{padding:9px 12px}
.uf-context-customer .uf-customer-info-item{min-height:46px;padding:5px 10px}
.uf-context-customer .uf-customer-profile-foot{padding:0 12px 10px}
.uf-customer-context-layout .disabled-section{opacity:.48}
.uf-customer-context-layout .disabled-section:not(.uf-context-customer){pointer-events:none}
@media(max-width:1050px){.uf-customer-context-layout{grid-template-columns:1fr;grid-template-areas:"customer" "details";direction:rtl}.uf-customer-context-layout>.uf-context-customer{height:auto}.uf-context-details #contract-section .grid,.uf-context-details #site-section .grid{grid-template-columns:repeat(2,1fr)}.uf-context-details #contract-section .span2,.uf-context-details #site-section .span2{grid-column:span 1}}
@media(max-width:620px){.uf-customer-context-layout{gap:10px}.uf-context-details #contract-section .grid,.uf-context-details #site-section .grid{grid-template-columns:1fr}.uf-context-details #site-section .map{height:120px}.uf-context-customer .lookup-row{grid-template-columns:1fr}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-customer-context-layout-script-v1">
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
    customer.parentNode.insertBefore(layout,customer);
    layout.appendChild(details);
    layout.appendChild(customer);
    details.appendChild(contract);
    details.appendChild(site);
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
