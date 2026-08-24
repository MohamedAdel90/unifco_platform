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
                'home' => 'الرئيسية',
                'about' => 'من نحن',
                'services' => 'الخدمات',
                'industries' => 'القطاعات',
                'industrial' => 'صناعي',
                'commercial' => 'تجاري',
                'data_centers' => 'مراكز البيانات',
                'hospitals' => 'المستشفيات',
                'hotels' => 'الفنادق',
                'facilities' => 'المرافق',
                'projects' => 'المشاريع',
                'careers' => 'الوظائف',
                'contact' => 'تواصل معنا',
            ] : [
                'home' => 'Home',
                'about' => 'About Us',
                'services' => 'Services',
                'industries' => 'Industries',
                'industrial' => 'Industrial',
                'commercial' => 'Commercial',
                'data_centers' => 'Data Centers',
                'hospitals' => 'Hospitals',
                'hotels' => 'Hotels',
                'facilities' => 'Facilities',
                'projects' => 'Projects',
                'careers' => 'Careers',
                'contact' => 'Contact us',
            ];

            $navigation = '<nav class="nav-links public-primary-nav">'
                .'<a href="#home">'.$labels['home'].'</a>'
                .'<a href="#about">'.$labels['about'].'</a>'
                .'<a href="#services">'.$labels['services'].'</a>'
                .'<span class="nav-dropdown"><a class="nav-dropdown-trigger" href="#industries" aria-haspopup="true">'.$labels['industries'].' <span aria-hidden="true">⌄</span></a>'
                .'<span class="nav-dropdown-menu">'
                .'<a href="#industries">'.$labels['industrial'].'</a>'
                .'<a href="#industries">'.$labels['commercial'].'</a>'
                .'<a href="#industries">'.$labels['data_centers'].'</a>'
                .'<a href="#industries">'.$labels['hospitals'].'</a>'
                .'<a href="#industries">'.$labels['hotels'].'</a>'
                .'</span></span>'
                .'<a href="#facilities">'.$labels['facilities'].'</a>'
                .'<a href="#projects">'.$labels['projects'].'</a>'
                .'<a href="#careers">'.$labels['careers'].'</a>'
                .'<a href="#contact">'.$labels['contact'].'</a>'
                .'</nav>';

            $html = preg_replace('/<nav class="nav-links">.*?<\/nav>/s', $navigation, $html, 1) ?? $html;
            $html = str_replace('<section class="operations">', '<section class="operations" id="facilities">', $html);
        }

        $style = <<<'HTML'
<style id="dynamic-brand-logo-presentation">
/* Shared presentation rules for the dynamic logo asset. */
img[src*="/brand/unifco-logo-v3.webp"]{object-fit:contain!important;image-rendering:auto}

/* Authenticated application shell: make the sidebar logo more prominent without distortion. */
.brand-logo-wrap{justify-content:center!important;align-items:center!important;min-height:118px!important;padding:7px 10px!important;overflow:hidden!important}
.brand-logo-wrap .brand-logo{display:block!important;width:min(252px,96%)!important;max-width:252px!important;height:100px!important;max-height:100px!important;flex:0 1 252px!important;object-fit:contain!important;object-position:center!important;margin:auto!important}
.brand-logo-wrap .brand-wordmark{display:none!important}
body.sidebar-collapsed .brand-logo-wrap{min-height:66px!important;padding:6px!important}
body.sidebar-collapsed .brand-logo-wrap .brand-logo{width:50px!important;max-width:50px!important;height:50px!important;max-height:50px!important;flex:0 0 50px!important}

