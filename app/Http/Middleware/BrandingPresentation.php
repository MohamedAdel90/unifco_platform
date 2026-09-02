<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BrandingPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        if (! str_contains($contentType, 'text/html') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) return $response;

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</head>')) return $response;

        if ($request->routeIs('public.home')) {
            $isArabic = str_contains($html, '<html lang="ar"');
            $labels = $isArabic ? [
                'home'=>'الرئيسية','about'=>'من نحن','services'=>'الخدمات','clients'=>'عملاؤنا','projects'=>'مشاريعنا','careers'=>'الوظائف','contact'=>'تواصل معنا',
            ] : [
                'home'=>'Home','about'=>'About Us','services'=>'Services','clients'=>'Our Clients','projects'=>'Our Projects','careers'=>'Careers','contact'=>'Contact us',
            ];
            $icons = $this->navigationIcons();
            $item = static fn (string $href, string $key) => '<a href="'.$href.'"><span class="nav-icon">'.$icons[$key].'</span><span class="nav-label">'.$labels[$key].'</span></a>';
            $navigation = '<nav class="nav-links public-primary-nav">'
                .$item('#home','home').$item('#about','about').$item('#services','services').$item('#clients','clients')
                .$item('#projects','projects').$item('#careers','careers').$item('#contact','contact').'</nav>';
            $html = preg_replace('/<nav class="nav-links">.*?<\/nav>/s', $navigation, $html, 1) ?? $html;

            if (! str_contains($html, 'id="clients"')) {
                $clientsSection = $isArabic ? <<<'HTML'
<section class="section public-clients-section" id="clients">
  <div class="wrap">
    <div class="section-head public-showcase-head">
      <div class="kicker">عملاؤنا وشركاء النجاح</div>
      <h2>شراكات نبنيها على الثقة وجودة التشغيل</h2>
      <p>نخدم جهات ومنشآت في قطاعات متعددة تحتاج إلى جاهزية مرافق عالية، استمرارية تشغيل، وصيانة موثوقة.</p>
    </div>
    <div class="public-client-grid">
      <article class="public-client-card"><span class="public-client-icon">⌂</span><b>الجهات الحكومية</b><small>تشغيل وصيانة المرافق والأصول</small></article>
      <article class="public-client-card"><span class="public-client-icon">▦</span><b>المنشآت التجارية</b><small>حلول مرافق وخدمات فنية متكاملة</small></article>
      <article class="public-client-card"><span class="public-client-icon">⚙</span><b>القطاع الصناعي</b><small>صيانة أنظمة الطاقة والمعدات الحرجة</small></article>
      <article class="public-client-card"><span class="public-client-icon">✚</span><b>المرافق الصحية</b><small>جاهزية تشغيلية واستمرارية الخدمات</small></article>
    </div>
    <div class="public-client-note"><strong>UNIFCO · ONE FACILITY SHOP</strong><span>شريك واحد لإدارة احتياجات منشأتك التشغيلية والفنية.</span></div>
  </div>
</section>
HTML
                : <<<'HTML'
<section class="section public-clients-section" id="clients">
  <div class="wrap">
    <div class="section-head public-showcase-head">
      <div class="kicker">OUR CLIENTS & SUCCESS PARTNERS</div>
      <h2>Partnerships built on trust and operational quality</h2>
      <p>We support organizations across sectors that depend on facility readiness, operational continuity and reliable maintenance.</p>
    </div>
    <div class="public-client-grid">
      <article class="public-client-card"><span class="public-client-icon">⌂</span><b>Government</b><small>Facility and asset operations & maintenance</small></article>
      <article class="public-client-card"><span class="public-client-icon">▦</span><b>Commercial Facilities</b><small>Integrated facility and technical services</small></article>
      <article class="public-client-card"><span class="public-client-icon">⚙</span><b>Industrial</b><small>Critical power and equipment maintenance</small></article>
      <article class="public-client-card"><span class="public-client-icon">✚</span><b>Healthcare Facilities</b><small>Operational readiness and service continuity</small></article>
    </div>
    <div class="public-client-note"><strong>UNIFCO · ONE FACILITY SHOP</strong><span>One accountable partner for your facility's operational and technical needs.</span></div>
  </div>
</section>
HTML;
                $html = str_replace('<section class="section alt" id="projects">', $clientsSection.'<section class="section alt public-projects-section" id="projects">', $html);
            }

            if ($isArabic) {
                $html = str_replace('<article class="project-panel"><h2>أعمالنا على أرض الواقع</h2>', '<article class="project-panel"><div class="kicker public-project-kicker">مشاريعنا</div><h2>مشاريعنا على أرض الواقع</h2><p class="public-project-intro">نماذج من نطاقات الأعمال التي تنفذها UNIFCO في التشغيل والصيانة والأنظمة الكهربائية وMEP وإدارة المرافق.</p>', $html);
            } else {
                $html = str_replace('<article class="project-panel"><h2>Our work in action</h2>', '<article class="project-panel"><div class="kicker public-project-kicker">OUR PROJECTS</div><h2>Our projects in action</h2><p class="public-project-intro">Examples of UNIFCO delivery scopes across operations, maintenance, electrical systems, MEP and facility management.</p>', $html);
            }
        }

        $compat = '<link rel="preload" as="image" href="/brand/unifco-logo-v2.webp">';
        $style = <<<'HTML'
