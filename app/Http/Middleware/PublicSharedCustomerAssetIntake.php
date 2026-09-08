<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicSharedCustomerAssetIntake
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, '</head>') || ! str_contains($html, '</body>')) {
            return $response;
        }

        $isCurrentMaintenance = $request->routeIs('public.current-maintenance');
        $isSharedRequest = $request->routeIs('public.request-service') || $request->routeIs('public.quote');

        if (! $isCurrentMaintenance && ! $isSharedRequest) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-shared-customer-asset-intake-v2">
.uf-intake{background:#fff;border:1px solid #dce6f0;border-radius:18px;padding:22px 24px;margin:0 0 18px;box-shadow:0 10px 28px rgba(7,31,77,.055);font-family:Cairo,Tahoma,Arial,sans-serif;color:#08295d;direction:rtl}
.uf-intake-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid #edf2f7}
.uf-intake-title-wrap{display:flex;align-items:center;gap:11px}.uf-intake-step{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#e3132c;color:#fff;font-weight:900;font-size:14px;flex:0 0 34px}.uf-intake h2{margin:0;font-size:21px;line-height:1.3;font-weight:900;color:#08295d}.uf-intake-sub{margin:4px 0 0;color:#7c8ba1;font-size:10px;font-weight:600}
.uf-choice-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}.uf-choice-group>label{display:block;text-align:right;font-size:12px;font-weight:900;color:#08295d;margin:0 0 8px}.uf-choice-row{display:grid;grid-template-columns:1fr 1fr;gap:9px}.uf-choice{position:relative;min-height:64px;border:1px solid #cfdae7;border-radius:11px;background:#fff;padding:10px 13px 10px 42px;cursor:pointer;transition:.18s ease;display:flex;align-items:center;gap:10px}.uf-choice:hover{border-color:#8fa8c6;box-shadow:0 5px 14px rgba(8,41,93,.06)}.uf-choice.active{border:1.5px solid #e3132c;background:#fff8f9;box-shadow:0 0 0 2px rgba(227,19,44,.035)}.uf-choice-icon{width:32px;height:32px;border-radius:8px;background:#eef5ff;color:#1558a5;display:grid;place-items:center;font-weight:900;font-size:16px;flex:0 0 32px}.uf-choice.active .uf-choice-icon{background:#fff;color:#e3132c}.uf-choice-copy b{display:block;color:#0a3268;font-size:11px;font-weight:900}.uf-choice-copy small{display:block;color:#8392a8;font-size:8.5px;margin-top:2px}.uf-radio{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:15px;height:15px;border:1.5px solid #5579a8;border-radius:50%;background:#fff}.uf-choice.active .uf-radio{border-color:#e3132c;box-shadow:inset 0 0 0 4px #fff;background:#e3132c}
.uf-search-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-top:16px}.uf-search-group>label{display:block;text-align:right;font-size:10.5px;font-weight:900;color:#08295d;margin-bottom:6px}.uf-search-row{display:grid;grid-template-columns:1fr auto;gap:8px}.uf-search-row.asset{grid-template-columns:1fr auto auto}.uf-search-input{height:42px;border:1px solid #cfdae7!important;border-radius:8px!important;padding:8px 11px!important;background:#fff!important;font:600 10px Cairo!important;color:#173a67!important;min-height:42px!important}.uf-search-btn,.uf-qr-btn{height:42px;border-radius:8px;border:1px solid #0b4d91;background:#fff;color:#0b4d91;padding:0 15px;font:800 9.5px Cairo;cursor:pointer;white-space:nowrap}.uf-search-btn.primary{background:#0b4d91;color:#fff}.uf-qr-btn{display:flex;align-items:center;gap:6px}.uf-result{display:none;margin-top:9px;border:1px solid #d8e4ef;border-radius:9px;background:#f9fbfd;padding:11px 12px;min-height:56px}.uf-result.show{display:grid}.uf-result.customer{grid-template-columns:auto 1fr auto;gap:10px;align-items:center;background:#f3fcf8;border-color:#cfeadd}.uf-result.asset{grid-template-columns:1fr auto;gap:11px;align-items:center}.uf-result-main b{display:block;font-size:10.5px;color:#0a3268}.uf-result-main small{display:block;font-size:8.5px;color:#7486a0;margin-top:3px}.uf-tag{background:#e6f0ff;color:#174e8b;border-radius:6px;padding:4px 7px;font-size:8px;font-weight:800}.uf-ok{width:21px;height:21px;border-radius:50%;display:grid;place-items:center;background:#12a36a;color:#fff;font-size:11px;font-weight:900}.uf-new-customer-fields,.uf-new-asset-fields{display:none;margin-top:13px;grid-template-columns:repeat(4,1fr);gap:9px}.uf-new-customer-fields.show,.uf-new-asset-fields.show{display:grid}.uf-mini-field label{display:block;font-size:9px;font-weight:800;color:#173a67;margin-bottom:4px}.uf-mini-field input{height:39px!important;min-height:39px!important;font-size:9.5px!important;border-radius:8px!important}
body.uf-current-maintenance-clean .request-selector-customer{display:none!important}
body.uf-current-maintenance-clean .request-selector-row{grid-template-columns:1fr 1fr!important;gap:14px!important}
body.uf-current-maintenance-clean .request-selector-note{display:none!important}
body.uf-current-maintenance-clean #routine-form>section.panel:first-of-type{display:none!important}
body.uf-current-maintenance-clean #asset-section{display:none!important}
body.uf-current-maintenance-clean #routine-form{display:block}
body.uf-current-maintenance-clean .request-selector-panel{padding:18px 22px!important;margin-bottom:18px!important;border-radius:16px!important}
@media(max-width:900px){.uf-choice-grid,.uf-search-grid{grid-template-columns:1fr}.uf-new-customer-fields,.uf-new-asset-fields{grid-template-columns:1fr 1fr}}
@media(max-width:700px){body.uf-current-maintenance-clean .request-selector-row{grid-template-columns:1fr!important}}
@media(max-width:560px){.uf-intake{padding:16px}.uf-choice-row{grid-template-columns:1fr}.uf-search-row,.uf-search-row.asset{grid-template-columns:1fr}.uf-new-customer-fields,.uf-new-asset-fields{grid-template-columns:1fr}.uf-result.customer,.uf-result.asset{grid-template-columns:1fr}}
</style>
HTML;

        $block = <<<'HTML'
<section class="uf-intake" id="uf-shared-intake">
  <div class="uf-intake-head">
    <div class="uf-intake-title-wrap"><span class="uf-intake-step">1</span><div><h2>بيانات العميل والأصل</h2><p class="uf-intake-sub">حدد نوع العميل ونوع الأصل ثم ابحث أو أضف البيانات المطلوبة.</p></div></div>
  </div>
  <div class="uf-choice-grid">
    <div class="uf-choice-group"><label>نوع العميل</label><div class="uf-choice-row">
      <button type="button" class="uf-choice active" data-uf-customer="current"><span class="uf-choice-icon">♙</span><span class="uf-choice-copy"><b>عميل حالي</b><small>لديك حساب مسجل لدى UNIFCO</small></span><span class="uf-radio"></span></button>
      <button type="button" class="uf-choice" data-uf-customer="new"><span class="uf-choice-icon">＋</span><span class="uf-choice-copy"><b>عميل جديد</b><small>ليس لديك حساب مسبق</small></span><span class="uf-radio"></span></button>
    </div></div>
    <div class="uf-choice-group"><label>نوع الأصل</label><div class="uf-choice-row">
      <button type="button" class="uf-choice active" data-uf-asset="registered"><span class="uf-choice-icon">▣</span><span class="uf-choice-copy"><b>أصل مسجل لدى UNIFCO</b><small>اختر من أصولك المسجلة</small></span><span class="uf-radio"></span></button>
      <button type="button" class="uf-choice" data-uf-asset="new"><span class="uf-choice-icon">▤</span><span class="uf-choice-copy"><b>أصل غير مسجل</b><small>إضافة بيانات أصل جديد</small></span><span class="uf-radio"></span></button>
    </div></div>
  </div>
  <div class="uf-search-grid">
    <div class="uf-search-group" id="uf-customer-search-group"><label>البحث عن العميل</label><div class="uf-search-row"><input class="uf-search-input" id="uf-customer-search" placeholder="ابحث برقم العميل أو الجوال أو البريد الإلكتروني"><button type="button" class="uf-search-btn primary" id="uf-customer-search-btn">بحث</button></div><div class="uf-result customer" id="uf-customer-result"><span class="uf-ok">✓</span><div class="uf-result-main"><b id="uf-customer-name">تم تحديد العميل</b><small id="uf-customer-meta">سيتم تعبئة البيانات من سجل العميل</small></div><span class="uf-tag" id="uf-customer-code">Customer</span></div></div>
    <div class="uf-search-group" id="uf-asset-search-group"><label>البحث عن الأصل</label><div class="uf-search-row asset"><input class="uf-search-input" id="uf-asset-search" placeholder="ابحث برقم الأصل أو الاسم أو الرقم التسلسلي"><button type="button" class="uf-qr-btn" id="uf-qr-btn">▦ مسح QR للأصل</button><button type="button" class="uf-search-btn" id="uf-asset-search-btn">بحث</button></div><div class="uf-result asset" id="uf-asset-result"><div class="uf-result-main"><b id="uf-asset-name">تم تحديد الأصل</b><small id="uf-asset-meta">بيانات الأصل المسجل لدى UNIFCO</small></div><span class="uf-ok">✓</span></div></div>
  </div>
  <div class="uf-new-customer-fields" id="uf-new-customer-fields"><div class="uf-mini-field"><label>اسم الشركة</label><input id="uf-new-company"></div><div class="uf-mini-field"><label>مسؤول التواصل</label><input id="uf-new-contact"></div><div class="uf-mini-field"><label>رقم التواصل</label><input id="uf-new-mobile"></div><div class="uf-mini-field"><label>البريد الإلكتروني</label><input id="uf-new-email" type="email"></div></div>
  <div class="uf-new-asset-fields" id="uf-new-asset-fields"><div class="uf-mini-field"><label>اسم الأصل</label><input id="uf-new-asset-name"></div><div class="uf-mini-field"><label>نوع الأصل</label><input id="uf-new-asset-type"></div><div class="uf-mini-field"><label>الشركة المصنعة</label><input id="uf-new-asset-brand"></div><div class="uf-mini-field"><label>الموديل / السيريال</label><input id="uf-new-asset-model"></div></div>
</section>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);

        if ($isCurrentMaintenance) {
            $marker = '<div id="routine-form">';
            if (str_contains($html, $marker)) {
                $html = str_replace($marker, $block.$marker, $html);
            }
        } else {
            $formStart = '<main class="shell"><form id="requestForm"';
            $pos = strpos($html, $formStart);
            if ($pos !== false) {
                $sectionPos = strpos($html, '<section class="section">', $pos);
                if ($sectionPos !== false) {
                    $html = substr_replace($html, $block, $sectionPos, 0);
                }
            }
        }

        $cleanFlag = $isCurrentMaintenance ? 'true' : 'false';
        $script = str_replace('@@CURRENT@@', $cleanFlag, <<<'HTML'
<script id="unifco-shared-customer-asset-intake-js-v2">
(function(){
 const root=document.getElementById('uf-shared-intake'); if(!root)return;
 const current=@@CURRENT@@;
 const q=(s)=>document.querySelector(s), qa=(s)=>Array.from(document.querySelectorAll(s));
 const setActive=(selector,el)=>qa(selector).forEach(x=>x.classList.toggle('active',x===el));
 if(current){
   document.body.classList.add('uf-current-maintenance-clean');
   const visiblePanels=qa('#routine-form>section.panel').filter(x=>getComputedStyle(x).display!=='none');
   let n=2; visiblePanels.forEach(panel=>{const badge=panel.querySelector('.section-title .num'); if(badge)badge.textContent=String(n++);});
 }
 qa('[data-uf-customer]').forEach(btn=>btn.addEventListener('click',()=>{
   setActive('[data-uf-customer]',btn); const v=btn.dataset.ufCustomer;
   q('#uf-customer-search-group').style.display=v==='current'?'block':'none';
   q('#uf-new-customer-fields').classList.toggle('show',v==='new');
 }));
 qa('[data-uf-asset]').forEach(btn=>btn.addEventListener('click',()=>{
   setActive('[data-uf-asset]',btn); const v=btn.dataset.ufAsset;
   q('#uf-asset-search-group').style.display=v==='registered'?'block':'none';
   q('#uf-new-asset-fields').classList.toggle('show',v==='new');
 }));
 q('#uf-customer-search-btn')?.addEventListener('click',()=>{
   const val=(q('#uf-customer-search')?.value||'').trim(); if(!val)return;
   const original=q('#customer_number'); const lookup=q('#customer-lookup');
   if(original){original.value=val; original.dispatchEvent(new Event('input',{bubbles:true})); lookup?.click();}
   q('#uf-customer-code').textContent=val; q('#uf-customer-result').classList.add('show');
   setTimeout(()=>{const company=q('#company_name')?.value||q('input[name="company_name"]')?.value; const person=q('#responsible_person')?.value||q('input[name="responsible_person"]')?.value; if(company)q('#uf-customer-name').textContent=company; if(person)q('#uf-customer-meta').textContent=person;},650);
 });
 q('#uf-asset-search-btn')?.addEventListener('click',()=>{
   const val=(q('#uf-asset-search')?.value||'').trim(); if(!val)return;
   const key=q('#asset-key'); const find=q('#asset-find');
   if(key){key.value=val; key.dispatchEvent(new Event('input',{bubbles:true})); find?.click();}
   q('#uf-asset-name').textContent=val; q('#uf-asset-result').classList.add('show');
   setTimeout(()=>{const n=q('#ac-name')?.textContent; const code=q('#ac-code')?.textContent; const serial=q('#ac-serial')?.textContent; if(n&&n!=='—')q('#uf-asset-name').textContent=n; if(code&&code!=='—')q('#uf-asset-meta').textContent=[code,serial&&serial!=='—'?serial:''].filter(Boolean).join(' · ');},650);
 });
 q('#uf-qr-btn')?.addEventListener('click',()=>{
   const qrMethod=qa('.asset-method').find(x=>x.dataset.method==='qr');
   if(qrMethod){qrMethod.click(); q('#asset-key')?.focus(); return;}
   q('#uf-asset-search')?.focus();
 });
 const sync=(source,target)=>q(source)?.addEventListener('input',e=>{const t=q(target);if(t){t.value=e.target.value;t.dispatchEvent(new Event('input',{bubbles:true}));}});
 sync('#uf-new-company','input[name="company_name"]'); sync('#uf-new-contact','input[name="responsible_person"]'); sync('#uf-new-mobile','input[name="mobile"]'); sync('#uf-new-email','input[name="email"]');
 sync('#uf-new-asset-type','#asset_type'); sync('#uf-new-asset-brand','#equipment_brand'); sync('#uf-new-asset-model','#equipment_model');
})();
</script>
HTML);

        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
