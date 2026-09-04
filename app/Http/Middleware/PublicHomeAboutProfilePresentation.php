<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeAboutProfilePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || str_contains($html, 'id="unifco-profile-modal"')) {
            return $response;
        }

        $isArabic = str_contains($html, '<html lang="ar"');
        $close = $isArabic ? 'إغلاق' : 'Close';

        $style = <<<'HTML'
<style id="unifco-profile-modal-style">
#unifco-profile-modal{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:22px;background:rgba(7,22,43,.78);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)}
#unifco-profile-modal.is-open{display:flex}
.unifco-profile-dialog{position:relative;width:min(1320px,96vw);height:min(930px,94vh);overflow:auto;border-radius:18px;background:#fff;box-shadow:0 30px 90px rgba(0,0,0,.35)}
.unifco-profile-close{position:sticky;top:16px;margin:16px 0 -54px 16px;z-index:50;display:grid;width:42px;height:42px;place-items:center;border:1px solid #dfe5ec;border-radius:50%;background:#fff;color:#17315a;font-size:23px;cursor:pointer;box-shadow:0 4px 16px rgba(10,31,63,.08)}
.unifco-profile-sheet{direction:rtl;min-height:100%;padding:28px 30px 18px;background:#fff;color:#12284d;font-family:inherit;box-sizing:border-box}
.up-topline{display:flex;align-items:center;gap:26px;margin:0 74px 18px 0;color:#65748a;font-size:11px}.up-topline:before,.up-topline:after{content:"";height:1px;background:#dde4eb;flex:1}.up-topline span{white-space:nowrap}
.up-hero{display:grid;grid-template-columns:44% 56%;gap:28px;direction:ltr;align-items:start}
.up-visual{direction:ltr}.up-brand{display:flex;align-items:center;gap:14px;margin:0 0 18px}.up-wordmark{font-weight:950;font-size:48px;letter-spacing:.02em;color:#133564;line-height:.95;position:relative;padding-left:6px}.up-wordmark .red{color:#d51c36}.up-brand-copy{font-size:11px;line-height:1.35;color:#17315a;text-align:right;direction:rtl}.up-brand-copy b{display:block;font-size:15px}.up-brand-copy small{letter-spacing:.16em;font-size:8px}
.up-photo{position:relative;height:300px;border-radius:14px;overflow:hidden;background:#0b2d59}.up-photo img{display:block;width:100%;height:100%;object-fit:cover;object-position:center}.up-photo:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(6,24,50,.68),rgba(6,24,50,.02) 62%)}.up-photo-copy{position:absolute;left:28px;top:82px;z-index:2;color:#fff;text-align:left;direction:rtl;font-weight:900;font-size:25px;line-height:1.35}.up-photo-copy:after{content:"";display:block;width:33px;height:4px;background:#cf1630;margin-top:14px}.up-photo-foot{position:absolute;left:28px;bottom:18px;z-index:2;color:#e8edf4;font-size:8px;letter-spacing:.28em;line-height:1.65;text-align:left}
.up-copy{direction:rtl;text-align:right;padding-top:0}.up-title-row{display:flex;align-items:flex-start;justify-content:space-between;gap:20px}.up-title-block{border-right:3px solid #cf1630;padding-right:20px}.up-kicker{font-size:10px;letter-spacing:.34em;color:#ce1730;font-weight:900;margin-bottom:3px}.up-title{margin:0;color:#0b2855;font-size:54px;line-height:.95;font-weight:950}.up-subtitle{margin:8px 0 0;color:#17315a;font-size:19px;font-weight:850}.up-side-note{min-width:112px;border-top:3px solid #cf1630;padding-top:10px;text-align:center;color:#8390a0;font-size:10px;line-height:1.65}.up-copy>p{margin:25px 0 14px;color:#283b58;font-size:13px;line-height:1.85;font-weight:600}.up-quote{position:relative;border-radius:10px;background:#f5f6f8;padding:18px 22px;color:#2e405d;font-size:12px;line-height:1.95}.up-quote:before{content:'”';position:absolute;right:13px;top:-2px;color:#d71d35;font-size:42px;font-weight:900;line-height:1}.up-quote p{margin:0;padding-right:26px}
.up-values{display:grid;grid-template-columns:repeat(7,1fr);margin:22px 0 18px;border-top:1px solid #e4e8ee;border-bottom:1px solid #e4e8ee}.up-value{text-align:center;padding:14px 7px 12px;border-left:1px solid #e4e8ee}.up-value:last-child{border-left:0}.up-value svg{width:30px;height:30px;fill:none;stroke:#173c73;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;margin-bottom:6px}.up-value:nth-child(2n) svg{stroke:#cf1630}.up-value b{display:block;color:#102b55;font-size:11px}.up-value span{display:block;color:#7b8798;font-size:8.5px;margin-top:3px;line-height:1.4}
.up-cards{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;direction:rtl}.up-card{position:relative;border:1px solid #e1e6ed;border-radius:13px;background:#fff;padding:17px 19px 15px;min-height:218px;box-sizing:border-box}.up-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:13px}.up-card-head h3{margin:0;font-size:22px;color:#102b55;font-weight:950}.up-card-head h3:after{content:"";display:block;width:34px;height:3px;background:#cf1630;margin-top:7px}.up-card-icon{width:50px;height:50px;border-radius:11px;background:#123769;color:#fff;display:grid;place-items:center;font-size:25px}.up-card p{margin:0;color:#33465f;font-size:11.2px;line-height:1.85}.up-card-callout{margin-top:13px;padding:10px;border-radius:8px;background:linear-gradient(90deg,#fff1f2,#fae1e4);color:#17315a;font-weight:900;text-align:center;font-size:11px}.up-footer{display:flex;align-items:center;justify-content:center;gap:28px;margin-top:14px;color:#637287;font-size:9px;letter-spacing:.14em}.up-footer:before,.up-footer:after{content:"";height:1px;width:27%;background:#dce3eb}
body.unifco-profile-open{overflow:hidden}
@media(max-width:1000px){#unifco-profile-modal{padding:10px}.unifco-profile-dialog{width:98vw;height:96vh}.unifco-profile-sheet{padding:20px}.up-hero{grid-template-columns:1fr;direction:rtl}.up-visual{order:2}.up-copy{order:1}.up-values{grid-template-columns:repeat(4,1fr)}.up-cards{grid-template-columns:1fr}.up-title{font-size:42px}}
@media(max-width:620px){#unifco-profile-modal{padding:4px}.unifco-profile-dialog{width:100%;height:98vh;border-radius:12px}.unifco-profile-close{top:8px;margin:8px 0 -46px 8px;width:38px;height:38px}.unifco-profile-sheet{padding:14px 12px}.up-topline{margin-right:46px;font-size:8px}.up-title-row{display:block}.up-side-note{display:none}.up-title{font-size:34px}.up-subtitle{font-size:16px}.up-copy>p,.up-quote{font-size:11px}.up-wordmark{font-size:38px}.up-photo{height:245px}.up-photo-copy{font-size:21px;top:70px;left:20px}.up-values{grid-template-columns:repeat(2,1fr)}.up-cards{gap:10px}.up-card{min-height:auto;padding:15px}}
</style>
HTML;

        $arabicBody = <<<'HTML'
<div class="unifco-profile-sheet" dir="rtl">
  <div class="up-topline"><span>مرافق أكثر .. لمستقبل أفضل</span></div>
  <section class="up-hero">
    <div class="up-visual">
      <div class="up-brand">
        <div class="up-wordmark"><span class="red">U</span>NIFCO</div>
        <div class="up-brand-copy"><b>وحدة المرافق للمقاولات</b><small>FACILITIES MANAGEMENT</small></div>
      </div>
      <div class="up-photo">
        <img src="/images/home/about-technician-v14.webp" alt="UNIFCO Facilities Management">
        <div class="up-photo-copy">نبني<br>مرافق أكثر<br>للحياة أفضل</div>
        <div class="up-photo-foot">PEOPLE<br>FACILITIES<br>A BRIGHTER TOMORROW</div>
      </div>
    </div>
    <div class="up-copy">
      <div class="up-title-row">
        <div class="up-title-block"><div class="up-kicker">UNIFCO</div><h2 class="up-title">من نحن</h2><div class="up-subtitle">وحدة المرافق للمقاولات</div></div>
        <div class="up-side-note">خبرة<br>تشغيل<br>استدامة<br>لمستقبل أفضل</div>
      </div>
      <p>وحدة المرافق منشأة سعودية 100% مصنفة في مجال التشغيل والصيانة والإنشاءات، ولديها اعتماد وعدة عقود مع عدد من الجهات الحكومية والخاصة كمقاول باطن ومزود خدمة. نعمل لتحقيق أعلى متطلبات الجودة في الخدمات المقدمة لعملائنا من خلال فريق عمل ذي كفاءة وخبرة عالية، يمتلك القدرات الفنية والأدوات اللازمة، ومدعومًا بإدارة محترفة وأنظمة تقنية مساعدة.</p>
      <div class="up-quote"><p>في وحدة المرافق للمقاولات، لم نأتِ لنكرر ما هو قائم، بل جئنا لنعيد تعريف مفهوم إدارة وتشغيل المرافق. بمنهج يقوم على الإتقان والابتكار والالتزام المطلق بالجودة، نحن لا نقدم خدمات تقليدية، بل نصنع قيمة مستدامة تمكن عملاءنا من التركيز على رؤاهم، بينما نتولى نحن التفاصيل التشغيلية بأعلى درجات الدقة والاحتراف. نؤمن أن المرافق ليست مجرد هياكل أو أنظمة، بل كيانات حيوية تنبض بالحركة والتفاعل، وتستحق إدارة ذكية تحفظ كفاءتها وتعزز قدرتها على العطاء. ومن هذا المنطلق، عملنا في وحدة المرافق للمقاولات على بناء منظومة تشغيلية متكاملة، تدار بعقول هندسية، وفرق متخصصة، وتقنيات تتطور باستمرار.</p></div>
    </div>
  </section>

  <section class="up-values">
    <div class="up-value"><svg viewBox="0 0 24 24"><path d="M12 3 4 7v5c0 5 3 8 8 9 5-1 8-4 8-9V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg><b>السلامة</b><span>بيئة عمل أكثر أمانًا</span></div>
    <div class="up-value"><svg viewBox="0 0 24 24"><path d="M19 4C12 4 7 7 5 14c4 1 7 0 10-3"/><path d="M5 20c1-5 5-8 10-10"/></svg><b>الاستدامة</b><span>نحو مستقبل مسؤول</span></div>
    <div class="up-value"><svg viewBox="0 0 24 24"><path d="M9 18h6M10 22h4M8 14c-2-2-3-4-2-7a6 6 0 0 1 12 0c1 3 0 5-2 7-1 1-1 2-1 3H9c0-1 0-2-1-3Z"/></svg><b>الابتكار</b><span>حلول تصنع الفرق</span></div>
    <div class="up-value"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M12 12l4-4"/></svg><b>الاستراتيجية</b><span>شراكة طويلة المدى</span></div>
    <div class="up-value"><svg viewBox="0 0 24 24"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8"/><circle cx="12" cy="12" r="4"/></svg><b>الهندسة</b><span>خبرات هندسية متكاملة</span></div>
    <div class="up-value"><svg viewBox="0 0 24 24"><path d="m12 3 2.6 5.3 5.9.9-4.3 4.2 1 5.9-5.2-2.8-5.2 2.8 1-5.9-4.3-4.2 5.9-.9L12 3Z"/></svg><b>الجودة</b><span>معايير عالمية</span></div>
    <div class="up-value"><svg viewBox="0 0 24 24"><path d="M4 20V8l8-4 8 4v12"/><path d="M9 20v-6h6v6M8 10h.01M12 10h.01M16 10h.01"/></svg><b>إدارة المرافق</b><span>تشغيل بكفاءة عالية</span></div>
  </section>

  <section class="up-cards">
    <article class="up-card"><div class="up-card-head"><h3>رؤيتنا</h3><div class="up-card-icon">◉</div></div><p>أن نصبح الشركة السعودية الرائدة إقليميًا في تقديم حلول متكاملة لإدارة المرافق والتشغيل والصيانة، لا باعتبارنا مجرد مزود خدمات، بل شريكًا استراتيجيًا يدفع عجلة التحول الوطني نحو مدن ذكية، ومجتمعات مستدامة، وبنى تحتية تعمل بكفاءة عالية وتحاكي المستقبل. نطمح أن نكون الاسم الذي يقترن بالجودة والابتكار والاستدامة في كل منشأة نخدمها، وأن نحدث فرقًا حقيقيًا في حياة الناس من خلال تشغيل ذكي يلبي احتياجات اليوم ويتطور مع تحديات الغد.</p></article>
    <article class="up-card"><div class="up-card-head"><h3>رسالتنا</h3><div class="up-card-icon">➤</div></div><p>رسالتنا تتمثل في بناء بيئة تشغيلية ذكية، آمنة، ومتطورة تمكن عملاءنا من التركيز على أهدافهم الأساسية بينما نتولى نحن إدارة التفاصيل التقنية والتشغيلية بكل دقة واحتراف. نؤمن أن إدارة المرافق ليست مجرد خدمات، بل مسؤولية استثمارية تعزز كفاءة الأصول.</p><div class="up-card-callout">شريكك في بناء بيئات أكثر كفاءة واستدامة</div></article>
    <article class="up-card"><div class="up-card-head"><h3>ما يميزنا</h3><div class="up-card-icon">◇</div></div><p>نحن لا نجيد العمل فقط، بل نتقنه. ما يميزنا ليس فقط خبرتنا، بل فلسفتنا في التنفيذ. نفكر كما يفكر القادة، ونعمل كما يعمل المحترفون، ونسعى كما يسعى الطموحون. نقدم حلولًا مصممة حسب احتياجات كل عميل، ونبني استراتيجيات مرنة قادرة على التكيف والنمو. فريقنا هو رأس مالنا الحقيقي، يضم نخبة من المهندسين والفنيين والاستشاريين المدربين وفق أعلى المعايير.</p></article>
  </section>
  <div class="up-footer">مرافق اليوم ... لمستقبل أكثر استدامة</div>
</div>
HTML;

        $englishBody = <<<'HTML'
<div class="unifco-profile-sheet" dir="ltr"><section class="up-hero"><div class="up-visual"><div class="up-brand"><div class="up-wordmark"><span class="red">U</span>NIFCO</div></div><div class="up-photo"><img src="/images/home/about-technician-v14.webp" alt="UNIFCO Facilities Management"></div></div><div class="up-copy"><div class="up-title-block"><div class="up-kicker">UNIFCO</div><h2 class="up-title">Who We Are</h2><div class="up-subtitle">Facilities Management</div></div><p>UNIFCO is a Saudi facilities management company specializing in operations, maintenance and construction.</p></div></section></div>
HTML;

        $body = $isArabic ? $arabicBody : $englishBody;
        $modal = '<div id="unifco-profile-modal" aria-hidden="true" role="dialog" aria-modal="true"><div class="unifco-profile-dialog"><button type="button" class="unifco-profile-close" data-unifco-profile-close aria-label="'.e($close).'">×</button>'.$body.'</div></div>';

        $script = <<<'HTML'
<script id="unifco-profile-modal-script">
(()=>{
 const modal=document.getElementById('unifco-profile-modal');
 if(!modal)return;
 const trigger=document.querySelector('#about .about-copy > .btn');
 const close=()=>{modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('unifco-profile-open');};
 const open=()=>{modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('unifco-profile-open');modal.querySelector('[data-unifco-profile-close]')?.focus();};
 if(trigger){trigger.setAttribute('href','#unifco-profile-modal');trigger.setAttribute('aria-haspopup','dialog');trigger.addEventListener('click',e=>{e.preventDefault();open();});}
 modal.querySelector('[data-unifco-profile-close]')?.addEventListener('click',close);
 modal.addEventListener('click',e=>{if(e.target===modal)close();});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal.classList.contains('is-open'))close();});
})();
</script>
HTML;

        $html = str_replace('</head>', $style."\n</head>", $html);
        $html = str_replace('</body>', $modal."\n".$script."\n</body>", $html);
        $response->setContent($html);

        return $response;
    }
}
