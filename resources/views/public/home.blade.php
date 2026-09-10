@php $home = app(\App\Services\HomepageContentService::class)->getContent('ar'); @endphp
@include('public.partials.home-reference-layout', ['home' => $home])
<link rel="stylesheet" href="/css/maintenance-approved-20260910.css?v=20260910-6">

<style id="unifco-arabic-home-hierarchy">
html[dir="rtl"] .kicker{font-size:17px;line-height:1.5;margin-bottom:8px;font-weight:900}
html[dir="rtl"] .process{direction:rtl}
html[dir="rtl"] .process-step{direction:rtl}
html[dir="rtl"] .process-step:not(:last-child):after{content:"←";right:auto;left:-27px}
#services .service-card[hidden]{display:none!important}
#services .center-action .btn.red{cursor:pointer}
@media(max-width:700px){html[dir="rtl"] .kicker{font-size:15px}}
</style>
<script id="unifco-services-row-toggle">
(()=>{const section=document.getElementById('services');if(!section)return;const grid=section.querySelector('.service-grid'),cards=[...section.querySelectorAll('.service-card')],button=section.querySelector('.center-action .btn.red');if(!grid||!button||!cards.length)return;let expanded=false;const columns=()=>{const value=getComputedStyle(grid).gridTemplateColumns.trim();return value?Math.max(1,value.split(/\s+/).length):1};const render=()=>{const visible=columns();cards.forEach((card,index)=>card.hidden=!expanded&&index>=visible);button.textContent=expanded?'إخفاء الخدمات':'عرض جميع الخدمات';button.setAttribute('aria-expanded',expanded?'true':'false')};button.setAttribute('href','#services');button.addEventListener('click',event=>{event.preventDefault();expanded=!expanded;render();if(!expanded)section.scrollIntoView({behavior:'smooth',block:'start'})});window.addEventListener('resize',render,{passive:true});render()})();
</script>

