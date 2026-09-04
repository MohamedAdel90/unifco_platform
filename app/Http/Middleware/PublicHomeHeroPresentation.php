<?php

namespace App\Http\Middleware;

use App\Services\HomepageContentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeHeroPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</head>')) {
            return $response;
        }

        $requested = $request->query('lang');
        $locale = in_array($requested, ['ar', 'en'], true)
            ? $requested
            : $request->session()->get('public_locale', 'ar');

        $home = app(HomepageContentService::class)->getContent($locale);
        $heroImage = trim((string) ($home['hero_image'] ?? ''));
        if ($heroImage === '') {
            $heroImage = '/images/unifco-home-hero-20260902.webp';
        }

        $safeHeroImage = str_replace(["\\", '"', "'", "\r", "\n"], ["\\\\", '\\"', "\\'", '', ''], $heroImage);

        // Important: the original Blade already contains a Hero background image.
        // We explicitly remove every background from .hero itself and render the
        // selected CMS image in ONE dedicated pseudo-element. This prevents the
        // legacy fallback and the CMS-selected image from ever being composited
        // as separate visible layers.
        $style = <<<HTML
<style id="unifco-home-hero-approved-layout">
.hero{
    min-height:610px!important;
    position:relative!important;
    isolation:isolate!important;
    background:none!important;
    background-image:none!important;
    overflow:hidden!important;
}
.hero::before{
    content:""!important;
    position:absolute!important;
    inset:0!important;
    z-index:0!important;
    pointer-events:none!important;
    background-image:linear-gradient(90deg,rgba(3,22,48,.98) 0%,rgba(5,30,62,.91) 30%,rgba(5,30,62,.54) 50%,rgba(5,30,62,.06) 74%),url('{$safeHeroImage}')!important;
    background-position:center center!important;
    background-size:cover!important;
    background-repeat:no-repeat!important;
}
.hero::after{
    z-index:1!important;
}
.hero-inner{
    position:relative!important;
    z-index:2!important;
    min-height:610px!important;
    align-items:center!important;
    justify-content:flex-start!important;
}
html[dir="rtl"] .hero-copy{
    width:min(570px,46%)!important;
    margin-left:18%!important;
    margin-right:0!important;
    padding:58px 0 46px!important;
    text-align:right!important;
}
html[dir="ltr"] .hero-copy{
    width:min(570px,46%)!important;
    margin-right:18%!important;
    margin-left:0!important;
    padding:58px 0 46px!important;
    text-align:left!important;
}
.hero-eyebrow{display:inline-block!important;position:relative!important;margin-bottom:18px!important;padding-bottom:14px!important;font-size:15px!important;font-weight:700!important;letter-spacing:.045em!important;color:#f1f5f9!important}
.hero-eyebrow:after{content:""!important;position:absolute!important;bottom:0!important;width:42px!important;height:3px!important;border-radius:999px!important;background:#e51b35!important}
html[dir="rtl"] .hero-eyebrow:after{right:0!important}
html[dir="ltr"] .hero-eyebrow:after{left:0!important}
.hero h1{max-width:570px!important;margin:0 0 18px!important;font-size:clamp(46px,4.1vw,62px)!important;line-height:1.28!important;font-weight:900!important;letter-spacing:-.025em!important;text-wrap:balance!important}
.hero p{max-width:555px!important;font-size:15px!important;line-height:1.9!important;color:#e4ebf4!important}
.hero-actions{margin-top:25px!important;gap:14px!important}
.hero-actions .btn{min-width:235px!important;min-height:56px!important;padding:12px 24px!important;border-radius:7px!important;font-size:14px!important;font-weight:800!important}
.hero-actions .btn.red{background:#d91c34!important;box-shadow:0 8px 22px rgba(206,18,45,.2)!important}
.hero-actions .btn.outline{background:rgba(4,24,54,.34)!important;border-color:rgba(255,255,255,.72)!important}
.hero-proof{margin-top:42px!important;gap:0!important}
.proof{min-width:155px!important;padding-inline:20px!important;gap:11px!important}
.proof:first-child{padding-inline-start:0!important}
.proof-icon{width:38px!important;height:38px!important;border-color:rgba(255,255,255,.42)!important}
.proof b{font-size:11px!important;line-height:1.45!important}
.proof small{font-size:9px!important;line-height:1.5!important;color:#d2dbe7!important}
@media(max-width:1200px){html[dir="rtl"] .hero-copy{margin-left:8%!important;width:min(560px,54%)!important}html[dir="ltr"] .hero-copy{margin-right:8%!important;width:min(560px,54%)!important}}
@media(max-width:850px){.hero,.hero-inner{min-height:560px!important}html[dir="rtl"] .hero-copy,html[dir="ltr"] .hero-copy{width:min(610px,76%)!important;margin-inline:0!important;padding:54px 0 42px!important}.hero h1{font-size:clamp(40px,7vw,54px)!important}.hero-actions .btn{min-width:190px!important}}
@media(max-width:700px){.hero,.hero-inner{min-height:590px!important}html[dir="rtl"] .hero-copy,html[dir="ltr"] .hero-copy{width:100%!important;padding:48px 0 38px!important}.hero h1{font-size:38px!important;max-width:520px!important}.hero p{font-size:12px!important;max-width:500px!important}.hero-actions{display:grid!important;grid-template-columns:1fr 1fr!important;width:100%!important}.hero-actions .btn{min-width:0!important;width:100%!important;min-height:50px!important;font-size:12px!important}.hero-proof{margin-top:30px!important;display:grid!important;grid-template-columns:repeat(3,1fr)!important}.proof{min-width:0!important;padding-inline:8px!important;gap:7px!important}.proof-icon{width:31px!important;height:31px!important}}
@media(max-width:450px){.hero,.hero-inner{min-height:610px!important}.hero h1{font-size:33px!important}.hero-actions{grid-template-columns:1fr!important}.hero-proof{gap:3px!important}.proof{display:block!important;text-align:center!important;border-inline-end:0!important}.proof-icon{margin:0 auto 6px!important}}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));

        return $response;
    }
}
