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

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="new-customer-panel"') || str_contains($html, 'id="new_project_name"')) {
            return $response;
        }

        $extraFields = <<<'HTML'
<div class="new-project-equipment" id="new-project-equipment">
<div class="new-subtitle">بيانات المشروع وموقع المعدة</div>
<div class="new-customer-grid">
<div class="field"><label>اسم المشروع <span class="req">*</span></label><input id="new_project_name" placeholder="مثال: مشروع المقر الرئيسي"></div>
<div class="field"><label>موقع المعدة / المبنى <span class="req">*</span></label><input id="new_equipment_location" placeholder="المبنى، الدور، الغرفة أو المنطقة"></div>
<div class="field"><label>وصف إضافي للموقع</label><input id="new_equipment_location_note" placeholder="مثال: غرفة الكهرباء B1"></div>
</div>
<div class="new-subtitle">بيانات المعدة</div>
<div class="new-customer-grid">
<div class="field"><label>اسم المعدة <span class="req">*</span></label><input id="new_equipment_name" placeholder="اسم المعدة"></div>
<div class="field"><label>نوع المعدة <span class="req">*</span></label><select id="new_equipment_type"><option value="">اختر نوع المعدة</option><option value="HVAC">تكييف وتهوية</option><option value="ELECTRICAL">كهرباء</option><option value="GENERATOR">مولد</option><option value="UPS">UPS</option><option value="PUMP">مضخة</option><option value="FIRE_SYSTEM">نظام مكافحة حريق</option><option value="PLUMBING">سباكة</option><option value="ELEVATOR">مصعد</option><option value="OTHER">أخرى</option></select></div>
<div class="field"><label>الشركة المصنعة</label><input id="new_equipment_brand" placeholder="الشركة المصنعة"></div>
<div class="field"><label>الموديل</label><input id="new_equipment_model" placeholder="رقم / اسم الموديل"></div>
<div class="field"><label>السيريال نمبر</label><input id="new_equipment_serial" placeholder="Serial Number"></div>
<div class="field"><label>رقم الأصل إن وجد</label><input id="new_equipment_asset_no" placeholder="Asset No."></div>
<div class="field"><label>حالة المعدة</label><select id="new_equipment_status"><option value="">اختر الحالة</option><option value="STOPPED">متوقفة</option><option value="PARTIAL">تعمل جزئيًا</option><option value="WORKING_WITH_ISSUE">تعمل مع وجود عطل</option><option value="UNKNOWN">غير محدد</option></select></div>
<div class="field span2"><label>وصف مختصر للمشكلة بالمعدة</label><input id="new_equipment_issue" placeholder="وصف مختصر يساعد الفريق الفني على الاستعداد"></div>
</div>
</div>
HTML;

        $html = str_replace(
            '<div class="new-customer-actions">',
            $extraFields.'<div class="new-customer-actions">',
            $html
        );

        $styles = <<<'HTML'
<style id="unifco-new-customer-equipment-style">
.new-project-equipment{margin-top:18px;padding-top:16px;border-top:1px solid #e8edf4}.new-subtitle{margin:0 0 10px;color:#0b2854;font-size:13px;font-weight:900}.new-project-equipment .new-customer-grid+.new-subtitle{margin-top:16px}.new-project-equipment select{height:42px}@media(max-width:700px){.new-project-equipment{margin-top:14px;padding-top:14px}}
</style>
HTML;
        $html = str_replace('</head>', $styles.'</head>', $html);

        $script = <<<'HTML'
<script id="unifco-new-customer-equipment-script">
(()=>{
const $=id=>document.getElementById(id),form=$('maintenance-form'),panel=$('new-customer-panel');
if(!form||!panel)return;
const ids=['new_project_name','new_equipment_location','new_equipment_name','new_equipment_type'];
function hidden(name,value){let el=form.querySelector('input[data-new-equipment="'+name+'"]');if(!el){el=document.createElement('input');el.type='hidden';el.name=name;el.dataset.newEquipment=name;form.appendChild(el)}el.value=value||'';el.disabled=!panel.classList.contains('show');return el}
function sync(){if(!panel.classList.contains('show'))return;
 const project=$('new_project_name')?.value.trim()||'';
 const location=$('new_equipment_location')?.value.trim()||'';
 const locationNote=$('new_equipment_location_note')?.value.trim()||'';
 const equipmentName=$('new_equipment_name')?.value.trim()||'';
 const type=$('new_equipment_type')?.value||'GENERAL';
 const brand=$('new_equipment_brand')?.value.trim()||'';
 const model=$('new_equipment_model')?.value.trim()||'';
 const serial=$('new_equipment_serial')?.value.trim()||'';
 const assetNo=$('new_equipment_asset_no')?.value.trim()||'';
 const status=$('new_equipment_status')?.value||'';
 const issue=$('new_equipment_issue')?.value.trim()||'';
 hidden('site_name',project||$('new_company_name')?.value||'موقع العميل الجديد');
 hidden('asset_type',type);hidden('equipment_brand',brand);hidden('equipment_model',model);
 const baseAddress=[$('new_district')?.value,$('new_address')?.value,location,locationNote].filter(Boolean).join(' - ');hidden('site_address',baseAddress);
 let details=form.querySelector('textarea[name="details"]');if(details){const marker='[بيانات العميل الجديد]';const original=(details.value.split(marker)[0]||'').trim();const meta=[marker,'المشروع: '+project,'المعدة: '+equipmentName,'نوع المعدة: '+type,'الشركة المصنعة: '+(brand||'—'),'الموديل: '+(model||'—'),'السيريال: '+(serial||'—'),'رقم الأصل: '+(assetNo||'—'),'حالة المعدة: '+(status||'—'),'موقع المعدة: '+location+(locationNote?' - '+locationNote:''),'ملاحظة المعدة: '+(issue||'—')].join('\n');details.value=(original?original+'\n\n':'')+meta;}
}
['new_project_name','new_equipment_location','new_equipment_location_note','new_equipment_name','new_equipment_type','new_equipment_brand','new_equipment_model','new_equipment_serial','new_equipment_asset_no','new_equipment_status','new_equipment_issue'].forEach(id=>{const el=$(id);el?.addEventListener('input',sync);el?.addEventListener('change',sync)});
document.querySelectorAll('[data-customer-kind]').forEach(btn=>btn.addEventListener('click',()=>setTimeout(()=>{const isNew=panel.classList.contains('show');ids.forEach(id=>{const el=$(id);if(el)el.required=isNew});form.querySelectorAll('[data-new-equipment]').forEach(el=>el.disabled=!isNew);if(isNew)sync()},0)));
form.addEventListener('submit',e=>{if(!panel.classList.contains('show'))return;sync();if(ids.some(id=>!$(id)?.value.trim())){e.preventDefault();alert('يرجى استكمال اسم المشروع وموقع المعدة واسم المعدة ونوعها.');}});
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);

        $response->setContent($html);
        return $response;
    }
}
