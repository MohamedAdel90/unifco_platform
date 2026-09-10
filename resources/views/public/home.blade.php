@php $home = app(\App\Services\HomepageContentService::class)->getContent('ar'); @endphp
@include('public.partials.home-reference-layout', ['home' => $home])
<link rel="stylesheet" href="/css/maintenance-approved-20260910.css?v=20260910-5">

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