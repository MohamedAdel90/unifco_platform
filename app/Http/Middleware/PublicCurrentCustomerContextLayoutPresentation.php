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
<style id="unifco-customer-context-layout-v3">
.uf-customer-context-layout{display:grid!important;grid-template-columns:minmax(0,1.08fr) minmax(0,.92fr)!important;grid-template-areas:"details customer"!important;gap:16px!important;align-items:start!important;margin-bottom:14px!important;direction:ltr!important}
.uf-customer-context-layout>.uf-context-customer{grid-area:customer!important;display:block!important;align-self:start!important;margin:0!important;direction:rtl!important;min-width:0!important;width:100%!important;max-width:none!important;height:auto!important;min-height:0!important;padding:18px!important;box-sizing:border-box!important;box-shadow:0 7px 22px rgba(7,31,77,.045)!important}
.uf-customer-context-layout>.uf-context-customer>*{width:100%!important;max-width:none!important;box-sizing:border-box!important}
.uf-customer-context-layout>.uf-context-details{grid-area:details!important;display:flex!important;flex-direction:column!important;gap:14px!important;align-self:start!important;min-width:0!important;width:100%!important;direction:rtl!important}
.uf-customer-context-layout>.uf-context-details>.panel{display:block!important;align-self:start!important;margin:0!important;width:100%!important;max-width:none!important;height:auto!important;min-height:0!important;box-sizing:border-box!important;box-shadow:0 7px 22px rgba(7,31,77,.045)!important}
.uf-context-customer>.section-title,.uf-context-details .section-title{margin-bottom:14px!important}
.uf-context-customer>.section-title{font-size:16px!important}
.uf-context-details .section-title{font-size:15px!important}
.uf-context-details #contract-section,.uf-context-details #site-section{padding:17px 18px!important}
.uf-context-details #contract-section .grid{display:grid!important;grid-template-columns:1.35fr 1fr 1fr!important;gap:12px!important;width:100%!important;max-width:none!important}
.uf-context-details #contract-section .span2{grid-column:span 1!important}
.uf-context-details #site-section .grid{display:grid!important;grid-template-columns:1.35fr 1fr 1fr!important;gap:12px!important;width:100%!important;max-width:none!important}
.uf-context-details #site-section .span2{grid-column:span 1!important}
.uf-context-details #site-section .map{height:96px!important;min-height:96px!important;margin-top:12px!important;border-radius:9px!important}
.uf-context-details .field{min-width:0!important;max-width:none!important}
.uf-context-details .field label{font-size:11px!important;margin-bottom:6px!important}
.uf-context-details input,.uf-context-details select{width:100%!important;max-width:none!important;height:42px!important;font-size:11px!important;box-sizing:border-box!important}
.uf-context-customer .lookup-row{display:grid!important;grid-template-columns:minmax(0,1fr) 155px!important;gap:10px!important;width:100%!important;max-width:none!important;margin:0!important}
.uf-context-customer .lookup-row>*{min-width:0!important;max-width:none!important;width:100%!important}
.uf-context-customer #customer_number{width:100%!important;max-width:none!important}
.uf-context-customer #customer-status{width:100%!important;max-width:none!important;margin-top:10px!important}
.uf-context-customer #customer-status.ok{display:none!important}
.uf-context-customer .uf-customer-profile-card{display:block!important;width:100%!important;max-width:none!important;margin:12px 0 0!important}
.uf-context-customer .uf-customer-profile-head{display:flex!important;width:100%!important;min-height:68px!important;padding:10px 13px!important;box-sizing:border-box!important}
.uf-context-customer .uf-customer-profile-body{display:grid!important;width:100%!important;max-width:none!important;padding:10px 13px!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;box-sizing:border-box!important}
.uf-context-customer .uf-customer-info-item{min-width:0!important;min-height:48px!important;padding:6px 10px!important}
.uf-context-customer .uf-customer-profile-foot{width:100%!important;padding:0 13px 11px!important;box-sizing:border-box!important}
.uf-context-customer .uf-company-name{max-width:300px!important}
.uf-context-customer .field,.uf-context-customer .grid,.uf-context-customer>div{width:100%!important;max-width:none!important}
.uf-context-customer>p,.uf-context-customer>.hint,.uf-context-customer>.helper{width:100%!important;max-width:none!important}
.uf-customer-context-layout .disabled-section{opacity:.48}
.uf-customer-context-layout .disabled-section:not(.uf-context-customer){pointer-events:none}
@media(max-width:1180px){.uf-customer-context-layout{grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important}.uf-context-customer .uf-customer-profile-body{grid-template-columns:repeat(2,minmax(0,1fr))!important}.uf-context-customer .uf-address-item{grid-column:span 1!important}}
@media(max-width:1050px){.uf-customer-context-layout{grid-template-columns:1fr!important;grid-template-areas:"customer" "details"!important;direction:rtl!important}.uf-context-details #contract-section .grid,.uf-context-details #site-section .grid{grid-template-columns:repeat(2,1fr)!important}.uf-context-details #contract-section .span2,.uf-context-details #site-section .span2{grid-column:span 1!important}.uf-context-customer .uf-customer-profile-body{grid-template-columns:repeat(3,minmax(0,1fr))!important}}
@media(max-width:760px){.uf-context-customer .uf-customer-profile-body{grid-template-columns:repeat(2,minmax(0,1fr))!important}.uf-context-customer .uf-address-item{grid-column:span 1!important}}
@media(max-width:620px){.uf-customer-context-layout{gap:10px!important}.uf-context-details #contract-section .grid,.uf-context-details #site-section .grid{grid-template-columns:1fr!important}.uf-context-details #site-section .map{height:88px!important;min-height:88px!important}.uf-context-customer .lookup-row{grid-template-columns:1fr!important}.uf-context-customer .uf-customer-profile-body{grid-template-columns:1fr!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-customer-context-layout-script-v3">
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
    customer.style.setProperty('display','block','important');
    customer.style.setProperty('height','auto','important');
    customer.style.setProperty('min-height','0','important');
    customer.style.setProperty('width','100%','important');
    customer.style.setProperty('max-width','none','important');

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
