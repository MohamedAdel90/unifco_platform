<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicNewCustomerEquipmentPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="new-customer-panel"') || str_contains($html, 'id="new-customer-equipment-panel"')) {
            return $response;
        }

        $equipmentPanel = <<<'HTML'
<section class="panel new-customer-equipment-panel" id="new-customer-equipment-panel">
<h2 class="section-title"><span class="num">2</span> المعدة وبياناتها</h2>
<div class="hint new-equipment-direct-hint">أدخل بيانات المعدة مباشرة.</div>
<div class="new-customer-grid">
<div class="field"><label>اسم المعدة <span class="req">*</span></label><input id="new_equipment_name" placeholder="اسم المعدة"></div>
<div class="field"><label>نوع المعدة <span class="req">*</span></label><select id="new_equipment_type"><option value="">اختر نوع المعدة</option><option value="HVAC">تكييف وتهوية</option><option value="ELECTRICAL">كهرباء</option><option value="GENERATOR">مولد</option><option value="UPS">UPS</option><option value="PUMP">مضخة</option><option value="FIRE_SYSTEM">نظام مكافحة حريق</option><option value="PLUMBING">سباكة</option><option value="ELEVATOR">مصعد</option><option value="OTHER">أخرى</option></select></div>
<div class="field"><label>الشركة المصنعة</label><input id="new_equipment_brand" placeholder="الشركة المصنعة"></div>
<div class="field"><label>الموديل</label><input id="new_equipment_model" placeholder="رقم / اسم الموديل"></div>
<div class="field"><label>السيريال نمبر</label><input id="new_equipment_serial" placeholder="Serial Number"></div>
<div class="field"><label>رقم الأصل إن وجد</label><input id="new_equipment_asset_no" placeholder="Asset No."></div>
<div class="field"><label>حالة المعدة</label><select id="new_equipment_status"><option value="">اختر الحالة</option><option value="STOPPED">متوقفة</option><option value="PARTIAL">تعمل جزئيًا</option><option value="WORKING_WITH_ISSUE">تعمل مع وجود عطل</option><option value="UNKNOWN">غير محدد</option></select></div>
</div>
</section>
HTML;

        $html = str_replace(
            '</section><section class="panel" id="current-customer-panel">',
            '</section>'.$equipmentPanel.'<section class="panel" id="current-customer-panel">',
            $html
        );

        $styles = <<<'HTML'
<style id="unifco-new-customer-equipment-style-v2">
.new-customer-equipment-panel{display:none}.new-customer-equipment-panel.show{display:block}.new-customer-equipment-panel .new-equipment-direct-hint{margin:-5px 0 13px!important}.new-customer-equipment-panel select{height:42px}
body.uf-new-customer-mode .uf-customer-context-layout,body.uf-new-customer-mode #current-customer-panel,body.uf-new-customer-mode #contract-section,body.uf-new-customer-mode #site-section,body.uf-new-customer-mode #asset-section{display:none!important}
body.uf-new-customer-mode #future-box{display:none!important}
body.uf-new-customer-mode .uf-workspace{grid-template-columns:minmax(0,1fr)!important;grid-template-areas:"details"!important}
body.uf-new-customer-mode .uf-workspace .uf-asset-pane{display:none!important}
body.uf-new-customer-mode .uf-workspace .uf-details-pane{grid-area:details!important;width:100%!important}
</style>
HTML;
        $html = str_replace('</head>', $styles.'</head>', $html);

        $script = <<<'HTML'
