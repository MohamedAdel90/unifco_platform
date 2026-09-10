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

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="routine-form"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-emergency-issue-details-v2">
.uf-details-pane.uf-emergency-details{display:flex!important;flex-direction:column!important}
.uf-details-pane.uf-emergency-details .uf-pane-head{margin-bottom:10px!important}
.uf-details-pane.uf-emergency-details .uf-pane-copy b{color:#08295d!important}
.uf-emergency-fields{display:grid!important;gap:10px!important}
.uf-emergency-description label,.uf-emergency-block>label{display:flex!important;align-items:center!important;gap:6px!important;margin:0 0 5px!important;color:#08295d!important;font-size:10.5px!important;font-weight:900!important}
.uf-emergency-description textarea{height:88px!important;min-height:88px!important;resize:none!important;border:1px solid #cfdbea!important;border-radius:8px!important;padding:9px 11px!important;font:600 10.5px Cairo,Tahoma,Arial,sans-serif!important;color:#233754!important;background:#fff!important}
.uf-emergency-description textarea:focus{border-color:#7f9fca!important;box-shadow:0 0 0 3px rgba(35,128,244,.05)!important;outline:none!important}
.uf-emergency-grid2{display:grid!important;grid-template-columns:1fr 1fr!important;gap:10px!important;align-items:start!important}
.uf-emergency-chips{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:7px!important}
.uf-emergency-chips.two{grid-template-columns:repeat(2,minmax(0,1fr))!important}
.uf-emergency-chip{height:38px!important;min-height:38px!important;padding:0 8px!important;border:1px solid #d4dfec!important;border-radius:7px!important;background:#fff!important;color:#143a70!important;font:900 9.4px Cairo!important;cursor:pointer!important;white-space:nowrap!important;display:flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;transition:.15s ease!important}
.uf-emergency-chip:hover{transform:translateY(-1px)!important;box-shadow:0 4px 10px rgba(7,31,77,.05)!important}
.uf-emergency-chip .em-ico{font-size:13px!important;font-weight:900!important;line-height:1!important}
.uf-emergency-chip.em-blue{border-color:#9fc8ff!important;background:#f8fbff!important;color:#0f5ac6!important}.uf-emergency-chip.em-blue.active{border-color:#2380f4!important;background:#eaf4ff!important;box-shadow:inset 0 0 0 1px #2380f4!important}
.uf-emergency-chip.em-green{border-color:#a9dfbf!important;background:#f2fcf6!important;color:#0b8a43!important}.uf-emergency-chip.em-green.active{border-color:#43b879!important;background:#e4f8ec!important;box-shadow:inset 0 0 0 1px #43b879!important}
.uf-emergency-chip.em-amber{border-color:#f0cb80!important;background:#fff9ed!important;color:#b87500!important}.uf-emergency-chip.em-amber.active{border-color:#e5a82d!important;background:#fff3d9!important;box-shadow:inset 0 0 0 1px #e5a82d!important}
.uf-emergency-chip.em-red{border-color:#f2aab3!important;background:#fff4f5!important;color:#d61d32!important}.uf-emergency-chip.em-red.active{border-color:#e63449!important;background:#ffe8eb!important;box-shadow:inset 0 0 0 1px #e63449!important}
.uf-emergency-priority{height:38px;border:1px solid #ee7786;border-radius:7px;background:#fff0f2;color:#d61d32;display:flex;align-items:center;justify-content:center;gap:8px;font:900 11px Cairo}
.uf-emergency-priority-note{margin-top:4px;text-align:center;color:#8190a6;font-size:8.5px;font-weight:700}
.uf-emergency-alert{margin:0!important;padding:9px 11px!important;border:1px solid #f0adb6!important;border-radius:8px!important;background:#fff2f4!important;color:#bb2335!important;font-size:9px!important;font-weight:800!important;line-height:1.7!important;display:flex!important;align-items:flex-start!important;gap:7px!important}.uf-emergency-alert b{display:block;color:#c7172c;font-size:10px;margin-bottom:1px}.uf-emergency-alert .em-alert-icon{font-size:14px;line-height:1.4}
.uf-details-pane.uf-emergency-details .uf-attach-title{margin:10px 0 7px!important}
.uf-details-pane.uf-emergency-details .uf-upload-row{min-height:49px!important;margin-bottom:6px!important;padding-top:6px!important;padding-bottom:6px!important}
@media(max-width:650px){.uf-emergency-grid2{grid-template-columns:1fr!important}.uf-emergency-chips,.uf-emergency-chips.two{grid-template-columns:1fr!important}.uf-emergency-chip{height:38px!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-emergency-issue-details-script-v2">
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
    const impact=$('uf-em-impact')?.value||'';
    const safety=$('uf-em-safety')?.value||'';
    const site=$('uf-em-site-status')?.value||'';
    const started=$('uf-em-started')?.value||'';

    const mappings={equipment_state:state,problem_started:started};
    Object.entries(mappings).forEach(([name,value])=>{
      const target=document.querySelector(`[name="${name}"]`)||$(name);
      if(target&&value){target.value=value;target.dispatchEvent(new Event('change',{bubbles:true}));}
    });

    const form=$('maintenance-form')||document.querySelector('form');
    if(form){
      let meta=$('uf-emergency-meta');
      if(!meta){meta=document.createElement('input');meta.type='hidden';meta.id='uf-emergency-meta';meta.name='emergency_context';form.appendChild(meta);}
      meta.value=JSON.stringify({equipment_state:state,impact,safety,site_status:site,started,priority:'EMERGENCY'});
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
    const help=pane.querySelector('#uf-detail-help,.uf-pane-copy small');if(help)help.textContent='يرجى توضيح تفاصيل العطل الطارئ لضمان الاستجابة السريعة';

    fields.innerHTML=`<div class="uf-emergency-fields" id="uf-emergency-fields">
      <div class="uf-emergency-description"><label>وصف العطل الطارئ <span class="req">*</span></label><textarea id="uf-emergency-description" required maxlength="5000" placeholder="اكتب وصفاً واضحاً ومختصراً للعطل الطارئ (ما المشكلة؟ متى حدثت؟ وما أثرها على التشغيل؟)"></textarea></div>

      <div class="uf-emergency-grid2">
        <div class="uf-emergency-block"><label>حالة المعدة الآن <span class="req">*</span></label><div class="uf-emergency-chips"><button type="button" class="uf-emergency-chip em-green" data-em-group="state" data-value="RUNNING"><span class="em-ico">▶</span> تعمل</button><button type="button" class="uf-emergency-chip em-amber" data-em-group="state" data-value="PARTIAL"><span class="em-ico">Ⅱ</span> تعمل جزئياً</button><button type="button" class="uf-emergency-chip em-red" data-em-group="state" data-value="STOPPED"><span class="em-ico">■</span> متوقفة</button></div><input type="hidden" id="uf-em-state"></div>
        <div class="uf-emergency-block"><label>تأثير العطل على التشغيل <span class="req">*</span></label><div class="uf-emergency-chips"><button type="button" class="uf-emergency-chip em-blue" data-em-group="impact" data-value="LIMITED"><span class="em-ico">▥</span> محدود</button><button type="button" class="uf-emergency-chip em-amber" data-em-group="impact" data-value="PARTIAL_SITE"><span class="em-ico">▲</span> توقف جزئي</button><button type="button" class="uf-emergency-chip em-red" data-em-group="impact" data-value="SITE_DOWN"><span class="em-ico">!</span> توقف كامل</button></div><input type="hidden" id="uf-em-impact"></div>
      </div>

      <div class="uf-emergency-grid2">
        <div class="uf-emergency-block"><label>هل يوجد خطر على السلامة؟ <span class="req">*</span></label><div class="uf-emergency-chips two"><button type="button" class="uf-emergency-chip em-green" data-em-group="safety" data-value="NO"><span class="em-ico">✓</span> لا</button><button type="button" class="uf-emergency-chip em-red" data-em-group="safety" data-value="YES"><span class="em-ico">▲</span> نعم</button></div><input type="hidden" id="uf-em-safety"></div>
        <div class="uf-emergency-block"><label>هل الموقع يعمل حالياً؟ <span class="req">*</span></label><div class="uf-emergency-chips two"><button type="button" class="uf-emergency-chip em-green" data-em-group="site-status" data-value="FULL"><span class="em-ico">✓</span> يعمل</button><button type="button" class="uf-emergency-chip em-red" data-em-group="site-status" data-value="DOWN"><span class="em-ico">−</span> متوقف</button></div><input type="hidden" id="uf-em-site-status"></div>
      </div>

      <div class="uf-emergency-grid2">
        <div class="uf-emergency-block"><label>متى بدأ العطل؟ <span class="req">*</span></label><div class="uf-emergency-chips"><button type="button" class="uf-emergency-chip em-blue" data-em-group="started" data-value="الآن"><span class="em-ico">ϟ</span> الآن</button><button type="button" class="uf-emergency-chip em-blue" data-em-group="started" data-value="منذ عدة ساعات"><span class="em-ico">◷</span> منذ عدة ساعات</button><button type="button" class="uf-emergency-chip em-blue" data-em-group="started" data-value="منذ عدة أيام"><span class="em-ico">▣</span> منذ عدة أيام</button></div><input type="hidden" id="uf-em-started"></div>
        <div class="uf-emergency-block"><label>الأولوية المطلوبة <span class="req">*</span></label><div class="uf-emergency-priority"><span>▲</span><span>طارئ</span></div><div class="uf-emergency-priority-note">سيتم التعامل مع هذا الطلب بأعلى أولوية وفقاً لاتفاقية مستوى الخدمة.</div></div>
      </div>

      <div class="uf-emergency-alert"><span class="em-alert-icon">▲</span><div><b>تنبيه سلامة</b>في حال وجود خطر على السلامة، يرجى التأكد من اتخاذ الإجراءات الاحترازية اللازمة وإبلاغ مسؤول الموقع فوراً.</div></div>
    </div>`;

    fields.querySelectorAll('[data-em-group]').forEach(btn=>btn.addEventListener('click',()=>setChoice(btn.dataset.emGroup,btn.dataset.value,btn)));
    $('uf-emergency-description')?.addEventListener('input',syncNative);
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
