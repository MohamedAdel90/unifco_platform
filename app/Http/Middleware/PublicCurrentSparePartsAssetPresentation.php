<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentSparePartsAssetPresentation
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
<style id="unifco-current-spare-parts-asset-v4">
#uf-request-workspace.uf-spare-linked-active .uf-attach-title,#uf-request-workspace.uf-spare-linked-active #uf-upload-rows,#uf-request-workspace.uf-spare-linked-active #uf-files-summary{display:none!important}.uf-spare-card{direction:rtl;color:#0b2d62}.uf-spare-choice{display:flex;align-items:center;justify-content:space-between;gap:12px;border:1.5px solid #2c7eea;background:linear-gradient(90deg,#f9fcff,#eaf5ff);border-radius:12px;padding:14px 16px;margin-bottom:12px}.uf-spare-choice-main{display:flex;align-items:center;gap:10px}.uf-spare-choice-icon{font-size:24px;color:#0b58b4}.uf-spare-choice strong{display:block;font-size:14px;color:#08295d}.uf-spare-choice small{display:block;font-size:9px;color:#7890ad;margin-top:2px}.uf-spare-choice-dot{width:20px;height:20px;border-radius:50%;border:2px solid #2d7ff0;box-shadow:inset 0 0 0 4px #fff;background:#2d7ff0}.uf-spare-section{border:1px solid #dbe7f5;border-radius:12px;background:#fff;padding:12px;margin-top:10px}.uf-spare-section-title{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}.uf-spare-section-title b{font-size:13px;color:#08295d}.uf-spare-section-title small{font-size:9px;color:#8191a8}.uf-spare-assets{display:flex;gap:8px;flex-wrap:wrap}.uf-spare-asset-chip{flex:1 1 170px;max-width:260px;border:1px solid #d8e5f4;border-radius:9px;background:#fbfdff;padding:10px 12px;display:grid;grid-template-columns:32px 1fr;gap:8px;align-items:center}.uf-spare-asset-icon{width:30px;height:30px;border-radius:8px;background:#edf6ff;display:grid;place-items:center;color:#1769c2;font-weight:900}.uf-spare-asset-chip small{display:block;color:#8293aa;font-size:8px}.uf-spare-asset-chip b{display:block;color:#12345f;font-size:10px;margin-top:2px;overflow-wrap:anywhere}.uf-spare-table-wrap{overflow-x:auto;border:1px solid #e2eaf4;border-radius:9px}.uf-spare-table{width:100%;min-width:650px;border-collapse:collapse;background:#fff}.uf-spare-table th{background:#f0f5fa;color:#12345f;font-size:8.5px;padding:8px;border-bottom:1px solid #dce6f1;white-space:nowrap}.uf-spare-table td{font-size:9px;color:#24466f;padding:8px;border-bottom:1px solid #edf2f7;text-align:center;vertical-align:middle}.uf-spare-table td.name{text-align:right;font-weight:800;color:#08295d}.uf-spare-qty{width:66px!important;height:32px!important;padding:4px 7px!important;text-align:center}.uf-spare-add{height:32px;border:0;border-radius:6px;background:#08295d;color:#fff;font:900 9px Cairo;padding:0 12px;cursor:pointer}.uf-spare-empty,.uf-spare-loading{padding:20px;text-align:center;color:#768ba5;font-size:10px}.uf-spare-selected{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;padding:10px;border-radius:9px;background:#eef7ff}.uf-spare-selected-title{width:100%;font-size:10px;font-weight:900;color:#0a3975}.uf-spare-selected-item{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;min-width:190px;background:#fff;border:1px solid #d6e5f5;border-radius:8px;padding:8px 10px}.uf-spare-selected-item b{font-size:9px;color:#08295d}.uf-spare-selected-item small{display:block;font-size:7.5px;color:#6f83a0;margin-top:2px}.uf-spare-remove{width:24px;height:24px;border:1px solid #f2aab4;background:#fff6f7;color:#d71932;border-radius:50%;cursor:pointer}.uf-spare-details-grid{display:grid;grid-template-columns:1.15fr 1fr 1fr;gap:9px;align-items:end}.uf-spare-field label{display:block;font-size:9px;font-weight:900;color:#0d376d;margin-bottom:5px}.uf-spare-readonly{height:38px;border:1px solid #d2dfed;border-radius:7px;background:#f8fbff;padding:9px 10px;color:#314e73;font-size:9px}.uf-spare-options{display:flex;gap:7px;flex-wrap:wrap}.uf-spare-option{height:36px;border:1px solid #cfddeb;background:#fff;border-radius:7px;padding:0 10px;font:800 9px Cairo;color:#254b78;cursor:pointer}.uf-spare-option.active{border-color:#2d7ff0;background:#eef6ff;color:#1769c2;box-shadow:inset 0 0 0 1px rgba(45,127,240,.08)}.uf-spare-notes{grid-column:1/-1}.uf-spare-notes textarea{min-height:76px!important;height:76px!important;resize:vertical}.uf-sp-hidden{position:absolute!important;left:-9999px!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important}@media(max-width:900px){.uf-spare-details-grid{grid-template-columns:1fr 1fr}.uf-spare-notes{grid-column:1/-1}}@media(max-width:600px){.uf-spare-choice{padding:11px}.uf-spare-choice strong{font-size:12px}.uf-spare-section{padding:9px}.uf-spare-details-grid{grid-template-columns:1fr}.uf-spare-notes{grid-column:auto}.uf-spare-asset-chip{max-width:none;flex-basis:100%}.uf-spare-table{min-width:610px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-current-spare-parts-asset-script-v4">
(()=>{
 const $=id=>document.getElementById(id);
 const service=$('service-type'),subtype=$('service-subtype'),assetList=$('asset-list'),assetId=$('asset_id'),customerNo=$('customer_number'),contract=$('contract_no');
 if(!service||!subtype||!assetList||!assetId)return;
 const api='/request-service/asset-parts';
 const selectedAssets=new Map(),partsByAsset=new Map(),selectedParts=new Map(),loadingAssets=new Set();
 let oldPart='نعم',supplyType='توريد',notes='',rendering=false;
 const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const currentAssetId=()=>String(assetId.value||assetList.value||'').trim();
 const target=()=>service.value==='quotation'&&subtype.value==='parts'&&!!contract?.value&&!!currentAssetId()&&!$('uf-manual')?.classList.contains('show');
 const assetSnapshot=()=>({id:currentAssetId(),code:($('ac-code')?.textContent||assetList.selectedOptions[0]?.textContent?.match(/AST-[\w-]+/i)?.[0]||assetList.value||'').trim(),name:($('ac-name')?.textContent||assetList.selectedOptions[0]?.textContent?.split('—')[0]||'أصل مسجل').trim()});
 async function loadAssetParts(a){
   if(!a.id||partsByAsset.has(a.id)||loadingAssets.has(a.id))return;
   loadingAssets.add(a.id);render();
   try{
     const p=new URLSearchParams({customer_number:customerNo?.value||'',contract_no:contract?.value||'',asset_id:a.id});
     const r=await fetch(api+'?'+p.toString(),{headers:{Accept:'application/json'}}),d=await r.json();
     if(!r.ok)throw new Error(d.message||'تعذر تحميل قطع الغيار المرتبطة بالأصل');
     const aa=d.asset||{};selectedAssets.set(a.id,{id:a.id,code:aa.asset_code||a.code,name:aa.name||a.name});partsByAsset.set(a.id,d.parts||[]);
   }catch(e){partsByAsset.set(a.id,{error:e.message})}
   loadingAssets.delete(a.id);render();
 }
 function collectParts(){const out=[];for(const a of selectedAssets.values()){const rows=partsByAsset.get(a.id);if(Array.isArray(rows))rows.forEach(p=>out.push({...p,asset_code:p.asset_code||a.code,asset_name:p.asset_name||a.name}))}return out}
 function syncDetails(){const details=$('uf-details');if(!details)return;const lines=[...selectedParts.values()].map(x=>`${x.asset_code} | ${x.name} | Part No: ${x.part_no||'-'} | Qty: ${x.qty}`);details.value=`طلب عرض سعر قطع غيار مرتبطة بالأصل\n${lines.length?lines.join('\n'):'لم يتم اختيار قطع بعد'}\nالقطعة القديمة متاحة للفحص: ${oldPart}\nنوع التوريد: ${supplyType}\nموقع تسليم القطع المطلوبة: ${$('site_name')?.value||'-'}${notes?`\nملاحظات إضافية: ${notes}`:''}`;details.dispatchEvent(new Event('input',{bubbles:true}))}
 function keyOf(p,i){return `${p.asset_id}-${p.id||p.item_id||p.part_no||i}`}
 function render(){
   if(rendering||!target())return;rendering=true;
   const workspace=$('uf-request-workspace'),fields=$('uf-detail-fields');if(!workspace||!fields){rendering=false;return}
   workspace.classList.add('uf-spare-linked-active');
   if($('uf-detail-heading'))$('uf-detail-heading').textContent='طلب عرض سعر لقطع الغيار';
   if($('uf-detail-help'))$('uf-detail-help').textContent='اختر قطع الغيار المرتبطة بالأصل المسجل وحدد بيانات التوريد';
   const assets=[...selectedAssets.values()],all=collectParts();let table='';
   if(loadingAssets.size){table='<div class="uf-spare-loading">جاري تحميل قطع الغيار المرتبطة بالأصل...</div>'}
   else if(all.length){table=`<div class="uf-spare-table-wrap"><table class="uf-spare-table"><thead><tr><th>#</th><th>اسم القطعة</th><th>رقم القطعة (Part No.)</th><th>رقم الأصل</th><th>الشركة المصنعة</th><th>الكمية المطلوبة</th><th>الإجراء</th></tr></thead><tbody>${all.map((p,i)=>{const k=keyOf(p,i);return `<tr><td>${i+1}</td><td class="name">${esc(p.name||'قطعة غيار')}</td><td dir="ltr">${esc(p.part_no||'—')}</td><td dir="ltr">${esc(p.asset_code||'—')}</td><td>${esc(p.manufacturer||'—')}</td><td><input class="uf-spare-qty" type="number" min="1" max="999" value="${selectedParts.get(k)?.qty||1}" data-qty="${esc(k)}"></td><td><button type="button" class="uf-spare-add" data-add="${esc(k)}">إضافة</button></td></tr>`}).join('')}</tbody></table></div>`}
   else{const err=[...partsByAsset.values()].find(v=>v&&v.error)?.error;table=`<div class="uf-spare-empty">${esc(err||'لا توجد قطع غيار مسجلة على الأصل المحدد حالياً.')}</div>`}
   const selected=[...selectedParts.values()];
   fields.innerHTML=`<div class="uf-spare-card"><div class="uf-spare-choice"><div class="uf-spare-choice-main"><span class="uf-spare-choice-icon">🔗</span><span><strong>قطع غيار مرتبطة بالأصل</strong><small>عرض قطع الغيار المسجلة على الأصل المحدد</small></span></div><span class="uf-spare-choice-dot"></span></div><div class="uf-spare-section"><div class="uf-spare-section-title"><b>الأصول المحددة (${assets.length})</b><small>اسم الأصل ورقم الأصل فقط</small></div><div class="uf-spare-assets">${assets.map(a=>`<div class="uf-spare-asset-chip"><span class="uf-spare-asset-icon">◇</span><span><small>اسم الأصل</small><b>${esc(a.name||'أصل مسجل')}</b><small>رقم الأصل</small><b dir="ltr">${esc(a.code||a.id)}</b></span></div>`).join('')}</div></div><div class="uf-spare-section"><div class="uf-spare-section-title"><b>قطع الغيار المرتبطة بهذا الأصل</b><small>يمكن اختيار قطع من أكثر من أصل، ويظهر رقم الأصل مع كل قطعة</small></div>${table}${selected.length?`<div class="uf-spare-selected"><div class="uf-spare-selected-title">القطع المختارة (${selected.length})</div>${selected.map(x=>`<div class="uf-spare-selected-item"><span><b>${esc(x.name)}</b><small>${esc(x.part_no||'—')} · الأصل: ${esc(x.asset_code||'—')} · الكمية: ${x.qty}</small></span><button type="button" class="uf-spare-remove" data-remove="${esc(x.key)}">×</button></div>`).join('')}</div>`:''}</div><div class="uf-spare-section"><div class="uf-spare-section-title"><b>تفاصيل الطلب</b><small>البيانات الأساسية المطلوبة للتوريد</small></div><div class="uf-spare-details-grid"><div class="uf-spare-field"><label>موقع تسليم القطع المطلوبة <span class="req">*</span></label><div class="uf-spare-readonly">${esc($('site_name')?.value||'اختر الموقع من بيانات الطلب')}</div></div><div class="uf-spare-field"><label>هل القطعة القديمة متاحة للفحص؟</label><div class="uf-spare-options"><button type="button" class="uf-spare-option ${oldPart==='نعم'?'active':''}" data-old="نعم">نعم</button><button type="button" class="uf-spare-option ${oldPart==='لا'?'active':''}" data-old="لا">لا</button></div></div><div class="uf-spare-field"><label>نوع التوريد <span class="req">*</span></label><div class="uf-spare-options"><button type="button" class="uf-spare-option ${supplyType==='توريد'?'active':''}" data-supply="توريد">توريد</button><button type="button" class="uf-spare-option ${supplyType==='توريد وتركيب'?'active':''}" data-supply="توريد وتركيب">توريد وتركيب</button></div></div><div class="uf-spare-field uf-spare-notes"><label>ملاحظات إضافية</label><textarea id="uf-spare-notes" maxlength="500" placeholder="أضف أي ملاحظات أو متطلبات خاصة...">${esc(notes)}</textarea></div></div></div><textarea class="uf-sp-hidden" name="details" id="uf-details"></textarea></div>`;
   fields.querySelectorAll('[data-add]').forEach(b=>b.addEventListener('click',()=>{const k=b.dataset.add,p=all.find((p,i)=>keyOf(p,i)===k);if(!p)return;const q=fields.querySelector(`[data-qty="${CSS.escape(k)}"]`);const qty=Math.max(1,Math.min(999,Number(q?.value||1)));selectedParts.set(k,{...p,key:k,qty});render();syncDetails()}));
   fields.querySelectorAll('[data-remove]').forEach(b=>b.addEventListener('click',()=>{selectedParts.delete(b.dataset.remove);render();syncDetails()}));
   fields.querySelectorAll('[data-old]').forEach(b=>b.addEventListener('click',()=>{oldPart=b.dataset.old;render();syncDetails()}));
   fields.querySelectorAll('[data-supply]').forEach(b=>b.addEventListener('click',()=>{supplyType=b.dataset.supply;render();syncDetails()}));
   $('uf-spare-notes')?.addEventListener('input',e=>{notes=e.target.value;syncDetails()});syncDetails();rendering=false;
 }
 function deactivate(){const w=$('uf-request-workspace');w?.classList.remove('uf-spare-linked-active')}
 function activate(){if(!target()){deactivate();return}const a=assetSnapshot();if(a.id){selectedAssets.set(a.id,a);loadAssetParts(a)}render()}
 const after=()=>setTimeout(activate,80);
 service.addEventListener('change',after);subtype.addEventListener('change',after);assetList.addEventListener('change',after);assetId.addEventListener('change',after);$('site_id')?.addEventListener('change',after);$('asset-find')?.addEventListener('click',()=>setTimeout(activate,300));
 customerNo?.addEventListener('input',after);contract?.addEventListener('change',()=>{selectedAssets.clear();partsByAsset.clear();selectedParts.clear();loadingAssets.clear();after()});
 const ac=$('ac-code');if(ac)new MutationObserver(after).observe(ac,{childList:true,subtree:true,characterData:true});
 setTimeout(activate,450);
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);
        return $response;
    }
}
