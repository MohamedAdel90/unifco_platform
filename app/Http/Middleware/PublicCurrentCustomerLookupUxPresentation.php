<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentCustomerLookupUxPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $icons = [
            'customer' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20v-1.6A6.5 6.5 0 0 1 12 12a6.5 6.5 0 0 1 6.5 6.4V20"/></svg>',
            'contract' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 12h6M10 16h6"/></svg>',
            'site' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Z"/><circle cx="12" cy="10" r="2"/></svg>',
            'asset' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14.6 6.4 3-3a4.2 4.2 0 0 1-5.4 5.4L5.6 15.4a2 2 0 1 0 3 3l6.6-6.6a4.2 4.2 0 0 1 5.4-5.4l-3 3"/></svg>',
            'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>',
            'fault' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2.8 20h18.4Z"/><path d="M12 9v5M12 17h.01"/></svg>',
            'attachment' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8.5 12.5 6.7-6.7a3.2 3.2 0 0 1 4.5 4.5l-8.1 8.1a5 5 0 0 1-7.1-7.1l8-8"/></svg>',
        ];

        $title = static function (string $number, string $label, string $icon): string {
            return '<h2 class="section-title"><span class="num">'.$number.'</span><span class="section-title-label"><span class="section-icon">'.$icon.'</span><span>'.$label.'</span></span></h2>';
        };

        $replacements = [
            '<h2 class="section-title"><span class="num">1</span> بيانات العميل الحالي</h2>' => $title('1', 'بيانات العميل الحالي', $icons['customer']),
            '<h2 class="section-title"><span class="num">2</span> بيانات العقد</h2>' => $title('2', 'بيانات العقد', $icons['contract']),
            '<h2 class="section-title"><span class="num">3</span> الموقع والتواصل</h2>' => $title('3', 'الموقع والتواصل', $icons['site']),
            '<h2 class="section-title"><span class="num">4</span> المعدة وبياناتها</h2>' => $title('4', 'المعدة وبياناتها', $icons['asset']),
            '<h2 class="section-title"><span class="num">5</span> موعد الزيارة</h2>' => $title('5', 'موعد الزيارة', $icons['calendar']),
            '<h2 class="section-title"><span class="num">6</span> وصف سريع للعطل</h2>' => $title('6', 'وصف سريع للعطل', $icons['fault']),
            '<h2 class="section-title"><span class="num">7</span> مرفقات الطلب</h2>' => $title('7', 'مرفقات الطلب', $icons['attachment']),
        ];
        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        $oldButton = '<button type="button" class="btn" id="customer-lookup">جلب بيانات العميل</button>';
        $newButton = '<button type="button" class="btn ghost customer-lookup-fallback" id="customer-lookup" title="جلب البيانات يدويًا" aria-label="جلب بيانات العميل يدويًا"><span class="lookup-refresh" aria-hidden="true">↻</span><span>جلب</span></button>';
        $html = str_replace($oldButton, $newButton, $html);

        $statusMarker = '<div class="status" id="customer-status"></div>';
        $statusReplacement = $statusMarker.'<div class="lookup-auto-hint" id="customer-auto-hint">يتم جلب بيانات العميل تلقائيًا بعد إدخال رقم العميل.</div>';
        $html = str_replace($statusMarker, $statusReplacement, $html);

        $style = <<<'HTML'