<style id="dynamic-brand-logo-presentation">
img[src*="/brand/unifco-logo-v2.webp"],img[src*="/brand/unifco-logo-v3.webp"]{object-fit:contain!important;image-rendering:auto}
.brand-logo-wrap{justify-content:center!important;align-items:center!important;min-height:118px!important;padding:7px 10px!important;overflow:hidden!important}
.brand-logo-wrap .brand-logo{display:block!important;width:min(252px,96%)!important;max-width:252px!important;height:100px!important;max-height:100px!important;flex:0 1 252px!important;object-fit:contain!important;object-position:center!important;margin:auto!important}
.brand-logo-wrap .brand-wordmark{display:none!important}
body.sidebar-collapsed .brand-logo-wrap{min-height:66px!important;padding:6px!important}
body.sidebar-collapsed .brand-logo-wrap .brand-logo{width:50px!important;max-width:50px!important;height:50px!important;max-height:50px!important;flex:0 0 50px!important}
.top .logo{object-fit:contain!important;image-rendering:auto}
.foot-logo{width:clamp(150px,16vw,190px)!important;height:72px!important;max-width:190px!important;object-fit:contain!important;padding:7px 10px!important;background:#fff!important;border-radius:10px!important}
.top .nav .brand-link{order:1!important;margin-right:0!important}
.top .nav .nav-actions{order:2!important}
.top .nav .nav-actions .lang{order:1!important}
.top .nav .nav-actions .btn:not(.red){order:2!important}
.top .nav .nav-actions .btn.red{order:3!important}
.top .nav .nav-links{order:3!important;margin-left:auto!important}
.top .nav .menu-toggle{order:4!important}
.public-primary-nav{gap:20px!important;overflow:visible!important;align-items:stretch!important}
.public-primary-nav>a{display:inline-flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:4px!important;min-width:54px!important;padding:9px 2px 8px!important;font-size:11px!important;line-height:1.15!important;color:#20324e!important;text-align:center!important;transition:color .18s ease,transform .18s ease!important}
.public-primary-nav>a:hover{color:#071f4d!important;transform:translateY(-1px)}
.public-primary-nav .nav-icon{display:grid!important;place-items:center!important;width:21px!important;height:21px!important;color:currentColor!important}
.public-primary-nav .nav-icon svg{display:block!important;width:21px!important;height:21px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.7!important;stroke-linecap:round!important;stroke-linejoin:round!important}
.public-primary-nav .nav-label{display:block!important;white-space:nowrap!important;font-weight:800!important}
.service-grid{grid-template-columns:repeat(auto-fit,minmax(205px,1fr))!important;gap:14px!important}.service-card{min-width:0!important}.service-image{height:150px!important}.service-body b{min-height:42px!important;font-size:11px!important}.service-body p{min-height:72px!important;font-size:9.5px!important}
.hero-logo{width:min(430px,78%)!important;height:165px!important;object-fit:contain!important;object-position:left center!important}.card-logo{width:min(290px,88%)!important;height:118px!important;object-fit:contain!important;padding:4px 8px!important}.feature-logo{width:175px!important;height:62px!important;object-fit:contain!important;padding:4px 8px!important}
.logo-preview{min-height:250px!important;background:linear-gradient(45deg,#f4f6f9 25%,transparent 25%),linear-gradient(-45deg,#f4f6f9 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#f4f6f9 75%),linear-gradient(-45deg,transparent 75%,#f4f6f9 75%),#fff!important;background-size:22px 22px!important;background-position:0 0,0 11px,11px -11px,-11px 0!important;padding:28px!important}.logo-preview img{width:min(520px,92%)!important;max-width:520px!important;height:180px!important;max-height:180px!important;object-fit:contain!important;background:rgba(255,255,255,.94)!important;border:1px solid #e4e9f1!important;border-radius:14px!important;padding:18px!important;box-shadow:0 10px 28px rgba(19,33,55,.08)!important}
.public-clients-section{position:relative;background:#fff;overflow:hidden}.public-clients-section:before{content:"";position:absolute;inset-inline-start:-120px;top:-150px;width:330px;height:330px;border-radius:50%;background:rgba(7,31,77,.035)}.public-showcase-head{position:relative;z-index:1;max-width:820px}.public-showcase-head .kicker{font-size:clamp(24px,2.35vw,34px)!important;line-height:1.3!important;font-weight:900!important;margin-bottom:10px!important}.public-client-grid{position:relative;z-index:1;display:grid;grid-template-columns:repeat(4,1fr);gap:14px;direction:ltr}.public-client-card{min-height:150px;padding:22px 18px;border:1px solid #dfe5ed;border-radius:12px;background:linear-gradient(145deg,#fff,#f8fafc);box-shadow:0 5px 18px rgba(10,31,62,.055);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;direction:inherit;transition:.18s}.public-client-card:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(10,31,62,.1);border-color:#ccd6e2}.public-client-icon{width:48px;height:48px;border-radius:50%;display:grid;place-items:center;background:#071f4d;color:#fff;font-size:20px;margin-bottom:10px}.public-client-card b{color:#071f4d;font-size:13px;margin-bottom:4px}.public-client-card small{color:#68758a;font-size:9.5px;line-height:1.7}.public-client-note{position:relative;z-index:1;margin-top:16px;padding:14px 18px;border-radius:10px;background:linear-gradient(100deg,#071f4d,#123d72);color:#fff;display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;text-align:center}.public-client-note strong{font-family:Inter,Arial,sans-serif;font-size:11px}.public-client-note span{color:#d7e2f0;font-size:10px}.public-projects-section{padding-top:58px!important;padding-bottom:58px!important}.public-projects-section .projects-layout{grid-template-columns:minmax(0,1.45fr) minmax(280px,.55fr)!important;gap:20px!important}.public-projects-section .project-panel{padding:24px!important;border-radius:12px!important;box-shadow:0 8px 24px rgba(10,31,62,.07)!important}.public-project-kicker{font-size:clamp(24px,2.35vw,34px)!important;line-height:1.3!important;font-weight:900!important;margin-bottom:10px!important}.public-project-intro{color:#68758a;font-size:10.5px;line-height:1.8;margin:-4px 0 14px}.public-projects-section .project-gallery{gap:10px!important}.public-projects-section .project-gallery img{height:165px!important;border-radius:8px!important}.public-projects-section .emergency-panel{border-radius:12px!important;padding:24px!important;background:#fff!important;box-shadow:0 8px 24px rgba(10,31,62,.05)!important}
@media(max-width:1180px){.public-primary-nav{gap:12px!important}.public-primary-nav>a{font-size:10px!important;min-width:48px!important}.public-primary-nav .nav-icon,.public-primary-nav .nav-icon svg{width:18px!important;height:18px!important}.public-client-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:850px){.brand-logo-wrap{min-height:104px!important;padding:7px 9px!important}.brand-logo-wrap .brand-logo{width:min(238px,96%)!important;max-width:238px!important;height:88px!important;max-height:88px!important}.service-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}.public-projects-section .projects-layout{grid-template-columns:1fr!important}}
@media(max-width:600px){.hero-logo{width:min(300px,80%)!important;height:120px!important}.card-logo{width:min(240px,86%)!important;height:100px!important}.foot-logo{width:150px!important;height:64px!important}.service-grid{grid-template-columns:1fr!important}.public-client-grid{grid-template-columns:1fr!important}.public-client-card{min-height:130px!important}.public-projects-section .project-gallery img{height:125px!important}.public-showcase-head .kicker,.public-project-kicker{font-size:22px!important}}
</style>
HTML;
        $response->setContent(str_replace('</head>', $compat.$style."\n</head>", $html));
        return $response;
    }

    private function navigationIcons(): array
    {
        return [
            'home'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5"/><path d="M9.5 20v-6h5v6"/></svg>',
            'about'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19c.6-3.4 2.6-5.2 5.5-5.2s4.9 1.8 5.5 5.2"/><circle cx="17" cy="9" r="2.4"/><path d="M15.5 14.5c2.9-.3 4.7 1.2 5 4"/></svg>',
            'services'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v2M12 19.2v2M2.8 12h2M19.2 12h2M5.5 5.5l1.4 1.4M17.1 17.1l1.4 1.4M18.5 5.5l-1.4 1.4M6.9 17.1l-1.4 1.4"/><circle cx="12" cy="12" r="7"/></svg>',
            'clients'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="3"/><circle cx="5.5" cy="9.5" r="2"/><circle cx="18.5" cy="9.5" r="2"/><path d="M7 20c.4-4 2.1-6 5-6s4.6 2 5 6M2.5 18c.2-2.8 1.4-4.2 3.6-4.2M21.5 18c-.2-2.8-1.4-4.2-3.6-4.2"/></svg>',
            'projects'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21h16M6.5 21V6.5l5-2.5v17M11.5 8H17v13M8.5 9h1M8.5 12h1M8.5 15h1M14 11h1M14 14h1M14 17h1"/></svg>',
            'careers'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7M3 11.5c5.6 2.3 12.4 2.3 18 0M10 12h4"/></svg>',
            'contact'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
        ];
    }
}