/* Public website header/footer: larger, cleaner brand footprint. */
.top .logo{width:clamp(190px,18vw,260px)!important;height:84px!important;max-width:260px!important;object-fit:contain!important;object-position:left center!important;padding:2px 0!important}
html[dir="rtl"] .top .logo{object-position:right center!important}
.foot-logo{width:clamp(150px,16vw,190px)!important;height:72px!important;max-width:190px!important;object-fit:contain!important;padding:7px 10px!important;background:#fff!important;border-radius:10px!important}

/* Public top bar ordering only: keep existing styling, dimensions, and colors. */
.top .nav .brand-link{order:1!important;margin-right:0!important}
.top .nav .nav-actions{order:2!important}
.top .nav .nav-actions .lang{order:1!important}
.top .nav .nav-actions .btn:not(.red){order:2!important}
.top .nav .nav-actions .btn.red{order:3!important}
.top .nav .nav-links{order:3!important;margin-left:auto!important}
.top .nav .menu-toggle{order:4!important}

/* Homepage primary navigation and Industries dropdown. */
.public-primary-nav{gap:15px!important;overflow:visible!important}
.public-primary-nav>a,.public-primary-nav .nav-dropdown-trigger{font-size:11px!important}
.public-primary-nav .nav-dropdown{position:relative;display:inline-flex;align-items:center;align-self:stretch}
.public-primary-nav .nav-dropdown-trigger{display:inline-flex;align-items:center;gap:4px;height:100%}
.public-primary-nav .nav-dropdown-menu{position:absolute;top:calc(100% - 6px);inset-inline-start:50%;transform:translateX(-50%);display:none;min-width:190px;padding:8px;background:#fff;border:1px solid #e1e6ed;border-radius:8px;box-shadow:0 14px 32px rgba(7,31,77,.16);z-index:120}
.public-primary-nav .nav-dropdown-menu a{display:block;padding:9px 11px;border-radius:5px;font-size:10.5px!important;font-weight:750!important;color:#25354d!important;white-space:nowrap}
.public-primary-nav .nav-dropdown-menu a:hover,.public-primary-nav .nav-dropdown-menu a:focus{background:#f4f7fb;color:#071f4d!important}
.public-primary-nav .nav-dropdown:hover .nav-dropdown-menu,.public-primary-nav .nav-dropdown:focus-within .nav-dropdown-menu{display:block}
.public-primary-nav .nav-dropdown-menu a:after{display:none!important}

/* Expanded homepage services: keep card language, but make 13 services balance across rows. */
.service-grid{grid-template-columns:repeat(auto-fit,minmax(205px,1fr))!important;gap:14px!important}
.service-card{min-width:0!important}
.service-image{height:150px!important}
.service-body b{min-height:42px!important;font-size:11px!important}
.service-body p{min-height:72px!important;font-size:9.5px!important}

/* Login page: preserve a premium wide-logo treatment. */
.hero-logo{width:min(430px,78%)!important;height:165px!important;object-fit:contain!important;object-position:left center!important}
.card-logo{width:min(290px,88%)!important;height:118px!important;object-fit:contain!important;padding:4px 8px!important}
.feature-logo{width:175px!important;height:62px!important;object-fit:contain!important;padding:4px 8px!important}

/* Admin branding preview: show realistic white and transparent-background context. */
.logo-preview{min-height:250px!important;background:linear-gradient(45deg,#f4f6f9 25%,transparent 25%),linear-gradient(-45deg,#f4f6f9 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#f4f6f9 75%),linear-gradient(-45deg,transparent 75%,#f4f6f9 75%),#fff!important;background-size:22px 22px!important;background-position:0 0,0 11px,11px -11px,-11px 0!important;padding:28px!important}
.logo-preview img{width:min(520px,92%)!important;max-width:520px!important;height:180px!important;max-height:180px!important;object-fit:contain!important;background:rgba(255,255,255,.94)!important;border:1px solid #e4e9f1!important;border-radius:14px!important;padding:18px!important;box-shadow:0 10px 28px rgba(19,33,55,.08)!important}

@media(max-width:1180px){
  .public-primary-nav{gap:11px!important}
  .public-primary-nav>a,.public-primary-nav .nav-dropdown-trigger{font-size:10px!important}
}
@media(max-width:850px){
  .brand-logo-wrap{min-height:104px!important;padding:7px 9px!important}
  .brand-logo-wrap .brand-logo{width:min(238px,96%)!important;max-width:238px!important;height:88px!important;max-height:88px!important}
  .top .logo{width:170px!important;height:70px!important;max-width:170px!important}
  .service-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}
}
@media(max-width:600px){
  .hero-logo{width:min(300px,80%)!important;height:120px!important}
  .card-logo{width:min(240px,86%)!important;height:100px!important}
  .top .logo{width:150px!important;height:62px!important;max-width:150px!important}
  .foot-logo{width:150px!important;height:64px!important}
  .service-grid{grid-template-columns:1fr!important}
}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));

        return $response;
    }
}