<script id="unifco-new-customer-equipment-script-v2">
(()=>{
const $=id=>document.getElementById(id),form=$('maintenance-form'),panel=$('new-customer-panel'),equipmentPanel=$('new-customer-equipment-panel'),routine=$('routine-form'),service=$('service-type'),subtype=$('service-subtype');
if(!form||!panel||!equipmentPanel)return;
const ids=['new_equipment_name','new_equipment_type'];
function hidden(name,value){let el=form.querySelector('input[data-new-equipment="'+name+'"]');if(!el){el=document.createElement('input');el.type='hidden';el.name=name;el.dataset.newEquipment=name;form.appendChild(el)}el.value=value||'';el.disabled=!panel.classList.contains('show');return el}
function sync(){if(!panel.classList.contains('show'))return;
 const equipmentName=$('new_equipment_name')?.value.trim()||'';
 const type=$('new_equipment_type')?.value||'GENERAL';
 const brand=$('new_equipment_brand')?.value.trim()||'';
 const model=$('new_equipment_model')?.value.trim()||'';
 const serial=$('new_equipment_serial')?.value.trim()||'';
 const assetNo=$('new_equipment_asset_no')?.value.trim()||'';
 const status=$('new_equipment_status')?.value||'';
 hidden('site_name',$('new_company_name')?.value||'موقع العميل الجديد');
 hidden('asset_type',type);hidden('equipment_brand',brand);hidden('equipment_model',model);
 const baseAddress=[$('new_district')?.value,$('new_address')?.value].filter(Boolean).join(' - ');hidden('site_address',baseAddress);
}
['new_equipment_name','new_equipment_type','new_equipment_brand','new_equipment_model','new_equipment_serial','new_equipment_asset_no','new_equipment_status'].forEach(id=>{const el=$(id);el?.addEventListener('input',sync);el?.addEventListener('change',sync)});
const equipmentLines=()=>{
 const english=document.documentElement.lang==='en';
 const value=id=>$(id)?.value.trim()||'';
 const selected=id=>$(id)?.selectedOptions?.[0]?.textContent.trim()||value(id);
 return [
  english?'[New customer equipment]':'[بيانات معدة العميل الجديد]',
  (english?'Equipment name: ':'اسم المعدة: ')+value('new_equipment_name'),
  (english?'Equipment type: ':'نوع المعدة: ')+selected('new_equipment_type'),
  (english?'Manufacturer: ':'الشركة المصنعة: ')+(value('new_equipment_brand')||'—'),
  (english?'Model: ':'الموديل: ')+(value('new_equipment_model')||'—'),
  (english?'Serial number: ':'السيريال نمبر: ')+(value('new_equipment_serial')||'—'),
  (english?'Asset number: ':'رقم الأصل: ')+(value('new_equipment_asset_no')||'—'),
  (english?'Equipment status: ':'حالة المعدة: ')+(selected('new_equipment_status')||'—')
 ];
};
const appendEquipmentDetails=()=>{const details=form.querySelector('textarea[name="details"]:not(:disabled)');if(!details)return;const clean=(details.value||'').split(/\n\n\[(?:بيانات معدة العميل الجديد|New customer equipment)\]\n/)[0].trim();details.value=(clean?clean+'\n\n':'')+equipmentLines().join('\n')};
const syncWorkspace=()=>{const isNew=panel.classList.contains('show'),workspace=$('uf-request-workspace'),detailsNum=document.querySelector('.uf-details-pane .uf-pane-head .num');document.body.classList.toggle('uf-new-customer-mode',isNew);workspace?.classList.toggle('new-customer-mode',isNew);if(detailsNum)detailsNum.textContent=isNew?'3':'5'};
const syncKind=()=>{const isNew=panel.classList.contains('show');equipmentPanel.classList.toggle('show',isNew);ids.forEach(id=>{const el=$(id);if(el)el.required=isNew});form.querySelectorAll('[data-new-equipment]').forEach(el=>el.disabled=!isNew);if(isNew){routine?.classList.remove('hidden','uf-core-only');sync()}syncWorkspace()};
document.querySelectorAll('[data-customer-kind]').forEach(btn=>btn.addEventListener('click',()=>setTimeout(syncKind,0)));
const keepNewFormVisible=()=>setTimeout(()=>{if(panel.classList.contains('show')){routine?.classList.remove('hidden','uf-core-only');syncWorkspace()}},0);
service?.addEventListener('change',keepNewFormVisible);subtype?.addEventListener('change',keepNewFormVisible);
form.addEventListener('submit',e=>{if(!panel.classList.contains('show'))return;e.stopImmediatePropagation();sync();if(ids.some(id=>!$(id)?.value.trim())){e.preventDefault();alert(document.documentElement.lang==='en'?'Please complete the equipment name and type.':'يرجى استكمال اسم المعدة ونوعها.');return}appendEquipmentDetails()},true);
syncKind();
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);

        $response->setContent($html);
        return $response;
    }
}
