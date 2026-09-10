<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentSparePartsQuoteFinalPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="service-type"') || ! str_contains($html, 'id="routine-form"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-spare-parts-quote-final-v1">
#uf-request-workspace.uf-spare-final-active .uf-attach-title,
#uf-request-workspace.uf-spare-final-active #uf-upload-rows,
#uf-request-workspace.uf-spare-final-active #uf-files-summary{display:none!important}
.uf-spf{direction:rtl;color:#08295d;font-family:Cairo,Tahoma,Arial,sans-serif}
.uf-spf-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 15px;border:1.5px solid #2d7ff0;border-radius:11px;background:linear-gradient(90deg,#fbfdff,#eaf5ff);margin-bottom:10px}
.uf-spf-head-main{display:flex;align-items:center;gap:10px}.uf-spf-link{font-size:24px;color:#1769c2}.uf-spf-head b{display:block;font-size:14px}.uf-spf-head small{display:block;color:#7a8da6;font-size:9px;margin-top:2px}.uf-spf-dot{width:20px;height:20px;border-radius:50%;border:2px solid #2d7ff0;background:#2d7ff0;box-shadow:inset 0 0 0 4px #fff}
.uf-spf-box{border:1px solid #dbe7f4;border-radius:11px;background:#fff;padding:11px;margin-top:9px}.uf-spf-title{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:9px}.uf-spf-title b{font-size:12px}.uf-spf-title small{font-size:8.5px;color:#8393a8}
.uf-spf-assets{display:flex;gap:8px;flex-wrap:wrap}.uf-spf-asset{flex:1 1 190px;max-width:280px;display:grid;grid-template-columns:30px 1fr;gap:8px;align-items:center;padding:9px 10px;border:1px solid #d7e4f3;border-radius:8px;background:#fbfdff}.uf-spf-asset i{width:29px;height:29px;border-radius:7px;background:#edf6ff;display:grid;place-items:center;font-style:normal;color:#1769c2}.uf-spf-asset small{display:block;color:#8393a8;font-size:7.5px}.uf-spf-asset b{display:block;font-size:9.5px;color:#173866;overflow-wrap:anywhere}
.uf-spf-table-wrap{overflow-x:auto;border:1px solid #e0e8f2;border-radius:8px}.uf-spf-table{width:100%;min-width:650px;border-collapse:collapse}.uf-spf-table th{padding:8px;background:#f1f5fa;color:#173866;font-size:8px;white-space:nowrap}.uf-spf-table td{padding:8px;border-top:1px solid #edf2f7;text-align:center;font-size:8.5px;color:#294b73}.uf-spf-table td.n{text-align:right;font-weight:800;color:#08295d}.uf-spf-qty{width:64px!important;height:31px!important;text-align:center;padding:3px!important}.uf-spf-add{height:31px;border:0;border-radius:6px;background:#08295d;color:#fff;padding:0 12px;font:900 8.5px Cairo;cursor:pointer}.uf-spf-empty{padding:18px;text-align:center;color:#778ca6;font-size:9px}
.uf-spf-selected{margin-top:9px;padding:9px;background:#eef7ff;border-radius:8px;display:flex;gap:7px;flex-wrap:wrap}.uf-spf-selected>strong{width:100%;font-size:9.5px}.uf-spf-item{min-width:190px;display:flex;align-items:center;justify-content:space-between;gap:7px;background:#fff;border:1px solid #d7e5f4;border-radius:7px;padding:7px 9px}.uf-spf-item b{font-size:8.5px}.uf-spf-item small{display:block;font-size:7px;color:#7388a3}.uf-spf-remove{width:23px;height:23px;border:1px solid #efabb5;border-radius:50%;background:#fff6f7;color:#d71932;cursor:pointer}
.uf-spf-grid{display:grid;grid-template-columns:1.15fr 1fr 1fr;gap:9px;align-items:end}.uf-spf-field label{display:block;font-size:8.5px;font-weight:900;margin-bottom:5px}.uf-spf-read{height:37px;border:1px solid #d2dfed;border-radius:7px;background:#f8fbff;padding:9px;color:#385474;font-size:8.5px}.uf-spf-options{display:flex;gap:6px;flex-wrap:wrap}.uf-spf-option{height:35px;border:1px solid #cfdae8;background:#fff;border-radius:7px;padding:0 10px;color:#274b76;font:800 8.5px Cairo;cursor:pointer}.uf-spf-option.active{border-color:#2d7ff0;background:#eef6ff;color:#1769c2}.uf-spf-notes{grid-column:1/-1}.uf-spf-notes textarea{height:72px!important;min-height:72px!important}.uf-spf-hidden{position:absolute!important;left:-9999px!important;width:1px!important;height:1px!important;opacity:0!important}
@media(max-width:900px){.uf-spf-grid{grid-template-columns:1fr 1fr}.uf-spf-notes{grid-column:1/-1}}
@media(max-width:600px){.uf-spf-head{padding:10px}.uf-spf-head b{font-size:12px}.uf-spf-box{padding:8px}.uf-spf-grid{grid-template-columns:1fr}.uf-spf-notes{grid-column:auto}.uf-spf-asset{max-width:none;flex-basis:100%}.uf-spf-table{min-width:610px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-spare-parts-quote-final-script-v1">
(()=>{
 const $=id=>document.getElementById(id);
 const api='/request-service/asset-parts';
 const assets=new Map(),parts=new Map(),chosen=new Map(),loading=new Set();
 let oldPart='نعم',supply='توريد',notes='',renderLock=false,lastAsset='';
 const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const els=()=>({service:$('service-type'),subtype:$('service-subtype'),assetList:$('asset-list'),assetId:$('asset_id'),customer:$('customer_number'),contract:$('contract_no'),fields:$('uf-detail-fields'),workspace:$('uf-request-workspace')});
 const selectedId=e=>String(e.assetId?.value||e.assetList?.value||'').trim();
 const isTarget=e=>!!e.service&&!!e.subtype&&e.service.value==='quotation'&&e.subtype.value==='parts'&&!!e.customer?.value&&!!e.contract?.value&&!!selectedId(e);
 const snap=e=>{const opt=e.assetList?.selectedOptions?.[0],text=opt?.textContent||'';return {id:selectedId(e),code:($('ac-code')?.textContent||text.match(/AST-[\w-]+/i)?.[0]||selectedId(e)).trim(),name:($('ac-name')?.textContent||text.split('—')[0]||'أصل مسجل').trim()}};
 const key=(p,i)=>`${p.asset_id}-${p.id||p.item_id||p.part_no||i}`;
 async function load(a,e){if(!a.id||parts.has(a.id)||loading.has(a.id))return;loading.add(a.id);draw();try{const q=new URLSearchParams({customer_number:e.customer.value,contract_no:e.contract.value,asset_id:a.id});const r=await fetch(api+'?'+q,{headers:{Accept:'application/json'},cache:'no-store'}),d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر تحميل قطع الغيار');const aa=d.asset||{};assets.set(a.id,{id:a.id,code:aa.asset_code||a.code,name:aa.name||a.name});parts.set(a.id,d.parts||[])}catch(x){parts.set(a.id,{error:x.message})}loading.delete(a.id);draw()}
 function allParts(){const out=[];for(const a of assets.values()){const rows=parts.get(a.id);if(Array.isArray(rows))rows.forEach(p=>out.push({...p,asset_code:p.asset_code||a.code,asset_name:p.asset_name||a.name}))}return out}
 function sync(){const ta=$('uf-spf-details');if(!ta)return;const lines=[...chosen.values()].map(x=>`${x.asset_code} | ${x.name} | Part No: ${x.part_no||'-'} | Qty: ${x.qty}`);ta.value=`طلب عرض سعر قطع غيار مرتبطة بالأصل\n${lines.length?lines.join('\n'):'لم يتم اختيار قطع بعد'}\nالقطعة القديمة متاحة للفحص: ${oldPart}\nنوع التوريد: ${supply}\nموقع تسليم القطع المطلوبة: ${$('site_name')?.value||'-'}${notes?`\nملاحظات إضافية: ${notes}`:''}`}
 function draw(){const e=els();if(renderLock||!isTarget(e)||!e.fields||!e.workspace)return;renderLock=true;e.workspace.classList.add('uf-spare-final-active','uf-spare-linked-active');if($('uf-detail-heading'))$('uf-detail-heading').textContent='طلب عرض سعر لقطع الغيار';if($('uf-detail-help'))$('uf-detail-help').textContent='اختر قطع الغيار المرتبطة بالأصل وحدد بيانات التوريد';const ap=allParts();let table='';if(loading.size)table='<div class="uf-spf-empty">جاري تحميل قطع الغيار المرتبطة بالأصل...</div>';else if(ap.length)table=`<div class="uf-spf-table-wrap"><table class="uf-spf-table"><thead><tr><th>#</th><th>اسم القطعة</th><th>رقم القطعة (Part No.)</th><th>رقم الأصل</th><th>الشركة المصنعة</th><th>الكمية المطلوبة</th><th>الإجراء</th></tr></thead><tbody>${ap.map((p,i)=>{const k=key(p,i);return `<tr><td>${i+1}</td><td class="n">${esc(p.name||'قطعة غيار')}</td><td dir="ltr">${esc(p.part_no||'—')}</td><td dir="ltr">${esc(p.asset_code||'—')}</td><td>${esc(p.manufacturer||'—')}</td><td><input class="uf-spf-qty" type="number" min="1" max="999" value="${chosen.get(k)?.qty||1}" data-qty="${esc(k)}"></td><td><button type="button" class="uf-spf-add" data-add="${esc(k)}">إضافة</button></td></tr>`}).join('')}</tbody></table></div>`;else{const er=[...parts.values()].find(v=>v?.error)?.error;table=`<div class="uf-spf-empty">${esc(er||'لا توجد قطع غيار مسجلة على الأصل المحدد.')}</div>`}const sel=[...chosen.values()];e.fields.innerHTML=`<div class="uf-spf" data-spare-final="1"><div class="uf-spf-head"><div class="uf-spf-head-main"><span class="uf-spf-link">🔗</span><span><b>قطع غيار مرتبطة بالأصل</b><small>عرض قطع الغيار المسجلة على الأصل المحدد</small></span></div><span class="uf-spf-dot"></span></div><div class="uf-spf-box"><div class="uf-spf-title"><b>الأصول المحددة (${assets.size})</b><small>اسم الأصل ورقم الأصل فقط</small></div><div class="uf-spf-assets">${[...assets.values()].map(a=>`<div class="uf-spf-asset"><i>◇</i><span><small>اسم الأصل</small><b>${esc(a.name)}</b><small>رقم الأصل</small><b dir="ltr">${esc(a.code)}</b></span></div>`).join('')}</div></div><div class="uf-spf-box"><div class="uf-spf-title"><b>قطع الغيار المرتبطة بهذا الأصل</b><small>يظهر رقم الأصل مع كل قطعة</small></div>${table}${sel.length?`<div class="uf-spf-selected"><strong>القطع المختارة (${sel.length})</strong>${sel.map(x=>`<div class="uf-spf-item"><span><b>${esc(x.name)}</b><small>${esc(x.part_no||'—')} · الأصل: ${esc(x.asset_code||'—')} · الكمية: ${x.qty}</small></span><button type="button" class="uf-spf-remove" data-remove="${esc(x.key)}">×</button></div>`).join('')}</div>`:''}</div><div class="uf-spf-box"><div class="uf-spf-title"><b>تفاصيل الطلب</b><small>البيانات الأساسية المطلوبة للتوريد</small></div><div class="uf-spf-grid"><div class="uf-spf-field"><label>موقع تسليم القطع المطلوبة <span class="req">*</span></label><div class="uf-spf-read">${esc($('site_name')?.value||'اختر الموقع من بيانات الطلب')}</div></div><div class="uf-spf-field"><label>هل القطعة القديمة متاحة للفحص؟</label><div class="uf-spf-options"><button type="button" class="uf-spf-option ${oldPart==='نعم'?'active':''}" data-old="نعم">نعم</button><button type="button" class="uf-spf-option ${oldPart==='لا'?'active':''}" data-old="لا">لا</button></div></div><div class="uf-spf-field"><label>نوع التوريد <span class="req">*</span></label><div class="uf-spf-options"><button type="button" class="uf-spf-option ${supply==='توريد'?'active':''}" data-supply="توريد">توريد</button><button type="button" class="uf-spf-option ${supply==='توريد وتركيب'?'active':''}" data-supply="توريد وتركيب">توريد وتركيب</button></div></div><div class="uf-spf-field uf-spf-notes"><label>ملاحظات إضافية</label><textarea id="uf-spf-notes" maxlength="500" placeholder="أضف أي ملاحظات أو متطلبات خاصة...">${esc(notes)}</textarea></div></div></div><textarea class="uf-spf-hidden" name="details" id="uf-spf-details"></textarea></div>`;
 e.fields.querySelectorAll('[data-add]').forEach(b=>b.onclick=()=>{const k=b.dataset.add,p=ap.find((p,i)=>key(p,i)===k);if(!p)return;const q=e.fields.querySelector(`[data-qty="${CSS.escape(k)}"]`),qty=Math.max(1,Math.min(999,Number(q?.value||1)));chosen.set(k,{...p,key:k,qty});renderLock=false;draw();sync()});e.fields.querySelectorAll('[data-remove]').forEach(b=>b.onclick=()=>{chosen.delete(b.dataset.remove);renderLock=false;draw();sync()});e.fields.querySelectorAll('[data-old]').forEach(b=>b.onclick=()=>{oldPart=b.dataset.old;renderLock=false;draw();sync()});e.fields.querySelectorAll('[data-supply]').forEach(b=>b.onclick=()=>{supply=b.dataset.supply;renderLock=false;draw();sync()});$('uf-spf-notes')?.addEventListener('input',ev=>{notes=ev.target.value;sync()});sync();renderLock=false}
 function tick(){const e=els();if(!isTarget(e)){e.workspace?.classList.remove('uf-spare-final-active');return}const a=snap(e);if(a.id&&a.id!==lastAsset){lastAsset=a.id;assets.set(a.id,a);load(a,e)}const f=e.fields;if(f&&!f.querySelector('[data-spare-final="1"]'))draw()}
 function boot(){const e=els();if(!e.service||!e.subtype)return setTimeout(boot,120);['change','input'].forEach(ev=>{e.service.addEventListener(ev,()=>setTimeout(tick,20));e.subtype.addEventListener(ev,()=>setTimeout(tick,20));e.assetList?.addEventListener(ev,()=>setTimeout(tick,20));e.assetId?.addEventListener(ev,()=>setTimeout(tick,20));e.customer?.addEventListener(ev,()=>setTimeout(tick,20));e.contract?.addEventListener(ev,()=>setTimeout(tick,20));$('site_id')?.addEventListener(ev,()=>setTimeout(tick,20))});new MutationObserver(()=>setTimeout(tick,10)).observe(document.body,{subtree:true,childList:true,attributes:true,attributeFilter:['class','value']});setInterval(tick,700);tick()}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);
        return $response;
    }
}