<style id="unifco-current-customer-auto-lookup-v1">
.section-title-label{display:inline-flex;align-items:center;gap:7px;min-width:0}
.section-icon{width:25px;height:25px;display:inline-grid;place-items:center;flex:0 0 25px;border:1px solid #d8e2ee;border-radius:7px;background:#f7faff;color:#173a69}
.section-icon svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.lookup-row{grid-template-columns:minmax(0,1fr) auto!important;align-items:center!important;gap:7px!important}
.customer-lookup-fallback{width:auto!important;min-width:68px!important;height:34px!important;padding:0 10px!important;border-radius:7px!important;background:#fff!important;color:#526b8c!important;border:1px solid #d5dfeb!important;font-size:9.5px!important;font-weight:800!important;box-shadow:none!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:4px!important;white-space:nowrap!important}
.customer-lookup-fallback:hover{border-color:#93a8c2!important;color:#0b2c59!important;background:#f8fbff!important}
.lookup-refresh{font-size:14px;line-height:1}
.lookup-auto-hint{margin-top:5px;color:#8190a4;font-size:9px;line-height:1.6}
.lookup-auto-hint.loading{color:#315f98;font-weight:700}
#customer_number.lookup-loading{background-image:linear-gradient(90deg,transparent,rgba(26,79,139,.06),transparent);background-size:200% 100%;animation:unifcoLookupPulse 1.15s linear infinite}
@keyframes unifcoLookupPulse{0%{background-position:200% 0}100%{background-position:-200% 0}}
@media(max-width:560px){.lookup-row{grid-template-columns:minmax(0,1fr) auto!important}.customer-lookup-fallback{min-width:58px!important;padding:0 8px!important}.section-icon{width:23px;height:23px;flex-basis:23px}}
</style>
HTML;
        $html = str_replace('</head>', $style.'</head>', $html);

        $script = <<<'HTML'
<script id="unifco-current-customer-auto-lookup-script">
(()=>{
    const input=document.getElementById('customer_number');
    const button=document.getElementById('customer-lookup');
    const status=document.getElementById('customer-status');
    const hint=document.getElementById('customer-auto-hint');
    if(!input||!button)return;

    let timer=null;
    let lastAutoValue='';
    const minimumLength=4;
    const delay=650;

    const resetDependentUi=()=>{
        ['company_name','responsible_person','mobile','email','customer_city','customer_address','contract_title','contract_status','site_city','site_name','visible_site_address','site_contact','site_mobile','site_address','latitude','longitude','asset_id','equipment_brand','equipment_model'].forEach(id=>{
            const el=document.getElementById(id);
            if(el)el.value='';
        });
        const contract=document.getElementById('contract_no');
        const site=document.getElementById('site_id');
        const assets=document.getElementById('asset-list');
        if(contract)contract.innerHTML='<option value="">اختر عقد العميل</option>';
        if(site)site.innerHTML='<option value="">اختر الموقع</option>';
        if(assets)assets.innerHTML='<option value="">اختر المعدة</option>';
        ['contract-section','site-section','asset-section'].forEach(id=>document.getElementById(id)?.classList.add('disabled-section'));
        document.getElementById('asset-card')?.classList.remove('show');
        const map=document.querySelector('#map iframe');
        if(map)map.src='about:blank';
    };

    const triggerLookup=()=>{
        const value=input.value.trim();
        if(value.length<minimumLength)return;
        lastAutoValue=value;
        input.classList.add('lookup-loading');
        if(hint){hint.textContent='جاري جلب بيانات العميل تلقائيًا…';hint.classList.add('loading')}
        button.click();
        const observer=new MutationObserver(()=>{
            if(status&&(status.classList.contains('ok')||status.classList.contains('bad'))){
                input.classList.remove('lookup-loading');
                if(hint){hint.textContent=status.classList.contains('ok')?'تم تحديث بيانات العميل. يمكنك المتابعة باختيار العقد والموقع.':'يمكنك مراجعة الرقم أو استخدام زر «جلب» كخيار احتياطي.';hint.classList.remove('loading')}
                observer.disconnect();
            }
        });
        if(status)observer.observe(status,{attributes:true,childList:true,subtree:true});
        setTimeout(()=>{input.classList.remove('lookup-loading');observer.disconnect()},12000);
    };

    input.addEventListener('input',()=>{
        clearTimeout(timer);
        const value=input.value.trim();
        resetDependentUi();
        lastAutoValue='';
        if(status){status.className='status';status.textContent=''}
        input.classList.remove('lookup-loading');
        if(value.length<minimumLength){
            if(hint){hint.textContent=value.length?'أكمل رقم العميل ليتم الجلب تلقائيًا.':'يتم جلب بيانات العميل تلقائيًا بعد إدخال رقم العميل.';hint.classList.remove('loading')}
            return;
        }
        if(hint){hint.textContent='سيتم جلب البيانات تلقائيًا…';hint.classList.remove('loading')}
        timer=setTimeout(()=>{
            if(input.value.trim()===value&&value!==lastAutoValue)triggerLookup();
        },delay);
    });

    input.addEventListener('keydown',event=>{
        if(event.key!=='Enter')return;
        event.preventDefault();
        clearTimeout(timer);
        if(input.value.trim().length>=minimumLength)triggerLookup();
        else input.reportValidity?.();
    });

    button.addEventListener('click',()=>{
        clearTimeout(timer);
        if(input.value.trim().length>=minimumLength){
            input.classList.add('lookup-loading');
            if(hint){hint.textContent='جاري جلب بيانات العميل…';hint.classList.add('loading')}
        }
    });
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);

        $response->setContent($html);
        return $response;
    }
}
