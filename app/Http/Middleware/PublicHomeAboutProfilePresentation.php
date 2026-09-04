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
        $title = $isArabic ? 'تعرف على UNIFCO' : 'Discover UNIFCO';
        $close = $isArabic ? 'إغلاق' : 'Close';
        $fullPage = $isArabic ? 'عرض صفحة من نحن كاملة' : 'View full About page';
        $aboutUrl = route('public.about');

        $style = <<<'HTML'
<style id="unifco-profile-modal-style">
#unifco-profile-modal{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:26px;background:rgba(4,18,41,.78);backdrop-filter:blur(8px)}
#unifco-profile-modal.is-open{display:flex}
.unifco-profile-dialog{position:relative;width:min(1120px,96vw);max-height:92vh;overflow:auto;border-radius:22px;background:#f7f9fc;box-shadow:0 30px 90px rgba(0,0,0,.36);border:1px solid rgba(255,255,255,.25)}
.unifco-profile-head{position:sticky;top:0;z-index:5;display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px 24px;border-bottom:1px solid #e2e7ef;background:rgba(255,255,255,.96);backdrop-filter:blur(10px)}
.unifco-profile-head-title{display:flex;align-items:center;gap:12px}.unifco-profile-mark{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;background:#0b2854;color:#fff;font-weight:900;font-size:14px;box-shadow:inset 0 -3px 0 #ce122d}
.unifco-profile-head h2{margin:0;color:#0b2449;font-size:22px}.unifco-profile-head p{margin:2px 0 0;color:#6b7789;font-size:11px}
.unifco-profile-close{display:grid;width:40px;height:40px;place-items:center;border:1px solid #d7dee8;border-radius:50%;background:#fff;color:#0b2449;font-size:22px;cursor:pointer}
.unifco-profile-content{padding:26px}.unifco-profile-hero{display:grid;grid-template-columns:1.1fr .9fr;gap:18px;margin-bottom:18px}.unifco-profile-intro{padding:26px;border-radius:18px;background:linear-gradient(135deg,#0c2c5d,#071f4d);color:#fff;position:relative;overflow:hidden}.unifco-profile-intro:after{content:"";position:absolute;width:240px;height:240px;border-radius:50%;background:rgba(206,18,45,.16);left:-70px;bottom:-110px}.unifco-profile-kicker{color:#ffccd2;font-size:12px;font-weight:900;margin-bottom:8px}.unifco-profile-intro h3{position:relative;z-index:1;margin:0 0 14px;font-size:30px;line-height:1.35}.unifco-profile-intro p{position:relative;z-index:1;margin:0;color:#e8edf5;line-height:2;font-size:14px}
.unifco-profile-facts{display:grid;grid-template-columns:1fr 1fr;gap:12px}.unifco-profile-fact{padding:18px;border:1px solid #e0e6ee;border-radius:16px;background:#fff}.unifco-profile-fact-icon{display:grid;width:42px;height:42px;place-items:center;margin-bottom:12px;border-radius:12px;background:#eef3fa;color:#0b2854}.unifco-profile-fact-icon svg{width:23px;height:23px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.unifco-profile-fact b{display:block;color:#0b2449;font-size:14px;margin-bottom:5px}.unifco-profile-fact span{color:#667488;font-size:11px;line-height:1.7}
.unifco-profile-section{padding:24px;margin-top:16px;border:1px solid #e0e6ee;border-radius:18px;background:#fff}.unifco-profile-section-head{display:flex;align-items:center;gap:12px;margin-bottom:14px}.unifco-profile-section-icon{display:grid;width:44px;height:44px;place-items:center;border-radius:13px;background:#0b2854;color:#fff}.unifco-profile-section-icon.red{background:#ce122d}.unifco-profile-section-icon svg{width:24px;height:24px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.unifco-profile-section h3{margin:0;color:#0b2449;font-size:21px}.unifco-profile-section p{margin:0;color:#4f5e72;line-height:2.05;font-size:13.5px}.unifco-profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.unifco-profile-highlight{border-inline-start:4px solid #ce122d;background:linear-gradient(180deg,#fff,#fbfcfe)}
.unifco-profile-actions{display:flex;justify-content:center;padding:4px 26px 28px}.unifco-profile-actions .btn{min-width:220px}.unifco-profile-english-fallback .about-grid{margin:0}.unifco-profile-english-fallback .about-copy>.btn{display:none}.unifco-profile-english-fallback .stats{margin-top:20px}body.unifco-profile-open{overflow:hidden}
@media(max-width:820px){.unifco-profile-hero,.unifco-profile-grid{grid-template-columns:1fr}.unifco-profile-facts{grid-template-columns:1fr 1fr}.unifco-profile-content{padding:16px}.unifco-profile-intro{padding:22px}.unifco-profile-intro h3{font-size:25px}}
@media(max-width:560px){#unifco-profile-modal{padding:8px}.unifco-profile-dialog{width:100%;max-height:95vh;border-radius:14px}.unifco-profile-head{padding:13px 14px}.unifco-profile-head h2{font-size:18px}.unifco-profile-content{padding:12px}.unifco-profile-facts{grid-template-columns:1fr}.unifco-profile-section{padding:18px}.unifco-profile-intro h3{font-size:22px}.unifco-profile-intro p,.unifco-profile-section p{font-size:12.5px}.unifco-profile-actions{padding:2px 14px 18px}}
</style>
HTML;

        $arabicBody = <<<'HTML'
<div class="unifco-profile-content" dir="rtl">
  <div class="unifco-profile-hero">
    <section class="unifco-profile-intro">
      <div class="unifco-profile-kicker">من نحن</div>
      <h3>وحدة المرافق للمقاولات</h3>
      <p>منشأة سعودية 100% مصنفة في مجال التشغيل والصيانة والإنشاءات، ولديها اعتماد وعدة عقود مع عدد من الجهات الحكومية والخاصة كمقاول باطن ومزود خدمة. نعمل لتحقيق أعلى متطلبات الجودة في الخدمات المقدمة لعملائنا من خلال فريق عمل ذي كفاءة وخبرة عالية، يمتلك القدرات الفنية والأدوات اللازمة، ومدعوم بإدارة محترفة وأنظمة تقنية مساعدة.</p>
    </section>
    <div class="unifco-profile-facts">
      <div class="unifco-profile-fact"><div class="unifco-profile-fact-icon"><svg viewBox="0 0 24 24"><path d="M4 20V8l8-4 8 4v12"/><path d="M9 20v-6h6v6M8 10h.01M12 10h.01M16 10h.01"/></svg></div><b>منشأة سعودية</b><span>هوية وطنية وخبرة تشغيلية محلية.</span></div>
      <div class="unifco-profile-fact"><div class="unifco-profile-fact-icon"><svg viewBox="0 0 24 24"><path d="M12 3 4 7v5c0 5 3 8 8 9 5-1 8-4 8-9V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg></div><b>جودة واعتمادية</b><span>التزام بمعايير واضحة في التنفيذ والمتابعة.</span></div>
      <div class="unifco-profile-fact"><div class="unifco-profile-fact-icon"><svg viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M2 20v-1a6 6 0 0 1 12 0v1M12 20v-1a6 6 0 0 1 10-4"/></svg></div><b>فرق متخصصة</b><span>مهندسون وفنيون واستشاريون بخبرات متنوعة.</span></div>
      <div class="unifco-profile-fact"><div class="unifco-profile-fact-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 15v2M11 11v6M15 8v9M19 12v5"/></svg></div><b>أنظمة وتقنية</b><span>أدوات تشغيلية تدعم القياس والتحسين المستمر.</span></div>
    </div>
  </div>

  <section class="unifco-profile-section unifco-profile-highlight">
    <div class="unifco-profile-section-head"><div class="unifco-profile-section-icon"><svg viewBox="0 0 24 24"><path d="M4 19V9l8-5 8 5v10"/><path d="M8 19v-6h8v6"/></svg></div><h3>منهجنا</h3></div>
    <p>في وحدة المرافق للمقاولات، لم نأتِ لنُكرر ما هو قائم، بل جئنا لنُعيد تعريف مفهوم إدارة وتشغيل المرافق، بمنهجٍ يقوم على الإتقان والابتكار والالتزام المطلق بالجودة. نحن لا نقدم خدمات تقليدية، بل نصنع قيمة مستدامة تُمكّن عملاءنا من التركيز على رؤاهم، بينما نتولى نحن التفاصيل التشغيلية بأعلى درجات الدقة والاحتراف. نؤمن أن المرافق ليست مجرد هياكل أو أنظمة، بل كيانات حيوية تنبض بالحركة والتفاعل، وتستحق إدارة ذكية تحفظ كفاءتها وتُعزز من قدرتها على العطاء. ومن هذا المنطلق، عملنا على بناء منظومة تشغيلية متكاملة، تُدار بعقول هندسية وفرق متخصصة وتقنيات تتطور باستمرار.</p>
  </section>

  <div class="unifco-profile-grid">
    <section class="unifco-profile-section">
      <div class="unifco-profile-section-head"><div class="unifco-profile-section-icon red"><svg viewBox="0 0 24 24"><path d="M3 12h18M12 3v18"/><circle cx="12" cy="12" r="8"/></svg></div><h3>رؤيتنا</h3></div>
      <p>أن نُصبح الشركة السعودية الرائدة إقليميًا في تقديم حلول متكاملة لإدارة المرافق والتشغيل والصيانة، لا باعتبارنا مجرد مزوّد خدمات، بل شريكًا استراتيجيًا يدفع عجلة التحول الوطني نحو مدن ذكية ومجتمعات مستدامة وبنى تحتية تعمل بكفاءة عالية وتحاكي المستقبل. نطمح أن نكون الاسم الذي يُقترن بالجودة والابتكار والاستدامة في كل منشأة نخدمها، وأن نُحدِث فرقًا حقيقيًا في حياة الناس من خلال تشغيل ذكي يُلبي احتياجات اليوم ويتطور مع تحديات الغد.</p>
    </section>
    <section class="unifco-profile-section">
      <div class="unifco-profile-section-head"><div class="unifco-profile-section-icon"><svg viewBox="0 0 24 24"><path d="M4 18h16M6 18V8l6-4 6 4v10"/><path d="M9 13h6"/></svg></div><h3>رسالتنا</h3></div>
      <p>رسالتنا تتمثل في بناء بيئة تشغيلية ذكية، آمنة ومتطورة، تمكّن عملاءنا من التركيز على أهدافهم الأساسية بينما نتولى نحن إدارة التفاصيل التقنية والتشغيلية بكل دقة واحتراف. نُؤمن أن إدارة المرافق ليست مجرد خدمات، بل مسؤولية استثمارية تعزز كفاءة الأصول وتدعم استمرارية الأعمال.</p>
    </section>
  </div>

  <section class="unifco-profile-section unifco-profile-highlight">
    <div class="unifco-profile-section-head"><div class="unifco-profile-section-icon red"><svg viewBox="0 0 24 24"><path d="M12 2 9 9l-7 3 7 3 3 7 3-7 7-3-7-3-3-7Z"/></svg></div><h3>ما يميزنا</h3></div>
    <p>نحن لا نُجيد العمل فقط، بل نُتقنه. ما يميزنا ليس فقط خبرتنا، بل فلسفتنا في التنفيذ. نُفكر كما يُفكر القادة، ونعمل كما يعمل المحترفون، ونسعى كما يسعى الطموحون. نُقدم حلولًا مصممة حسب احتياجات كل عميل، ونبني استراتيجيات مرنة قادرة على التكيّف والنمو. فريقنا هو رأس مالنا الحقيقي، ويضم نخبة من المهندسين والفنيين والاستشاريين المدربين وفق أعلى المعايير. التكنولوجيا ليست مجرد أداة لدينا، بل جزء من منظومتنا التشغيلية، نستخدمها لتحسين الأداء وتقليل التكاليف وزيادة الكفاءة التشغيلية.</p>
  </section>
</div>
HTML;

        $modalBody = $isArabic
            ? $arabicBody
            : '<div class="unifco-profile-content unifco-profile-english-fallback" id="unifco-profile-body"></div>';

        $modal = '<div id="unifco-profile-modal" role="dialog" aria-modal="true" aria-labelledby="unifco-profile-title" aria-hidden="true">'
            .'<div class="unifco-profile-dialog">'
            .'<div class="unifco-profile-head"><div class="unifco-profile-head-title"><span class="unifco-profile-mark">U</span><div><h2 id="unifco-profile-title">'.e($title).'</h2><p>UNIFCO · One Facility Shop</p></div></div><button class="unifco-profile-close" type="button" data-unifco-profile-close aria-label="'.e($close).'">×</button></div>'
            .$modalBody
            .'<div class="unifco-profile-actions"><a class="btn" href="'.e($aboutUrl).'">'.e($fullPage).'</a></div>'
            .'</div></div>';

        $script = <<<'HTML'
<script id="unifco-profile-modal-script">
(()=>{
 const modal=document.getElementById('unifco-profile-modal');
 if(!modal)return;
 const trigger=document.querySelector('#about .about-copy > .btn');
 const close=()=>{modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('unifco-profile-open');};
 const open=()=>{
   const body=modal.querySelector('#unifco-profile-body');
   const source=document.querySelector('#about .wrap');
   if(body && source && !body.dataset.loaded){
     const grid=source.querySelector('.about-grid');
     const stats=source.querySelector('.stats');
     if(grid) body.appendChild(grid.cloneNode(true));
     if(stats) body.appendChild(stats.cloneNode(true));
     body.dataset.loaded='1';
   }
   modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('unifco-profile-open');
   modal.querySelector('[data-unifco-profile-close]')?.focus();
 };
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
