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
        if (! str_contains($contentType, 'text/html') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</head>')) {
            return $response;
        }

        if ($request->routeIs('public.home')) {
            $isArabic = str_contains($html, '<html lang="ar"');
            $labels = $isArabic ? [
                'home' => 'الرئيسية', 'about' => 'من نحن', 'services' => 'الخدمات', 'clients' => 'عملاؤنا',
                'projects' => 'المشاريع', 'careers' => 'الوظائف', 'contact' => 'تواصل معنا',
            ] : [
                'home' => 'Home', 'about' => 'About Us', 'services' => 'Services', 'clients' => 'Our Clients',
                'projects' => 'Projects', 'careers' => 'Careers', 'contact' => 'Contact us',
            ];

            $icons = $this->navigationIcons();
            $item = static fn (string $href, string $key) => '<a href="'.$href.'"><span class="nav-icon">'.$icons[$key].'</span><span class="nav-label">'.$labels[$key].'</span></a>';
            $navigation = '<nav class="nav-links public-primary-nav">'
                .$item('#home', 'home').$item('#about', 'about').$item('#services', 'services').$item('#industries', 'clients')
                .$item('#projects', 'projects').$item('#careers', 'careers').$item('#contact', 'contact').'</nav>';
            $html = preg_replace('/<nav class="nav-links">.*?<\/nav>/s', $navigation, $html, 1) ?? $html;
        }

        $isRequestPage = $request->is('request-service');
        if ($isRequestPage) {
            $isArabic = str_contains($html, '<html lang="ar"');
            $locale = $isArabic ? 'ar' : 'en';
            $otherLocale = $isArabic ? 'en' : 'ar';
            $labels = $isArabic ? [
                'home' => 'الرئيسية', 'about' => 'من نحن', 'services' => 'الخدمات', 'clients' => 'عملاؤنا',
                'projects' => 'المشاريع', 'careers' => 'الوظائف', 'contact' => 'تواصل معنا', 'lang' => 'EN',
            ] : [
                'home' => 'Home', 'about' => 'About Us', 'services' => 'Services', 'clients' => 'Our Clients',
                'projects' => 'Projects', 'careers' => 'Careers', 'contact' => 'Contact us', 'lang' => 'AR',
            ];
            $icons = $this->navigationIcons();
            $home = route('public.home', ['lang' => $locale]);
            $nav = static function (string $href, string $key) use ($icons, $labels): string {
                return '<a href="'.$href.'"><span class="nav-icon">'.$icons[$key].'</span><span class="nav-label">'.$labels[$key].'</span></a>';
            };
            $header = '<header class="top request-site-header"><div class="request-site-header-inner">'
                .'<a class="request-brand-link" href="'.$home.'"><img class="request-brand-logo" src="'.route('brand.logo').'" alt="UNIFCO"></a>'
                .'<a class="request-lang" href="'.$request->fullUrlWithQuery(['lang' => $otherLocale]).'">'.$labels['lang'].'</a>'
                .'<nav class="request-primary-nav">'
                .$nav($home.'#home', 'home').$nav($home.'#about', 'about').$nav($home.'#services', 'services').$nav($home.'#industries', 'clients')
                .$nav($home.'#projects', 'projects').$nav($home.'#careers', 'careers').$nav($home.'#contact', 'contact')
                .'</nav></div></header>';
            $html = preg_replace('/<header class="top">.*?<\/header>/s', $header, $html, 1) ?? $html;

            $heroTitle = $isArabic ? 'خدمة أسرع تبدأ بطلب أوضح' : 'Faster service starts with a clearer request';
            $heroText = $isArabic
                ? 'خطوات بسيطة تساعد فريق UNIFCO على فهم الخدمة المطلوبة بشكل سريع وواضح.'
                : 'Simple steps help the UNIFCO team understand the required service quickly and clearly.';
            $html = preg_replace(
                '/<section class="hero"><div class="heroin">.*?<\/div><\/section>/s',
                '<section class="hero request-hero"><div class="heroin"><h1>'.$heroTitle.'</h1><p>'.$heroText.'</p></div></section>',
                $html,
                1
            ) ?? $html;
        }

        $style = <<<'HTML'