<style id="unifco-maintenance-approved-runtime-v2">
.operations .unifco-maintenance-visual-card{position:relative!important;display:block!important;aspect-ratio:1546/1017!important;min-height:0!important;padding:0!important;border:0!important;border-radius:18px!important;overflow:hidden!important;background:#f7f9fc!important;box-shadow:0 18px 44px rgba(7,31,77,.10)!important}
.ufm-approved{position:absolute;inset:0;display:grid;grid-template-columns:42% 58%;background:#fff;font-family:"Cairo",Tahoma,Arial,sans-serif;direction:ltr}
.ufm-photo{position:relative;overflow:hidden;background:#0a315f}
.ufm-photo img{width:100%;height:100%;object-fit:cover;object-position:center 35%;filter:saturate(.92) contrast(1.03)}
.ufm-photo:after{content:"";position:absolute;inset:0;background:linear-gradient(0deg,rgba(4,28,61,.78) 0%,rgba(4,28,61,.08) 58%,rgba(4,28,61,0) 82%)}
.ufm-brand{position:absolute;z-index:2;top:5.2%;left:6.5%;color:#071f4d;background:rgba(255,255,255,.92);padding:9px 15px;border-radius:8px;font-family:Inter,Arial,sans-serif;font-size:21px;font-weight:900;letter-spacing:.03em}.ufm-brand small{display:block;margin-top:2px;color:#68758a;font-size:7px;letter-spacing:.13em}
.ufm-photo-copy{position:absolute;z-index:2;left:7%;right:7%;bottom:7%;color:#fff;text-align:right;direction:rtl}.ufm-photo-copy b{display:block;font-size:clamp(24px,2.55vw,39px);line-height:1.45;font-weight:900}.ufm-photo-copy b:after{content:"";display:block;width:42px;height:5px;margin:14px 0 13px auto;border-radius:99px;background:#ed1737}.ufm-photo-copy p{margin:0;font-size:clamp(10px,1vw,15px);line-height:1.85;color:#eef3f8}.ufm-proof{display:flex;justify-content:space-between;gap:8px;margin-top:15px;padding-top:13px;border-top:1px solid rgba(255,255,255,.28);font-size:9px;font-weight:800}.ufm-proof span{white-space:nowrap}
.ufm-main{display:flex;flex-direction:column;padding:4.4% 4.2% 3.2%;background:linear-gradient(135deg,#fff 0%,#f7f9fc 100%);direction:rtl;text-align:right}
.ufm-kicker{display:flex;align-items:center;gap:9px;align-self:flex-start;color:#17345f;font-size:clamp(8px,.78vw,12px);font-weight:900}.ufm-kicker:before{content:"";width:34px;height:3px;border-radius:99px;background:#ed1737}
.ufm-main h2{margin:4.5% 0 1.4%;color:#071f4d;font-size:clamp(27px,3vw,47px);line-height:1.35;font-weight:900;letter-spacing:-.02em}.ufm-main>p{margin:0 0 3%;color:#68758a;font-size:clamp(9px,1vw,15px);line-height:1.7}
.ufm-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2.2%;direction:rtl;flex:1}.ufm-item{display:flex;min-width:0;flex-direction:column;justify-content:center;padding:8% 8.5%;border:1px solid #dde4ed;border-radius:13px;background:#fff;box-shadow:0 5px 15px rgba(10,31,62,.045)}.ufm-icon{width:42px;height:42px;display:grid;place-items:center;margin-bottom:7%;border-radius:50%;background:#f1f5fa;color:#071f4d;font-size:21px;font-weight:900}.ufm-item:nth-child(2) .ufm-icon,.ufm-item:nth-child(4) .ufm-icon{color:#ed1737}.ufm-item b{display:block;color:#071f4d;font-size:clamp(9px,.95vw,14px);line-height:1.45}.ufm-item small{display:block;margin-top:3px;color:#7a8799;font-size:clamp(7px,.68vw,10px);line-height:1.45}
.ufm-cta-panel{display:grid;grid-template-columns:1fr auto;align-items:center;gap:3%;margin-top:2.4%;padding:2.5% 3%;border-radius:13px;background:linear-gradient(100deg,#082b5d,#0a3a74);color:#fff;direction:rtl}.ufm-cta-copy b{display:block;font-size:clamp(10px,1.08vw,16px)}.ufm-cta-copy small{display:block;margin-top:2px;color:#cdd9e8;font-size:clamp(7px,.7vw,10px)}.ufm-cta{display:inline-flex;align-items:center;justify-content:center;min-width:190px;padding:12px 18px;border-radius:9px;background:#ed1737;color:#fff!important;font-size:clamp(9px,.86vw,13px);font-weight:900;box-shadow:0 8px 18px rgba(237,23,55,.25);cursor:pointer;pointer-events:auto!important}.ufm-cta:hover{transform:translateY(-1px);filter:brightness(1.04)}
@media(max-width:1080px){.operations .unifco-maintenance-visual-card{max-width:900px!important;margin-inline:auto!important}.ufm-brand{font-size:18px}.ufm-cta{min-width:165px}}
@media(max-width:700px){.operations .unifco-maintenance-visual-card{aspect-ratio:auto!important;min-height:720px!important}.ufm-approved{grid-template-columns:1fr;grid-template-rows:280px 1fr}.ufm-photo-copy b{font-size:27px}.ufm-proof{font-size:8px}.ufm-main{padding:24px 20px}.ufm-main h2{font-size:28px;margin:10px 0 5px}.ufm-grid{grid-template-columns:1fr 1fr;gap:10px}.ufm-item{padding:14px}.ufm-icon{width:36px;height:36px;margin-bottom:8px}.ufm-cta-panel{grid-template-columns:1fr;margin-top:12px;padding:14px}.ufm-cta{width:100%;min-width:0;margin-top:8px}}
</style>
<script id="unifco-maintenance-approved-runtime-v2-script">
(()=>{
 const maintenanceUrl=@json(route('public.request-service'));
 const visual=`<div class="ufm-approved" aria-label="من الصيانة التفاعلية إلى التشغيل المخطط">
   <div class="ufm-photo">
     <img src="/images/home/about-reference-technician.png?v=20260910-7" alt="فني صيانة UNIFCO">
     <div class="ufm-brand">UNIFCO<small>ONE FACILITY SHOP</small></div>
     <div class="ufm-photo-copy"><b>مرافقك<br>في أيدٍ أمينة</b><p>حلول متكاملة لصيانة وتشغيل مرافقك بكل كفاءة وموثوقية</p><div class="ufm-proof"><span>موثوقية</span><span>فريق متخصص</span><span>استجابة سريعة</span></div></div>
   </div>
   <div class="ufm-main">
     <div class="ufm-kicker">الصيانة الوقائية والتشغيل المخطط</div>
     <h2>من الصيانة التفاعلية إلى<br>التشغيل المخطط.</h2>
     <p>خدمات مرنة تلبي احتياجات منشأتك في مكان واحد</p>
     <div class="ufm-grid">
       <div class="ufm-item"><span class="ufm-icon">↗</span><b>تحسين الأداء</b><small>قرارات مبنية على بيانات</small></div>
       <div class="ufm-item"><span class="ufm-icon">⚙</span><b>توريد قطع الغيار</b><small>جودة وتوفر دائم</small></div>
       <div class="ufm-item"><span class="ufm-icon">🔧</span><b>خدمات الصيانة</b><small>صيانة دورية وتصحيحية</small></div>
       <div class="ufm-item"><span class="ufm-icon">✓</span><b>خطط وقائية</b><small>للتشغيل المستمر</small></div>
       <div class="ufm-item"><span class="ufm-icon">☑</span><b>متابعة أوامر العمل</b><small>شفافية في كل خطوة</small></div>
       <div class="ufm-item"><span class="ufm-icon">●</span><b>كوادر مؤهلة</b><small>خبرات تعتمد عليها</small></div>
     </div>
     <div class="ufm-cta-panel"><div class="ufm-cta-copy"><b>جاهز لبدء الصيانة لمرافقك؟</b><small>كفاءة أعلى • تكاليف أقل • استدامة أكبر</small></div><a class="ufm-cta" href="${maintenanceUrl}">تعرف على خدمات الصيانة ←</a></div>
   </div>
 </div>`;
 const apply=()=>{
   const card=document.querySelector('.operations .unifco-maintenance-visual-card, .operations .maintenance-card');
   if(!card)return false;
   if(card.querySelector('.ufm-approved'))return true;
   card.classList.add('unifco-maintenance-visual-card');
   card.innerHTML=visual;
   return true;
 };
 const start=()=>{apply();[50,150,400,900,1800,3500].forEach(ms=>setTimeout(apply,ms));const obs=new MutationObserver(()=>apply());obs.observe(document.body,{childList:true,subtree:true});};
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
</script>