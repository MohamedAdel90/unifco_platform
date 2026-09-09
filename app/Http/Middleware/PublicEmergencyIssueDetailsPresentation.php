<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicEmergencyIssueDetailsPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="routine-form"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-emergency-issue-details-v1">
.uf-details-pane.uf-emergency-details{display:flex!important;flex-direction:column!important}
.uf-details-pane.uf-emergency-details .uf-pane-head{margin-bottom:11px!important}
.uf-details-pane.uf-emergency-details .uf-pane-copy b{color:#08295d!important}
.uf-emergency-fields{display:grid!important;gap:9px!important}
.uf-emergency-description label,.uf-emergency-block>label,.uf-emergency-select label{display:flex!important;align-items:center!important;gap:6px!important;margin:0 0 5px!important;color:#08295d!important;font-size:10.5px!important;font-weight:900!important}
.uf-emergency-description textarea{height:88px!important;min-height:88px!important;resize:none!important;border:1px solid #cfdbea!important;border-radius:8px!important;padding:9px 11px!important;font:600 10.5px Cairo,Tahoma,Arial,sans-serif!important;color:#233754!important;background:#fff!important}
.uf-emergency-description textarea:focus,.uf-emergency-select select:focus{border-color:#7f9fca!important;box-shadow:0 0 0 3px rgba(35,128,244,.05)!important;outline:none!important}
.uf-emergency-row{display:grid!important;grid-template-columns:1fr 1fr!important;gap:8px!important}
.uf-emergency-chips{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:7px!important}
.uf-emergency-chip{height:35px!important;min-height:35px!important;padding:0 7px!important;border:1px solid #d6e1ed!important;border-radius:7px!important;background:#fff!important;color:#24466f!important;font:800 9.2px Cairo!important;cursor:pointer!important;white-space:nowrap!important}
.uf-emergency-chip:hover{border-color:#96bce8!important;background:#f8fbff!important}
.uf-emergency-chip.active{border-color:#2380f4!important;background:#eaf4ff!important;color:#1266bf!important;box-shadow:inset 0 0 0 1px #2380f4!important}
.uf-emergency-chip[data-kind="danger"].active{border-color:#df3044!important;background:#fff0f2!important;color:#bb1f31!important;box-shadow:inset 0 0 0 1px #df3044!important}
.uf-emergency-chip[data-kind="warning"].active{border-color:#d99b21!important;background:#fff7e6!important;color:#a96e00!important;box-shadow:inset 0 0 0 1px #d99b21!important}
.uf-emergency-select select{width:100%!important;height:37px!important;min-height:37px!important;border:1px solid #cfdbea!important;border-radius:7px!important;padding:6px 9px!important;background:#fff!important;color:#233754!important;font:700 9.7px Cairo!important}
.uf-emergency-alert{display:none!important;margin:0!important;padding:7px 9px!important;border:1px solid #f1b7bf!important;border-radius:7px!important;background:#fff4f5!important;color:#b42435!important;font-size:8.8px!important;font-weight:800!important;line-height:1.55!important}.uf-emergency-alert.show{display:block!important}
.uf-details-pane.uf-emergency-details .uf-attach-title{margin:10px 0 7px!important}
.uf-details-pane.uf-emergency-details .uf-upload-row{min-height:49px!important;margin-bottom:6px!important;padding-top:6px!important;padding-bottom:6px!important}
@media(max-width:650px){.uf-emergency-row{grid-template-columns:1fr!important}.uf-emergency-chips{grid-template-columns:1fr!important}.uf-emergency-chip{height:36px!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-emergency-issue-details-script-v1">
(()=>{
  const $=id=>document.getElementById(id);
  const isUrgent=()=>($('service-type')?.value==='maintenance' && $('service-subtype')?.value==='urgent');
  let applying=false;

  const setChoice=(group,value,button)=>{
    document.querySelectorAll(`[data-em-group="${group}"]`).forEach(x=>x.classList.toggle('active',x===button));
    const hidden=$(`uf-em-${group}`);if(hidden)hidden.value=value;
    syncNative();
  };

  const syncNative=()=>{
    if(!isUrgent()) return;
    const desc=$('uf-emergency-description');
    const native=[...document.querySelectorAll('[name="details"]')].filter(x=>x!==desc);
    native.forEach(x=>{if(!x.dataset.emOriginalName)x.dataset.emOriginalName=x.getAttribute('name')||'';x.removeAttribute('name');});
    if(desc) desc.setAttribute('name','details');

    const state=$('uf-em-state')?.value||'';
    const started=$('uf-em-started')?.value||'';
    const impact=$('uf-em-impact')?.value||'';
    const safety=$('uf-em-safety')?.value||'';
    const site=$('uf-em-site-status')?.value||'';

    const mappings={equipment_state:state,problem_started:started};
    Object.entries(mappings).forEach(([name,value])=>{
      const target=document.querySelector(`[name="${name}"]`)||$(name);
      if(target&&value){target.value=value;target.dispatchEvent(new Event('change',{bubbles:true}));}
    });

    const warning=$('uf-emergency-alert');
    if(warning) warning.classList.toggle('show',safety==='YES'||impact==='SAFETY');

    const form=$('maintenance-form');
    if(form){
      let meta=$('uf-emergency-meta');
      if(!meta){meta=document.createElement('input');meta.type='hidden';meta.id='uf-emergency-meta';meta.name='emergency_context';form.appendChild(meta);}
      meta.value=JSON.stringify({equipment_state:state,impact,safety,site_status:site,started});
    }
  };

  const restoreRoutine=()=>{
    const pane=document.querySelector('.uf-details-pane');if(!pane)return;
    pane.classList.remove('uf-emergency-details');
    const desc=$('uf-emergency-description');if(desc)desc.removeAttribute('name');
    document.querySelectorAll('[data-em-original-name]').forEach(x=>{if(x.dataset.emOriginalName)x.setAttribute('name',x.dataset.emOriginalName);delete x.dataset.emOriginalName;});
  };

  const render=()=>{
    if(applying)return;
    const pane=document.querySelector('.uf-details-pane'),fields=$('uf-detail-fields');
    if(!pane||!fields)return;
    if(!isUrgent()){restoreRoutine();return;}
    if($('uf-emergency-fields')){syncNative();return;}
    applying=true;
    pane.classList.add('uf-emergency-details');
    const title=pane.querySelector('#uf-detail-heading,.uf-pane-copy b');if(title)title.textContent='تفاصيل العطل الطارئ والمرفقات';
    const help=pane.querySelector('#uf-detail-help,.uf-pane-copy small');if(help)help.textContent='حدد حالة العطل وتأثيره والمخاطر بشكل واضح لتسريع الاستجابة';

    fields.innerHTML=`<div class="uf-emergency-fields" id="uf-emergency-fields">
      <div class="uf-emergency-description"><label>وصف العطل الطارئ <span class="req">*</span></label><textarea id="uf-emergency-description" required maxlength="5000" placeholder="اكتب وصفاً مختصراً وواضحاً للعطل، ما الذي حدث وما الذي توقف..."></textarea></div>
      <div class="uf-emergency-block"><label>حالة المعدة حالياً <span class="req">*</span></label><div class="uf-emergency-chips"><button type="button" class="uf-emergency-chip" data-em-group="state" data-value="تعمل">تعمل</button><button type="button" class="uf-emergency-chip" data-em-group="state" data-value="تعمل جزئياً" data-kind="warning">تعمل جزئياً</button><button type="button" class="uf-emergency-chip" data-em-group="state" data-value="متوقفة" data-kind="danger">متوقفة</button></div><input type="hidden" id="uf-em-state"></div>
      <div class="uf-emergency-block"><label>تأثير العطل <span class="req">*</span></label><div class="uf-emergency-chips"><button type="button" class="uf-emergency-chip" data-em-group="impact" data-value="LIMITED">تأثير محدود</button><button type="button" class="uf-emergency-chip" data-em-group="impact" data-value="PARTIAL_SITE" data-kind="warning">توقف جزئي</button><button type="button" class="uf-emergency-chip" data-em-group="impact" data-value="SITE_DOWN" data-kind="danger">توقف كامل</button></div><input type="hidden" id="uf-em-impact"></div>
      <div class="uf-emergency-row">
        <div class="uf-emergency-select"><label>هل يوجد خطر سلامة؟ <span class="req">*</span></label><select id="uf-em-safety"><option value="">اختر</option><option value="NO">لا</option><option value="YES">نعم</option><option value="UNKNOWN">غير معروف</option></select></div>
        <div class="uf-emergency-select"><label>هل الموقع يعمل حالياً؟ <span class="req">*</span></label><select id="uf-em-site-status"><option value="">اختر</option><option value="FULL">نعم بالكامل</option><option value="PARTIAL">جزئياً</option><option value="DOWN">متوقف</option></select></div>
      </div>
      <div class="uf-emergency-block"><label>متى بدأ العطل؟ <span class="req">*</span></label><div class="uf-emergency-chips"><button type="button" class="uf-emergency-chip" data-em-group="started" data-value="اليوم">اليوم</button><button type="button" class="uf-emergency-chip" data-em-group="started" data-value="منذ عدة أيام">منذ عدة أيام</button><button type="button" class="uf-emergency-chip" data-em-group="started" data-value="متكررة">متكررة</button></div><input type="hidden" id="uf-em-started"></div>
      <div class="uf-emergency-alert" id="uf-emergency-alert">يوجد مؤشر خطر سلامة. يرجى وصف الخطر بوضوح في وصف العطل وعدم الاقتراب من المعدة إذا كان ذلك غير آمن.</div>
    </div>`;

    fields.querySelectorAll('[data-em-group]').forEach(btn=>btn.addEventListener('click',()=>setChoice(btn.dataset.emGroup,btn.dataset.value,btn)));
    ['uf-em-safety','uf-em-site-status','uf-emergency-description'].forEach(id=>$(id)?.addEventListener(id==='uf-emergency-description'?'input':'change',syncNative));
    syncNative();
    applying=false;
  };

  const schedule=()=>{setTimeout(render,0);setTimeout(render,80);setTimeout(render,260)};
  document.addEventListener('change',e=>{if(['service-type','service-subtype'].includes(e.target?.id))schedule();});
  const observer=new MutationObserver(()=>{if(isUrgent()&&!applying&&!$('uf-emergency-fields'))schedule();});
  const start=()=>{const pane=document.querySelector('.uf-details-pane');if(pane){observer.observe(pane,{childList:true,subtree:true});schedule();return true;}return false;};
  if(!start()){let n=0,t=setInterval(()=>{if(start()||++n>40)clearInterval(t)},50);}
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