<style id="dynamic-brand-logo-presentation">
img[src*="/brand/unifco-logo-v3.webp"]{object-fit:contain!important;image-rendering:auto}
.brand-logo-wrap{justify-content:center!important;align-items:center!important;min-height:118px!important;padding:7px 10px!important;overflow:hidden!important}
.brand-logo-wrap .brand-logo{display:block!important;width:min(252px,96%)!important;max-width:252px!important;height:100px!important;max-height:100px!important;flex:0 1 252px!important;object-fit:contain!important;object-position:center!important;margin:auto!important}
.brand-logo-wrap .brand-wordmark{display:none!important}
body.sidebar-collapsed .brand-logo-wrap{min-height:66px!important;padding:6px!important}
body.sidebar-collapsed .brand-logo-wrap .brand-logo{width:50px!important;max-width:50px!important;height:50px!important;max-height:50px!important;flex:0 0 50px!important}
.top .logo{width:clamp(190px,18vw,260px)!important;height:84px!important;max-width:260px!important;object-fit:contain!important;object-position:left center!important;padding:2px 0!important}
html[dir="rtl"] .top .logo{object-position:right center!important}
.foot-logo{width:clamp(150px,16vw,190px)!important;height:72px!important;max-width:190px!important;object-fit:contain!important;padding:7px 10px!important;background:#fff!important;border-radius:10px!important}
.top .nav .brand-link{order:1!important;margin-right:0!important}.top .nav .nav-actions{order:2!important}.top .nav .nav-actions .lang{order:1!important}.top .nav .nav-actions .btn:not(.red){order:2!important}.top .nav .nav-actions .btn.red{order:3!important}.top .nav .nav-links{order:3!important;margin-left:auto!important}.top .nav .menu-toggle{order:4!important}
.public-primary-nav{gap:20px!important;overflow:visible!important;align-items:stretch!important}.public-primary-nav>a{display:inline-flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:4px!important;min-width:54px!important;padding:9px 2px 8px!important;font-size:11px!important;line-height:1.15!important;color:#20324e!important;text-align:center!important;transition:color .18s ease,transform .18s ease!important}.public-primary-nav>a:hover{color:#071f4d!important;transform:translateY(-1px)}.public-primary-nav .nav-icon{display:grid!important;place-items:center!important;width:21px!important;height:21px!important;color:currentColor!important}.public-primary-nav .nav-icon svg{display:block!important;width:21px!important;height:21px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.7!important;stroke-linecap:round!important;stroke-linejoin:round!important}.public-primary-nav .nav-label{display:block!important;white-space:nowrap!important;font-weight:800!important}
.service-grid{grid-template-columns:repeat(auto-fit,minmax(205px,1fr))!important;gap:14px!important}.service-card{min-width:0!important}.service-image{height:150px!important}.service-body b{min-height:42px!important;font-size:11px!important}.service-body p{min-height:72px!important;font-size:9.5px!important}
.hero-logo{width:min(430px,78%)!important;height:165px!important;object-fit:contain!important;object-position:left center!important}.card-logo{width:min(290px,88%)!important;height:118px!important;object-fit:contain!important;padding:4px 8px!important}.feature-logo{width:175px!important;height:62px!important;object-fit:contain!important;padding:4px 8px!important}
.logo-preview{min-height:250px!important;background:linear-gradient(45deg,#f4f6f9 25%,transparent 25%),linear-gradient(-45deg,#f4f6f9 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#f4f6f9 75%),linear-gradient(-45deg,transparent 75%,#f4f6f9 75%),#fff!important;background-size:22px 22px!important;background-position:0 0,0 11px,11px -11px,-11px 0!important;padding:28px!important}.logo-preview img{width:min(520px,92%)!important;max-width:520px!important;height:180px!important;max-height:180px!important;object-fit:contain!important;background:rgba(255,255,255,.94)!important;border:1px solid #e4e9f1!important;border-radius:14px!important;padding:18px!important;box-shadow:0 10px 28px rgba(19,33,55,.08)!important}

/* Single-page request: align the top bar with the public homepage while omitting login/request CTA buttons. */
.request-site-header{height:86px!important;background:#fff!important;border-bottom:1px solid #e7edf4!important}
.request-site-header-inner{width:min(1460px,96%)!important;height:86px!important;margin:auto!important;display:flex!important;align-items:center!important;gap:14px!important;direction:ltr!important}
.request-brand-link{display:flex!important;align-items:center!important;text-decoration:none!important;flex:0 0 auto!important}
.request-brand-logo{display:block!important;width:190px!important;height:72px!important;object-fit:contain!important;object-position:left center!important}
.request-lang{display:grid!important;place-items:center!important;width:58px!important;height:46px!important;border:1px solid #d6e0eb!important;border-radius:10px!important;color:#071f4d!important;font-size:10px!important;font-weight:900!important;text-decoration:none!important;flex:0 0 58px!important}
.request-primary-nav{margin-left:auto!important;display:flex!important;align-items:center!important;gap:24px!important;height:100%!important}
html[dir="rtl"] .request-primary-nav{direction:rtl!important}
html[dir="ltr"] .request-primary-nav{direction:ltr!important}
.request-primary-nav>a{display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:4px!important;color:#20324e!important;text-decoration:none!important;font-size:10px!important;font-weight:800!important;white-space:nowrap!important}
.request-primary-nav .nav-icon{display:grid!important;place-items:center!important;width:21px!important;height:21px!important}.request-primary-nav svg{width:21px!important;height:21px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.7!important;stroke-linecap:round!important;stroke-linejoin:round!important}
.request-hero{min-height:185px!important}.request-hero .heroin{padding:30px 0 36px!important}.request-hero h1{margin:0 0 7px!important;font-size:clamp(30px,4vw,48px)!important}.request-hero p{font-size:12px!important}.request-hero .eyebrow{display:none!important}

/* Compact request choices. */
body:has(.request-site-header) .shell{margin-top:-34px!important}
body:has(.request-site-header) .card{padding:24px 28px!important}
body:has(.request-site-header) .intent-grid{gap:12px!important}
body:has(.request-site-header) .pick span{min-height:66px!important;padding:12px 16px!important;border-radius:11px!important}
body:has(.request-site-header) .pick span b{font-size:12px!important}
body:has(.request-site-header) .pick span small{font-size:8px!important;margin-top:3px!important}
body:has(.request-site-header) .subtypes{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;margin-top:8px!important}
body:has(.request-site-header) .subpick span{min-height:42px!important;padding:9px 8px!important;border-radius:9px!important;display:grid!important;place-items:center!important}
html[dir="rtl"] body:has(.request-site-header) #subQuote{width:calc((100% - 24px)/3)!important;margin-left:auto!important;margin-right:0!important}
html[dir="ltr"] body:has(.request-site-header) #subQuote{width:calc((100% - 24px)/3)!important;margin-left:0!important;margin-right:auto!important}
body:has(.request-site-header) #subMaintenance{width:calc((100% - 24px)/3)!important;margin-left:auto!important;margin-right:auto!important}
body:has(.request-site-header) #subConsult{display:none!important}
body:has(.request-site-header) #map{height:225px!important}

/* Quote-specific field language; JS below toggles these labels with the selected intent. */
body:has(.request-site-header) .quote-context-note{display:none;margin:0 0 14px;padding:10px 12px;border:1px solid #dfe7f1;border-radius:9px;background:#f8fbff;color:#54677f;font-size:9px}
body:has(.request-site-header).request-is-quote .quote-context-note{display:block}

@media(max-width:1180px){.public-primary-nav{gap:12px!important}.public-primary-nav>a{font-size:10px!important;min-width:48px!important}.public-primary-nav .nav-icon,.public-primary-nav .nav-icon svg{width:18px!important;height:18px!important}.request-primary-nav{gap:14px!important}.request-primary-nav>a{font-size:9px!important}.request-brand-logo{width:165px!important}}
@media(max-width:900px){.request-primary-nav{display:none!important}.request-site-header-inner{justify-content:flex-start!important}.request-lang{margin-left:0!important}}
@media(max-width:850px){.brand-logo-wrap{min-height:104px!important;padding:7px 9px!important}.brand-logo-wrap .brand-logo{width:min(238px,96%)!important;max-width:238px!important;height:88px!important;max-height:88px!important}.top .logo{width:170px!important;height:70px!important;max-width:170px!important}.service-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}}
@media(max-width:760px){html[dir] body:has(.request-site-header) #subQuote,body:has(.request-site-header) #subMaintenance{width:100%!important;margin-left:0!important;margin-right:0!important}.request-brand-logo{width:145px!important}.request-site-header,.request-site-header-inner{height:74px!important}}
@media(max-width:600px){.hero-logo{width:min(300px,80%)!important;height:120px!important}.card-logo{width:min(240px,86%)!important;height:100px!important}.top .logo{width:150px!important;height:62px!important;max-width:150px!important}.foot-logo{width:150px!important;height:64px!important}.service-grid{grid-template-columns:1fr!important}}
</style>
HTML;

        if ($isRequestPage) {
            $script = <<<'HTML'
<script id="public-request-presentation-logic">
(function(){
  const body=document.body;
  const radios=[...document.querySelectorAll('input[name="request_intent"]')];
  const subtypeGroups={QUOTATION:document.getElementById('subQuote'),SERVICE_REQUEST:document.getElementById('subMaintenance'),CONSULTATION:document.getElementById('subConsult')};
  const field=(name)=>document.querySelector(`[name="${name}"]`)?.closest('.field');
  const label=(name,text)=>{const el=field(name)?.querySelector('span'); if(el){const req=el.querySelector('.req'); el.childNodes[0].textContent=text+' '; if(req&&!el.contains(req))el.appendChild(req);}};
  const ar=document.documentElement.dir==='rtl';
  const sectionHeadings=[...document.querySelectorAll('.section h2')];
  const dataSection=sectionHeadings.find(h=>/بيانات الموقع والتواصل|Site & Contact Details/.test(h.textContent||''))?.closest('.section');
  if(dataSection && !dataSection.querySelector('.quote-context-note')){
    const n=document.createElement('div'); n.className='quote-context-note'; n.textContent=ar?'في طلبات عرض السعر تتغير الحقول الفنية لتناسب الصنف والكمية والموديل والمرفقات، وسيتم توسيع تفاصيل البنود في المرحلة التالية.':'For quotation requests, technical fields adapt to item, quantity, model and attachments; detailed line items will be expanded in the next phase.'; dataSection.insertBefore(n,dataSection.children[1]||null);
  }
  function sync(){
    const intent=radios.find(r=>r.checked)?.value||'QUOTATION';
    body.classList.toggle('request-is-quote',intent==='QUOTATION');
    Object.entries(subtypeGroups).forEach(([key,group])=>{if(!group)return; group.classList.toggle('show',key===intent && key!=='CONSULTATION');});
    if(intent==='CONSULTATION'){
      const consult=document.querySelector('input[name="request_subtype"][value="TECHNICAL_CONSULTATION"]'); if(consult)consult.checked=true;
    }
    if(intent==='QUOTATION'){
      label('asset_type',ar?'نوع المعدة / الصنف':'Equipment / Item Type');
      label('equipment_brand',ar?'الماركة / الشركة المصنعة':'Brand / Manufacturer');
      label('equipment_model',ar?'الموديل / رقم الصنف':'Model / Item Number');
      label('service_category',ar?'فئة طلب عرض السعر':'Quotation Category');
    }else{
      label('asset_type',ar?'نوع المعدة':'Equipment Type');
      label('equipment_brand',ar?'العلامة التجارية':'Brand');
      label('equipment_model',ar?'الموديل':'Model');
      label('service_category',ar?'الخدمة المطلوبة':'Service');
    }
  }
  radios.forEach(r=>r.addEventListener('change',sync)); sync();
})();
</script>
HTML;
            $html = str_replace('</body>', $script."\n</body>", $html);
        }

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));
        return $response;
    }

    private function navigationIcons(): array
    {
        return [
            'home' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5"/><path d="M9.5 20v-6h5v6"/></svg>',
            'about' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19c.6-3.4 2.6-5.2 5.5-5.2s4.9 1.8 5.5 5.2"/><circle cx="17" cy="9" r="2.4"/><path d="M15.5 14.5c2.9-.3 4.7 1.2 5 4"/></svg>',
            'services' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v2M12 19.2v2M2.8 12h2M19.2 12h2M5.5 5.5l1.4 1.4M17.1 17.1l1.4 1.4M18.5 5.5l-1.4 1.4M6.9 17.1l-1.4 1.4"/><circle cx="12" cy="12" r="7"/></svg>',
            'clients' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="3"/><circle cx="5.5" cy="9.5" r="2"/><circle cx="18.5" cy="9.5" r="2"/><path d="M7 20c.4-4 2.1-6 5-6s4.6 2 5 6M2.5 18c.2-2.8 1.4-4.2 3.6-4.2M21.5 18c-.2-2.8-1.4-4.2-3.6-4.2"/></svg>',
            'projects' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21h16M6.5 21V6.5l5-2.5v17M11.5 8H17v13M8.5 9h1M8.5 12h1M8.5 15h1M14 11h1M14 14h1M14 17h1"/></svg>',
            'careers' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7M3 11.5c5.6 2.3 12.4 2.3 18 0M10 12h4"/></svg>',
            'contact' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
        ];
    }
}
