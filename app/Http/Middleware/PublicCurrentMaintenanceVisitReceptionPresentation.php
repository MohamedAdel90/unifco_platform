<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenanceVisitReceptionPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        // The unified workspace is created client-side by another presentation middleware,
        // so it is not present in the server-rendered HTML yet. Gate on the native form
        // anchors that are guaranteed to exist instead.
        if (! str_contains($html, 'id="asset-section"') || ! str_contains($html, 'id="routine-form"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-visit-reception-cards-v2">
.uf-visit-reception{display:grid;grid-template-columns:minmax(0,3fr) minmax(330px,2fr);grid-template-areas:"reception visit";gap:16px;direction:rtl;align-items:stretch;margin:0 0 12px}
.uf-visit-reception .uf-vr-card{background:#fff;border:1px solid #dfe7f1;border-radius:14px;box-shadow:0 8px 24px rgba(7,31,77,.045);padding:14px 18px;min-width:0}
.uf-reception-card{grid-area:reception}.uf-visit-card{grid-area:visit}
.uf-vr-head{display:flex;align-items:flex-start;gap:9px;margin-bottom:10px}.uf-vr-icon{display:inline-flex;align-items:center;justify-content:center;color:#1976e8;flex:0 0 21px;margin-top:2px}.uf-vr-icon svg{width:21px;height:21px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}.uf-vr-copy{min-width:0;flex:1}.uf-vr-copy b{display:block;margin:0;color:#08295d;font-size:18px;font-weight:900;line-height:1.45}.uf-vr-copy small{display:block;margin-top:2px;color:#8392a8;font-size:9.5px;font-weight:600;line-height:1.5}
.uf-vr-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 10px}.uf-vr-field{display:grid;gap:4px;min-width:0}.uf-vr-field.full{grid-column:1/-1}.uf-vr-field label{display:flex;align-items:center;gap:5px;margin:0;color:#08295d;font-size:10.5px;font-weight:900;line-height:1.45}.uf-vr-field .req{color:#e3132c}.uf-vr-field input,.uf-vr-field select,.uf-vr-field textarea{width:100%;min-width:0;height:38px;min-height:38px;border:1px solid #cfdbea;border-radius:7px;background:#fff;padding:7px 10px;color:#233754;font:700 10px Cairo,Tahoma,Arial,sans-serif;outline:none}.uf-vr-field textarea{height:44px;min-height:44px;resize:none;line-height:1.5}.uf-vr-field input:focus,.uf-vr-field select:focus,.uf-vr-field textarea:focus{border-color:#7f9fca;box-shadow:0 0 0 3px rgba(35,128,244,.05)}
.uf-period{display:grid;grid-template-columns:1fr 1fr;gap:6px}.uf-period label{position:relative;display:block}.uf-period input{position:absolute;opacity:0;pointer-events:none}.uf-period span{height:38px;display:flex;align-items:center;justify-content:center;border:1px solid #d6e1ed;border-radius:7px;background:#fff;color:#24466f;font:800 9.5px Cairo;cursor:pointer}.uf-period input:checked+span{border-color:#2380f4;background:#eaf4ff;color:#1266bf;box-shadow:inset 0 0 0 1px #2380f4}.uf-period .morning input:checked+span{border-color:#e7bd69;background:#fff8e9;color:#aa7106}
@media(max-width:1000px){.uf-visit-reception{grid-template-columns:1fr;grid-template-areas:"reception" "visit"}}
@media(max-width:650px){.uf-visit-reception .uf-vr-card{padding:13px 14px}.uf-vr-grid{grid-template-columns:1fr}.uf-vr-field.full{grid-column:auto}.uf-vr-copy b{font-size:16px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-visit-reception-cards-script-v2">
(()=>{
  const mount=()=>{
    const workspace=document.getElementById('uf-request-workspace');
    if(!workspace||document.getElementById('uf-visit-reception'))return false;

    const section=document.createElement('section');
    section.id='uf-visit-reception';
    section.className='uf-visit-reception';
    section.innerHTML=`
      <div class="uf-vr-card uf-reception-card">
        <div class="uf-vr-head">
          <span class="uf-vr-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V7l7-4 7 4v13"/><path d="M8 20v-5h6v5M8 9h.01M11 9h.01M14 9h.01"/></svg></span>
          <div class="uf-vr-copy"><b>معلومات الموقع والاستقبال</b><small>بيانات مختصرة تساعد الفريق على الوصول والاستقبال</small></div>
        </div>
        <div class="uf-vr-grid">
          <div class="uf-vr-field"><label>اسم مسؤول الاستقبال <span class="req">*</span></label><input name="reception_contact_name" autocomplete="name" required></div>
          <div class="uf-vr-field"><label>رقم الجوال <span class="req">*</span></label><input name="reception_mobile" inputmode="tel" autocomplete="tel" placeholder="05XXXXXXXX" required></div>
          <div class="uf-vr-field"><label>هل يلزم تصريح دخول؟</label><select name="entry_permit_required"><option value="">اختر الإجابة</option><option value="1">نعم</option><option value="0">لا</option></select></div>
          <div class="uf-vr-field"><label>اسم البوابة / رقم البوابة</label><input name="entry_gate" placeholder="مثال: البوابة الرئيسية"></div>
          <div class="uf-vr-field full"><label>ملاحظات الوصول</label><textarea name="arrival_notes" placeholder="أي تعليمات تساعد الفني للوصول إلى الموقع"></textarea></div>
        </div>
      </div>
      <div class="uf-vr-card uf-visit-card">
        <div class="uf-vr-head">
          <span class="uf-vr-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 9h18M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01"/></svg></span>
          <div class="uf-vr-copy"><b>موعد الزيارة</b><small>حدد التاريخ والوقت المناسب لزيارة الموقع</small></div>
        </div>
        <div class="uf-vr-grid">
          <div class="uf-vr-field full"><label>التاريخ <span class="req">*</span></label><input type="date" name="visit_date" min="${new Date().toISOString().slice(0,10)}" required></div>
          <div class="uf-vr-field"><label>من <span class="req">*</span></label><input type="time" name="visit_from_time" required></div>
          <div class="uf-vr-field"><label>إلى <span class="req">*</span></label><input type="time" name="visit_to_time" required></div>
          <div class="uf-vr-field"><label>الفترة المفضلة</label><div class="uf-period"><label class="morning"><input type="radio" name="preferred_visit_period" value="morning"><span>صباحاً</span></label><label><input type="radio" name="preferred_visit_period" value="evening"><span>مساءً</span></label></div></div>
          <div class="uf-vr-field"><label>المدة المتوقعة للزيارة</label><select name="expected_visit_duration"><option value="">اختر المدة</option><option value="30">30 دقيقة</option><option value="60">ساعة</option><option value="90">ساعة ونصف</option><option value="120">ساعتان</option><option value="180">3 ساعات</option></select></div>
        </div>
      </div>`;
    workspace.after(section);
    return true;
  };

  if(mount()) return;
  let attempts=0;
  const timer=setInterval(()=>{
    attempts++;
    if(mount()||attempts>=30) clearInterval(timer);
  },50);
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
