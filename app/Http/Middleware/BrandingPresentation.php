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

        $style = <<<'HTML'
<style id="dynamic-brand-logo-presentation">
/* Shared presentation rules for the dynamic logo asset. */
img[src*="/brand/unifco-logo-v3.webp"]{object-fit:contain!important;image-rendering:auto}

/* Authenticated application shell: use the sidebar width and give square/vertical logos more height. */
.brand-logo-wrap{justify-content:center!important;align-items:center!important;min-height:108px!important;padding:8px 12px!important;overflow:hidden!important}
.brand-logo-wrap .brand-logo{display:block!important;width:min(232px,94%)!important;max-width:232px!important;height:88px!important;max-height:88px!important;flex:0 1 232px!important;object-fit:contain!important;object-position:center!important;margin:auto!important}
.brand-logo-wrap .brand-wordmark{display:none!important}
body.sidebar-collapsed .brand-logo-wrap{min-height:64px!important;padding:7px!important}
body.sidebar-collapsed .brand-logo-wrap .brand-logo{width:48px!important;max-width:48px!important;height:48px!important;max-height:48px!important;flex:0 0 48px!important}

/* Public website header/footer: larger, cleaner brand footprint. */
.top .logo{width:clamp(150px,14vw,205px)!important;height:68px!important;max-width:205px!important;object-fit:contain!important;object-position:left center!important;padding:4px 0!important}
html[dir="rtl"] .top .logo{object-position:right center!important}
.foot-logo{width:clamp(150px,16vw,190px)!important;height:72px!important;max-width:190px!important;object-fit:contain!important;padding:7px 10px!important;background:#fff!important;border-radius:10px!important}

/* Login page: preserve a premium wide-logo treatment. */
.hero-logo{width:min(430px,78%)!important;height:165px!important;object-fit:contain!important;object-position:left center!important}
.card-logo{width:min(290px,88%)!important;height:118px!important;object-fit:contain!important;padding:4px 8px!important}
.feature-logo{width:175px!important;height:62px!important;object-fit:contain!important;padding:4px 8px!important}

/* Admin branding preview: show realistic white and transparent-background context. */
.logo-preview{min-height:250px!important;background:linear-gradient(45deg,#f4f6f9 25%,transparent 25%),linear-gradient(-45deg,#f4f6f9 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#f4f6f9 75%),linear-gradient(-45deg,transparent 75%,#f4f6f9 75%),#fff!important;background-size:22px 22px!important;background-position:0 0,0 11px,11px -11px,-11px 0!important;padding:28px!important}
.logo-preview img{width:min(520px,92%)!important;max-width:520px!important;height:180px!important;max-height:180px!important;object-fit:contain!important;background:rgba(255,255,255,.94)!important;border:1px solid #e4e9f1!important;border-radius:14px!important;padding:18px!important;box-shadow:0 10px 28px rgba(19,33,55,.08)!important}

@media(max-width:850px){
  .brand-logo-wrap{min-height:96px!important;padding:7px 10px!important}
  .brand-logo-wrap .brand-logo{width:min(218px,94%)!important;max-width:218px!important;height:78px!important;max-height:78px!important}
  .top .logo{width:145px!important;height:60px!important}
}
@media(max-width:600px){
  .hero-logo{width:min(300px,80%)!important;height:120px!important}
  .card-logo{width:min(240px,86%)!important;height:100px!important}
  .top .logo{width:132px!important;height:54px!important}
  .foot-logo{width:150px!important;height:64px!important}
}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));

        return $response;
    }
}
