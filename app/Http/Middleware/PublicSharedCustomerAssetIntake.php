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
<style id="unifco-shared-customer-asset-intake-v1">
.uf-intake{background:#fff;border:1px solid #dce6f0;border-radius:16px;padding:18px 20px;margin:0 0 16px;box-shadow:0 8px 24px rgba(7,31,77,.05);font-family:Cairo,Tahoma,Arial,sans-serif;color:#08295d;direction:rtl}
.uf-intake-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:14px}
.uf-intake-title-wrap{display:flex;align-items:center;gap:10px}.uf-intake-step{width:31px;height:31px;border-radius:50%;display:grid;place-items:center;background:#e3132c;color:#fff;font-weight:900;font-size:13px;flex:0 0 31px}.uf-intake h2{margin:0;font-size:18px;line-height:1.3;font-weight:900;color:#08295d}.uf-intake-sub{margin:3px 0 0;color:#7486a0;font-size:9.5px;font-weight:600}
.uf-choice-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.uf-choice-group>label{display:block;text-align:right;font-size:12px;font-weight:900;color:#08295d;margin:0 0 7px}.uf-choice-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}.uf-choice{position:relative;min-height:56px;border:1px solid #cfdbe8;border-radius:9px;background:#fff;padding:9px 12px 9px 42px;cursor:pointer;transition:.18s ease;display:flex;align-items:center;gap:10px}.uf-choice:hover{border-color:#8ea9c8;box-shadow:0 4px 12px rgba(8,41,93,.05)}.uf-choice.active{border:1.5px solid #e3132c;background:#fff7f8;box-shadow:0 0 0 2px rgba(227,19,44,.035)}.uf-choice-icon{width:29px;height:29px;border-radius:7px;background:#eef5ff;color:#1558a5;display:grid;place-items:center;font-weight:900;font-size:16px;flex:0 0 29px}.uf-choice.active .uf-choice-icon{background:#fff;color:#e3132c}.uf-choice-copy b{display:block;color:#0a3268;font-size:10.5px;font-weight:900}.uf-choice-copy small{display:block;color:#8191a7;font-size:8px;margin-top:2px}.uf-radio{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;border:1.5px solid #5579a8;border-radius:50%;background:#fff}.uf-choice.active .uf-radio{border-color:#e3132c;box-shadow:inset 0 0 0 3.5px #fff;background:#e3132c}
.uf-search-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:13px}.uf-search-group>label{display:block;text-align:right;font-size:10px;font-weight:900;color:#08295d;margin-bottom:5px}.uf-search-row{display:grid;grid-template-columns:1fr auto;gap:7px}.uf-search-row.asset{grid-template-columns:1fr auto auto}.uf-search-input{height:38px;border:1px solid #cfdbe8!important;border-radius:7px!important;padding:7px 10px!important;background:#fff!important;font:600 9.5px Cairo!important;color:#173a67!important;min-height:38px!important}.uf-search-btn,.uf-qr-btn{height:38px;border-radius:7px;border:1px solid #0b4d91;background:#fff;color:#0b4d91;padding:0 13px;font:800 9px Cairo;cursor:pointer;white-space:nowrap}.uf-search-btn.primary{background:#0b4d91;color:#fff}.uf-qr-btn{display:flex;align-items:center;gap:5px}.uf-result{display:none;margin-top:8px;border:1px solid #d8e4ef;border-radius:8px;background:#f9fbfd;padding:10px 12px;min-height:54px}.uf-result.show{display:grid}.uf-result.customer{grid-template-columns:auto 1fr auto auto;gap:10px;align-items:center;background:#f3fcf8;border-color:#cfeadd}.uf-result.asset{grid-template-columns:74px 1fr auto;gap:11px;align-items:center}.uf-result-img{width:74px;height:48px;border-radius:6px;background:#e8eef5;object-fit:cover}.uf-result-main b{display:block;font-size:10px;color:#0a3268}.uf-result-main small{display:block;font-size:8px;color:#7486a0;margin-top:3px}.uf-tag{background:#e6f0ff;color:#174e8b;border-radius:6px;padding:4px 7px;font-size:8px;font-weight:800}.uf-ok{width:20px;height:20px;border-radius:50%;display:grid;place-items:center;background:#12a36a;color:#fff;font-size:11px;font-weight:900}.uf-new-customer-fields,.uf-new-asset-fields{display:none;margin-top:10px;grid-template-columns:repeat(4,1fr);gap:8px}.uf-new-customer-fields.show,.uf-new-asset-fields.show{display:grid}.uf-mini-field label{display:block;font-size:8.5px;font-weight:800;color:#173a67;margin-bottom:4px}.uf-mini-field input{height:36px!important;min-height:36px!important;font-size:9px!important;border-radius:7px!important}
@media(max-width:900px){.uf-choice-grid,.uf-search-grid{grid-template-columns:1fr}.uf-new-customer-fields,.uf-new-asset-fields{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.uf-intake{padding:14px}.uf-choice-row{grid-template-columns:1fr}.uf-search-row,.uf-search-row.asset{grid-template-columns:1fr}.uf-new-customer-fields,.uf-new-asset-fields{grid-template-columns:1fr}.uf-result.customer,.uf-result.asset{grid-template-columns:1fr}.uf-result-img{display:none}}
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
    <div class="uf-search-group" id="uf-customer-search-group"><label>البحث عن العميل</label><div class="uf-search-row"><input class="uf-search-input" id="uf-customer-search" placeholder="ابحث برقم العميل أو الجوال أو البريد الإلكتروني"><button type="button" class="uf-search-btn primary" id="uf-customer-search-btn">بحث</button></div><div class="uf-result customer" id="uf-customer-result"><span class="uf-ok">✓</span><div class="uf-result-main"><b id="uf-customer-name">تم تحديد العميل</b><small id="uf-customer-meta">سيتم تعبئة البيانات من سجل العميل</small></div><span class="uf-tag" id="uf-customer-code">Customer</span><span class="uf-ok">✓</span></div></div>
    <div class="uf-search-group" id="uf-asset-search-group"><label>البحث عن الأصل</label><div class="uf-search-row asset"><input class="uf-search-input" id="uf-asset-search" placeholder="ابحث برقم الأصل أو الاسم أو رقم الجوال"><button type="button" class="uf-qr-btn" id="uf-qr-btn">▦ مسح QR للأصل</button><button type="button" class="uf-search-btn" id="uf-asset-search-btn">بحث</button></div><div class="uf-result asset" id="uf-asset-result"><div class="uf-result-img"></div><div class="uf-result-main"><b id="uf-asset-name">تم تحديد الأصل</b><small id="uf-asset-meta">بيانات الأصل المسجل لدى UNIFCO</small></div><span class="uf-ok">✓</span></div></div>
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
            $marker = '@@UF_NEVER@@';
            $formStart = '<main class="shell"><form id="requestForm"';
            $pos = strpos($html, $formStart);
            if ($pos !== false) {
                $sectionPos = strpos($html, '<section class="section">', $pos);
                if ($sectionPos !== false) {
                    $html = substr_replace($html, $block, $sectionPos, 0);
                }
            }
        }

        $script = <<<'HTML'
<script id="unifco-shared-customer-asset-intake-js-v1">
(function(){
 const root=document.getElementById('uf-shared-intake'); if(!root)return;
 const q=(s)=>document.querySelector(s), qa=(s)=>Array.from(document.querySelectorAll(s));
 const setActive=(selector,el)=>{qa(selector).forEach(x=>x.classList.toggle('active',x===el));};
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
 const customerBtn=q('#uf-customer-search-btn'); if(customerBtn)customerBtn.addEventListener('click',()=>{
   const val=(q('#uf-customer-search')?.value||'').trim(); if(!val)return;
   const original=q('#customer_number'); const lookup=q('#customer-lookup');
   if(original){original.value=val; original.dispatchEvent(new Event('input',{bubbles:true})); if(lookup)lookup.click();}
   q('#uf-customer-code').textContent=val;
   q('#uf-customer-result').classList.add('show');
   setTimeout(()=>{const company=q('#company_name')?.value||q('input[name="company_name"]')?.value; const person=q('#responsible_person')?.value||q('input[name="responsible_person"]')?.value; if(company)q('#uf-customer-name').textContent=company; if(person)q('#uf-customer-meta').textContent=person;},700);
 }));
 const assetBtn=q('#uf-asset-search-btn'); if(assetBtn)assetBtn.addEventListener('click',()=>{
   const val=(q('#uf-asset-search')?.value||'').trim(); if(!val)return;
   const key=q('#asset-key'); const find=q('#asset-find'); if(key){key.value=val; key.dispatchEvent(new Event('input',{bubbles:true})); if(find)find.click();}
   q('#uf-asset-name').textContent=val; q('#uf-asset-result').classList.add('show');
   setTimeout(()=>{const n=q('#ac-name')?.textContent; const code=q('#ac-code')?.textContent; const serial=q('#ac-serial')?.textContent; if(n&&n!=='—')q('#uf-asset-name').textContent=n; if(code&&code!=='—')q('#uf-asset-meta').textContent=[code,serial&&serial!=='—'?serial:''].filter(Boolean).join(' · ');},700);
 }));
 const qr=q('#uf-qr-btn'); if(qr)qr.addEventListener('click',()=>{ const methods=qa('.asset-method'); const qrMethod=methods.find(x=>x.dataset.method==='qr'); if(qrMethod)qrMethod.click(); const key=q('#asset-key'); if(key)key.focus(); });
 const mirror=(from,to)=>{const a=q(from),b=q(to); if(a&&b)a.addEventListener('input',()=>{b.value=a.value;b.dispatchEvent(new Event('input',{bubbles:true}));});};
 mirror('#uf-new-company','input[name="company_name"]'); mirror('#uf-new-contact','input[name="responsible_person"]'); mirror('#uf-new-mobile','input[name="mobile"]'); mirror('#uf-new-email','input[name="email"]'); mirror('#uf-new-asset-type','#asset_type'); mirror('#uf-new-asset-brand','#equipment_brand'); mirror('#uf-new-asset-model','#equipment_model');
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
