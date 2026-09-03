<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicEmergencyMaintenancePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $styles = <<<'HTML'
<style id="unifco-emergency-maintenance-v1">
.emergency-shell{display:none}.emergency-shell.show{display:block}.emergency-banner{background:linear-gradient(135deg,#b90f20,#e3132c);color:#fff;border-radius:14px;padding:18px 22px;margin-bottom:13px;text-align:center;box-shadow:0 10px 26px rgba(227,19,44,.16)}.emergency-banner h2{margin:0;font-size:24px;font-weight:900}.emergency-banner p{margin:4px 0 0;font-size:11px;font-weight:700;opacity:.94}.emergency-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 9px 28px rgba(7,31,77,.06);padding:18px;margin-bottom:13px}.emergency-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}.emergency-card-title{font-size:16px;font-weight:900;color:#0b2854}.emergency-step{width:30px;height:30px;border-radius:7px;display:grid;place-items:center;background:#0f5ac6;color:#fff;font-weight:900}.emergency-card.equipment .emergency-step{background:#169447}.emergency-card.triage .emergency-step{background:#f08a00}.emergency-card.contact .emergency-step{background:#7342bd}.emergency-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.emergency-grid.three{grid-template-columns:repeat(3,1fr)}.emergency-grid.two{grid-template-columns:repeat(2,1fr)}.emergency-span2{grid-column:span 2}.emergency-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;border:1px solid #dce6f5;background:#f7faff;border-radius:10px;padding:11px;margin-top:10px}.emergency-summary div{font-size:9px;color:#73839b}.emergency-summary b{display:block;color:#16345f;font-size:11px;margin-top:2px}.emergency-asset-summary{display:none;grid-template-columns:repeat(5,1fr);gap:10px;border:1px solid #cfe9d7;background:#f4fbf6;border-radius:10px;padding:11px;margin-top:10px}.emergency-asset-summary.show{display:grid}.emergency-asset-summary div{font-size:9px;color:#6f819c}.emergency-asset-summary b{display:block;color:#173b28;font-size:11px;margin-top:2px}.emergency-risk{border:1px solid #f3d29a;background:#fff9ec;border-radius:9px;padding:10px 12px;margin-top:10px;font-size:11px;font-weight:800;color:#80550c}.emergency-risk strong{color:#d26d00}.emergency-location-actions{display:flex;align-items:center;gap:9px;margin-top:10px}.emergency-map{display:none;height:170px;border:1px solid #dce4ee;border-radius:9px;overflow:hidden;margin-top:10px}.emergency-map.show{display:block}.emergency-map iframe{width:100%;height:100%;border:0}.emergency-upload{border:1px dashed #c6d1df;border-radius:9px;padding:12px;background:#fbfcfe}.emergency-upload b{display:block;font-size:10px;color:#0b2854;margin-bottom:5px}.emergency-contact-layout{display:grid;grid-template-columns:1.15fr 1fr 1fr;gap:12px}.emergency-submit-row{display:flex;justify-content:flex-start;align-items:center;gap:10px;margin-top:12px}.emergency-submit{height:50px;padding:0 28px;border:0;border-radius:8px;background:#e3132c;color:#fff;font:900 13px Cairo;cursor:pointer}.emergency-cancel{height:50px;padding:0 22px;border:1px solid #ccd7e5;border-radius:8px;background:#fff;color:#1b3558;font:800 12px Cairo;cursor:pointer}.emergency-current-only,.emergency-new-only{display:none}.emergency-current-only.show,.emergency-new-only.show{display:block}.emergency-status{font-size:10px;margin-top:7px;color:#70819a}.emergency-status.ok{color:#16722d}.emergency-status.bad{color:#b20d22}@media(max-width:1000px){.emergency-grid,.emergency-summary,.emergency-asset-summary{grid-template-columns:1fr 1fr}.emergency-contact-layout{grid-template-columns:1fr 1fr}.emergency-contact-layout>.emergency-upload{grid-column:1/-1}}@media(max-width:650px){.emergency-grid,.emergency-grid.three,.emergency-grid.two,.emergency-summary,.emergency-asset-summary,.emergency-contact-layout{grid-template-columns:1fr}.emergency-span2{grid-column:auto}.emergency-submit-row{flex-direction:column}.emergency-submit,.emergency-cancel{width:100%}.emergency-location-actions{flex-direction:column;align-items:stretch}}
</style>
HTML;
        $html = str_replace('</head>', $styles.'</head>', $html);

        $marker = '<div id="routine-form">';
        $panel = <<<'HTML'
<div class="emergency-shell" id="emergency-shell">
<section class="emergency-banner"><h2>⚠ طلب صيانة طارئة</h2><p>للأعطال التي تحتاج تدخلًا سريعًا — يرجى إدخال المعلومات الحرجة لتسريع الاستجابة.</p></section>

<section class="emergency-card">
<div class="emergency-card-head"><div class="emergency-card-title">العميل والموقع</div><div class="emergency-step">1</div></div>
<div class="emergency-current-only" id="em-current">
<div class="emergency-grid">
<div class="field"><label>رقم العميل <span class="req">*</span></label><input id="em_customer_number" placeholder="أدخل رقم العميل"></div>
<div class="field"><label>العقد <span class="req">*</span></label><select id="em_contract"><option value="">اختر العقد</option></select></div>
<div class="field"><label>اسم المشروع</label><input id="em_project" readonly placeholder="يتم تعبئته تلقائيًا"></div>
<div class="field"><label>الموقع <span class="req">*</span></label><select id="em_site"><option value="">اختر الموقع</option></select></div>
</div>
<div class="emergency-status" id="em_customer_status">أدخل رقم العميل لجلب العقود والمواقع.</div>
<div class="emergency-summary" id="em_customer_summary"><div>اسم العميل<b id="em_sum_customer">—</b></div><div>العقد<b id="em_sum_contract">—</b></div><div>المشروع<b id="em_sum_project">—</b></div><div>الموقع<b id="em_sum_site">—</b></div></div>
</div>
<div class="emergency-new-only" id="em-new">
<div class="emergency-grid">
<div class="field"><label>اسم الشركة <span class="req">*</span></label><input id="em_new_company"></div>
<div class="field"><label>مسؤول التواصل <span class="req">*</span></label><input id="em_new_contact"></div>
<div class="field"><label>الجوال <span class="req">*</span></label><input id="em_new_mobile" placeholder="+966 5X XXX XXXX"></div>
<div class="field"><label>البريد الإلكتروني <span class="req">*</span></label><input id="em_new_email" type="email"></div>
<div class="field"><label>اسم المشروع <span class="req">*</span></label><input id="em_new_project"></div>
<div class="field"><label>المنطقة <span class="req">*</span></label><select id="em_new_region"><option value="">اختر المنطقة</option></select></div>
<div class="field"><label>المدينة <span class="req">*</span></label><select id="em_new_city" disabled><option value="">اختر المدينة</option></select></div>
<div class="field"><label>الحي</label><input id="em_new_district"></div>
<div class="field emergency-span2"><label>العنوان / موقع المعدة <span class="req">*</span></label><input id="em_new_address"></div>
</div>
<div class="emergency-location-actions"><button type="button" class="location-btn" id="em_use_location">⌖ استخدم موقعي الحالي</button><span class="emergency-status" id="em_location_status">يمكن تحديد الموقع لتسريع وصول الفني.</span></div>
<div class="emergency-map" id="em_map"><iframe id="em_map_frame" title="موقع الطوارئ" src="about:blank"></iframe></div>
</div>
</section>

<section class="emergency-card equipment">
<div class="emergency-card-head"><div class="emergency-card-title">المعدة المتأثرة</div><div class="emergency-step">2</div></div>
<div class="emergency-current-only" id="em-current-asset">
<div class="emergency-grid"><div class="field emergency-span2"><label>اختر المعدة <span class="req">*</span></label><select id="em_asset"><option value="">اختر من معدات العقد / الموقع</option></select></div><div class="field"><label>أو رقم الأصل</label><input id="em_asset_code"></div><div class="field"><label>أو الرقم التسلسلي</label><input id="em_serial"></div></div>
</div>
<div class="emergency-new-only" id="em-new-asset">
<div class="emergency-grid"><div class="field"><label>اسم المعدة <span class="req">*</span></label><input id="em_eq_name"></div><div class="field"><label>نوع المعدة <span class="req">*</span></label><select id="em_eq_type"><option value="">اختر النوع</option><option>HVAC</option><option>Electrical</option><option>Generator</option><option>UPS</option><option>Pump</option><option>Fire Fighting</option><option>Plumbing</option><option>Elevator</option><option>Other</option></select></div><div class="field"><label>الشركة المصنعة</label><input id="em_eq_brand"></div><div class="field"><label>الموديل</label><input id="em_eq_model"></div><div class="field"><label>السيريال نمبر</label><input id="em_eq_serial"></div><div class="field"><label>رقم الأصل إن وجد</label><input id="em_eq_asset"></div><div class="field emergency-span2"><label>موقع المعدة داخل المشروع</label><input id="em_eq_location" placeholder="مثال: سطح المبنى - غرفة التكييف"></div></div>
</div>
<div class="emergency-asset-summary" id="em_asset_summary"><div>رقم الأصل<b id="em_as_code">—</b></div><div>اسم المعدة<b id="em_as_name">—</b></div><div>النوع<b id="em_as_type">—</b></div><div>الشركة المصنعة<b id="em_as_brand">—</b></div><div>الموديل<b id="em_as_model">—</b></div><div>السيريال<b id="em_as_serial">—</b></div><div>موقع المعدة<b id="em_as_site">—</b></div><div>الحالة<b id="em_as_status">—</b></div></div>
</section>

<section class="emergency-card triage">
<div class="emergency-card-head"><div class="emergency-card-title">حالة الطوارئ وتقييم العطل</div><div class="emergency-step">3</div></div>
<div class="emergency-grid">
<div class="field"><label>حالة المعدة الآن <span class="req">*</span></label><select id="em_state"><option value="">اختر</option><option value="STOPPED">متوقفة بالكامل</option><option value="PARTIAL">تعمل جزئيًا</option><option value="RISK">تعمل لكن بها خطر</option><option value="UNKNOWN">غير معروف</option></select></div>
<div class="field"><label>تأثير العطل <span class="req">*</span></label><select id="em_impact"><option value="">اختر</option><option value="SITE_DOWN">توقف كامل للموقع</option><option value="PARTIAL_SITE">توقف جزء من الموقع</option><option value="PRODUCTION">تأثير على الإنتاج</option><option value="SAFETY">تأثير على السلامة</option><option value="POWER">تأثير على الكهرباء</option><option value="HVAC">تأثير على التكييف</option><option value="WATER">تأثير على المياه</option><option value="LIMITED">تأثير محدود</option></select></div>
<div class="field"><label>هل يوجد خطر سلامة؟ <span class="req">*</span></label><select id="em_safety"><option value="">اختر</option><option value="YES">نعم</option><option value="NO">لا</option><option value="UNKNOWN">غير معروف</option></select></div>
<div class="field"><label>نوع الخطر</label><select id="em_hazard"><option value="">لا يوجد / اختر</option><option>كهربائي</option><option>حريق / دخان</option><option>تسريب مياه</option><option>تسريب غاز</option><option>حرارة مرتفعة</option><option>أجزاء ميكانيكية خطرة</option><option>خطر آخر</option></select></div>
<div class="field"><label>هل الموقع يعمل حاليًا؟ <span class="req">*</span></label><select id="em_site_status"><option value="">اختر</option><option value="FULL">نعم بالكامل</option><option value="PARTIAL">جزئيًا</option><option value="DOWN">متوقف</option></select></div>
<div class="field"><label>متى بدأ العطل؟ <span class="req">*</span></label><select id="em_started"><option value="">اختر</option><option>الآن</option><option>أقل من ساعة</option><option>1–3 ساعات</option><option>3–12 ساعة</option><option>أكثر من 12 ساعة</option><option>غير معروف</option></select></div>
<div class="field"><label>هل تمت محاولة إعادة التشغيل؟ <span class="req">*</span></label><select id="em_restart"><option value="">اختر</option><option>نعم ونجحت مؤقتًا</option><option>نعم ولم تنجح</option><option>لا</option><option>غير مناسب / خطر</option></select></div>
<div class="field"><label>متى تحتاج التدخل؟ <span class="req">*</span></label><select id="em_response"><option value="">اختر</option><option value="NOW">فورًا</option><option value="2H">خلال ساعتين</option><option value="TODAY">اليوم</option><option value="ASAP">في أقرب وقت ممكن</option></select></div>
<div class="field emergency-span2"><label>وصف مختصر للعطل <span class="req">*</span></label><textarea id="em_details" placeholder="اشرح ما حدث وأي أعراض ظاهرة"></textarea></div>
</div>
<div class="emergency-risk">⚠ مستوى الأولوية المتوقع: <strong id="em_priority_label">P2 — عالي</strong>. يتم احتسابه تلقائيًا حسب خطر السلامة وتوقف الموقع وتأثير العطل.</div>
</section>

<section class="emergency-card contact">
<div class="emergency-card-head"><div class="emergency-card-title">التواصل والمرفقات</div><div class="emergency-step">4</div></div>
<div class="emergency-contact-layout">
<div><div class="field"><label>الشخص الموجود في الموقع <span class="req">*</span></label><input id="em_on_site_person"></div><div class="field"><label>رقم الجوال <span class="req">*</span></label><input id="em_on_site_mobile"></div></div>
<div><div class="field"><label>هل يمكنه استقبال الفني؟ <span class="req">*</span></label><select id="em_can_receive"><option value="">اختر</option><option>نعم</option><option>لا</option></select></div><div class="field"><label>هل يحتاج دخول أمني / تصريح؟</label><select id="em_access"><option>لا</option><option>نعم</option></select></div><div class="field"><label>نقطة الدخول / البوابة</label><input id="em_gate"></div></div>
<div class="emergency-upload"><b>المرفقات</b><div class="hint">صور العطل والمعدة، Nameplate، فيديو أو تقرير سابق.</div></div>
</div>
<div class="emergency-submit-row"><button class="emergency-submit" type="submit">إرسال طلب الصيانة الطارئة</button><button class="emergency-cancel" id="em_cancel" type="button">إلغاء</button></div>
</section>
</div>
HTML;
        if (str_contains($html, $marker)) {
            $html = str_replace($marker, $panel.$marker, $html);
        }

        $script = <<<'HTML'
<script id="unifco-emergency-maintenance-script">
(()=>{
const $=id=>document.getElementById(id), form=$('maintenance-form'), shell=$('emergency-shell'), routine=$('routine-form'), subtype=$('service-subtype');
if(!form||!shell||!subtype)return;
const regions={'الرياض':['الرياض','الخرج','الدرعية','الدوادمي','المجمعة','شقراء','القويعية','وادي الدواسر','الزلفي'],'مكة المكرمة':['مكة المكرمة','جدة','الطائف','رابغ','القنفذة','الليث','خليص'],'المدينة المنورة':['المدينة المنورة','ينبع','العلا','بدر','خيبر'],'المنطقة الشرقية':['الدمام','الخبر','الظهران','الجبيل','الأحساء','القطيف','رأس تنورة','حفر الباطن'],'القصيم':['بريدة','عنيزة','الرس','البكيرية','المذنب'],'عسير':['أبها','خميس مشيط','بيشة','محايل عسير','النماص'],'تبوك':['تبوك','ضباء','الوجه','أملج','تيماء'],'حائل':['حائل','بقعاء','الغزالة'],'الحدود الشمالية':['عرعر','رفحاء','طريف'],'جازان':['جازان','صبيا','أبو عريش','صامطة','بيش'],'نجران':['نجران','شرورة','حبونا'],'الباحة':['الباحة','بلجرشي','المخواة','العقيق'],'الجوف':['سكاكا','دومة الجندل','القريات','طبرجل']};
const reg=$('em_new_region'), city=$('em_new_city');Object.keys(regions).forEach(r=>reg?.add(new Option(r,r)));reg?.addEventListener('change',()=>{city.innerHTML='<option value="">اختر المدينة</option>';(regions[reg.value]||[]).forEach(c=>city.add(new Option(c,c)));city.disabled=!reg.value});
function customerKind(){return document.querySelector('[data-customer-kind].active')?.dataset.customerKind||'current'}
function toggleKind(){const isNew=customerKind()==='new';['em-current','em-current-asset'].forEach(id=>$(id)?.classList.toggle('show',!isNew));['em-new','em-new-asset'].forEach(id=>$(id)?.classList.toggle('show',isNew));}
document.querySelectorAll('[data-customer-kind]').forEach(b=>b.addEventListener('click',toggleKind));
function emergencyMode(){const on=subtype.value==='urgent';shell.classList.toggle('show',on);if(routine)routine.style.display=on?'none':'';const ri=form.querySelector('input[name="request_intent"]'),rs=form.querySelector('input[name="request_subtype"]'),ur=form.querySelector('input[name="urgency"]');if(on){if(ri)ri.value='SERVICE_REQUEST';if(rs)rs.value='URGENT_MAINTENANCE';if(ur)ur.value='EMERGENCY';toggleKind();}else{if(rs)rs.value='ROUTINE_MAINTENANCE';if(ur)ur.value='NORMAL';}}
subtype.addEventListener('change',emergencyMode);emergencyMode();

let customerData=null,assets=[];
$('em_customer_number')?.addEventListener('change',loadCustomer);$('em_customer_number')?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();loadCustomer()}});
async function loadCustomer(){const num=$('em_customer_number').value.trim(),st=$('em_customer_status');if(!num)return;st.textContent='جارٍ جلب بيانات العميل…';st.className='emergency-status';try{const r=await fetch('/request-service/current-maintenance/customer?customer_number='+encodeURIComponent(num),{headers:{'Accept':'application/json'}});const d=await r.json();if(!r.ok)throw new Error(d.message||'تعذر جلب العميل');customerData=d;$('em_sum_customer').textContent=d.customer?.name||'—';const c=$('em_contract');c.innerHTML='<option value="">اختر العقد</option>';(d.contracts||[]).forEach(x=>c.add(new Option((x.contract_no||'')+' — '+(x.title||''),x.contract_no||'')));const s=$('em_site');s.innerHTML='<option value="">اختر الموقع</option>';(d.sites||[]).forEach(x=>{const o=new Option((x.name||'')+' — '+(x.city||''),x.id);o.dataset.payload=JSON.stringify(x);s.add(o)});st.textContent='تم جلب بيانات العميل بنجاح.';st.className='emergency-status ok';}catch(e){st.textContent=e.message;st.className='emergency-status bad';}}
$('em_contract')?.addEventListener('change',()=>{const opt=$('em_contract').selectedOptions[0];$('em_sum_contract').textContent=opt?.textContent||'—';$('em_project').value=opt?.textContent?.split('—').slice(1).join('—').trim()||'';$('em_sum_project').textContent=$('em_project').value||'—';loadAssets()});
$('em_site')?.addEventListener('change',()=>{const opt=$('em_site').selectedOptions[0];$('em_sum_site').textContent=opt?.textContent||'—';loadAssets()});
async function loadAssets(){if(!customerData?.customer?.id)return;const q=new URLSearchParams({customer_id:customerData.customer.id});if($('em_contract').value)q.set('contract_no',$('em_contract').value);if($('em_site').value)q.set('site_id',$('em_site').value);try{const r=await fetch('/request-service/current-maintenance/assets?'+q.toString(),{headers:{'Accept':'application/json'}});const d=await r.json();assets=d.assets||[];const s=$('em_asset');s.innerHTML='<option value="">اختر من معدات العقد / الموقع</option>';assets.forEach(a=>{const o=new Option((a.asset_code||'')+' — '+(a.name||''),a.id);o.dataset.payload=JSON.stringify(a);s.add(o)})}catch(e){}}
$('em_asset')?.addEventListener('change',()=>{const o=$('em_asset').selectedOptions[0];if(!o?.dataset.payload)return;$('em_asset_summary').classList.add('show');const a=JSON.parse(o.dataset.payload);$('em_as_code').textContent=a.asset_code||'—';$('em_as_name').textContent=a.name||'—';$('em_as_type').textContent=a.asset_type||a.asset_category||'—';$('em_as_brand').textContent=a.manufacturer||'—';$('em_as_model').textContent=a.model_no||'—';$('em_as_serial').textContent=a.serial_no||'—';$('em_as_site').textContent=[a.site_name,a.site_city].filter(Boolean).join(' - ')||'—';$('em_as_status').textContent=a.operational_status||'—';});

$('em_use_location')?.addEventListener('click',()=>{const st=$('em_location_status');if(!navigator.geolocation){st.textContent='المتصفح لا يدعم تحديد الموقع.';return}st.textContent='جارٍ تحديد الموقع…';navigator.geolocation.getCurrentPosition(pos=>{const lat=pos.coords.latitude.toFixed(6),lon=pos.coords.longitude.toFixed(6);$('latitude').value=lat;$('longitude').value=lon;const f=$('em_map_frame');f.src='https://www.openstreetmap.org/export/embed.html?bbox='+(+lon-.02)+'%2C'+(+lat-.015)+'%2C'+(+lon+.02)+'%2C'+(+lat+.015)+'&layer=mapnik&marker='+lat+'%2C'+lon;$('em_map').classList.add('show');st.textContent='تم تحديد الموقع بنجاح.';st.className='emergency-status ok';},()=>{st.textContent='تعذر الوصول للموقع. أكمل العنوان يدويًا.';st.className='emergency-status bad'},{enableHighAccuracy:true,timeout:10000})});

['em_state','em_impact','em_safety','em_site_status'].forEach(id=>$(id)?.addEventListener('change',score));function score(){let p=3;if($('em_safety').value==='YES'||$('em_site_status').value==='DOWN'||$('em_impact').value==='SITE_DOWN')p=1;else if($('em_state').value==='STOPPED'||['PRODUCTION','POWER','HVAC','WATER','SAFETY'].includes($('em_impact').value)||$('em_site_status').value==='PARTIAL')p=2;$('em_priority_label').textContent=p===1?'P1 — حرج':p===2?'P2 — عالي':'P3 — عاجل';return p}
function hidden(name,val){let x=form.querySelector('input[data-em-field="'+name+'"]');if(!x){x=document.createElement('input');x.type='hidden';x.dataset.emField=name;x.name=name;form.appendChild(x)}x.value=val??'';x.disabled=false}
form.addEventListener('submit',e=>{if(subtype.value!=='urgent')return;const isNew=customerKind()==='new';const required=isNew?['em_new_company','em_new_contact','em_new_mobile','em_new_email','em_new_project','em_new_region','em_new_city','em_new_address','em_eq_name','em_eq_type','em_state','em_impact','em_safety','em_site_status','em_started','em_restart','em_response','em_details','em_on_site_person','em_on_site_mobile','em_can_receive']:['em_customer_number','em_contract','em_site','em_asset','em_state','em_impact','em_safety','em_site_status','em_started','em_restart','em_response','em_details','em_on_site_person','em_on_site_mobile','em_can_receive'];if(required.some(id=>!$(id)?.value)){e.preventDefault();alert('يرجى استكمال الحقول المطلوبة في نموذج الصيانة الطارئة.');return}
let company='',person='',mobile='',email='',siteName='',siteCity='',siteArea='',siteAddress='',assetType='GENERAL',brand='',model='',assetId='';if(isNew){company=$('em_new_company').value;person=$('em_new_contact').value;mobile=$('em_new_mobile').value;email=$('em_new_email').value;siteName=$('em_new_project').value;siteCity=$('em_new_city').value;siteArea=$('em_new_region').value;siteAddress=[$('em_new_district').value,$('em_new_address').value,$('em_eq_location').value].filter(Boolean).join(' - ');assetType=$('em_eq_type').value;brand=$('em_eq_brand').value;model=$('em_eq_model').value;}else{company=customerData?.customer?.name||'';person=customerData?.customer?.contact_name||$('em_on_site_person').value;mobile=customerData?.customer?.phone||$('em_on_site_mobile').value;email=customerData?.customer?.email||'service@unifco.sa';const so=$('em_site').selectedOptions[0];const sp=so?.dataset.payload?JSON.parse(so.dataset.payload):{};siteName=sp.name||$('em_sum_site').textContent;siteCity=sp.city||'General';siteArea=sp.city||'General';siteAddress=sp.address||'';assetId=$('em_asset').value;const ao=$('em_asset').selectedOptions[0];const ap=ao?.dataset.payload?JSON.parse(ao.dataset.payload):{};assetType=ap.asset_type||ap.asset_category||'GENERAL';brand=ap.manufacturer||'';model=ap.model_no||'';if(sp.latitude)$('latitude').value=sp.latitude;if(sp.longitude)$('longitude').value=sp.longitude;}
hidden('company_name',company);hidden('responsible_person',person);hidden('mobile',mobile);hidden('email',email);hidden('site_name',siteName);hidden('site_city',siteCity);hidden('site_area',siteArea);hidden('site_address',siteAddress);hidden('asset_type',assetType);hidden('equipment_brand',brand);hidden('equipment_model',model);if(assetId)hidden('asset_id',assetId);hidden('requested_date',new Date().toISOString().slice(0,10));hidden('requested_time',new Date().toTimeString().slice(0,5));const p=score();hidden('service_other','Emergency Maintenance · '+(isNew?$('em_new_project').value:$('em_project').value)+' · '+$('em_priority_label').textContent);const detail=['حالة المعدة: '+$('em_state').selectedOptions[0]?.textContent,'تأثير العطل: '+$('em_impact').selectedOptions[0]?.textContent,'خطر سلامة: '+$('em_safety').selectedOptions[0]?.textContent,'نوع الخطر: '+$('em_hazard').value,'حالة الموقع: '+$('em_site_status').selectedOptions[0]?.textContent,'بدأ العطل: '+$('em_started').value,'إعادة التشغيل: '+$('em_restart').value,'التدخل المطلوب: '+$('em_response').selectedOptions[0]?.textContent,'الأولوية: P'+p,'وصف العطل: '+$('em_details').value,'الشخص بالموقع: '+$('em_on_site_person').value+' / '+$('em_on_site_mobile').value,'استقبال الفني: '+$('em_can_receive').value,'تصريح دخول: '+$('em_access').value,'نقطة الدخول: '+$('em_gate').value];if(isNew)detail.push('المعدة: '+$('em_eq_name').value+' / '+$('em_eq_type').value+' / '+$('em_eq_brand').value+' / '+$('em_eq_model').value+' / SN '+$('em_eq_serial').value+' / Asset '+$('em_eq_asset').value+' / '+$('em_eq_location').value);hidden('details',detail.join('\n'));});
$('em_cancel')?.addEventListener('click',()=>{subtype.value='routine';subtype.dispatchEvent(new Event('change'))});
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);

        $response->setContent($html);
        return $response;
    }
}
