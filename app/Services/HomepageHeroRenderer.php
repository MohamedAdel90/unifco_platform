<?php

namespace App\Services;

class HomepageHeroRenderer
{
    public const MODE_BACKGROUND = 'background';
    public const MODE_FULL_BANNER = 'full_banner';

    public static function normalizeMode(?string $mode): string
    {
        return in_array($mode, [self::MODE_BACKGROUND, self::MODE_FULL_BANNER], true)
            ? $mode
            : self::MODE_BACKGROUND;
    }

    public function render(string $html, array $home): string
    {
        if ($html === '' || ! str_contains($html, '</head>')) {
            return $html;
        }

        $mode = self::normalizeMode((string) ($home['hero_render_mode'] ?? self::MODE_BACKGROUND));
        $heroImage = trim((string) ($home['hero_image'] ?? ''));
        if ($heroImage === '') {
            $heroImage = '/images/unifco-home-hero-20260902.webp';
        }

        $safeCssImage = str_replace(["\\", '"', "'", "\r", "\n"], ["\\\\", '\\"', "\\'", '', ''], $heroImage);
        $safeHtmlImage = htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8');

        $common = <<<'CSS'
<style id="unifco-home-hero-approved-layout">
.hero{position:relative!important;isolation:isolate!important;overflow:hidden!important;color:#fff!important;background:none!important;background-image:none!important}
.hero-inner{position:relative!important;z-index:2!important;display:flex!important;direction:ltr!important}
html[dir="rtl"] .hero-copy{width:min(570px,46%)!important;margin-left:18%!important;margin-right:0!important;padding:58px 0 46px!important;text-align:right!important}
html[dir="ltr"] .hero-copy{width:min(570px,46%)!important;margin-right:18%!important;margin-left:0!important;padding:58px 0 46px!important;text-align:left!important}
.hero-eyebrow{display:inline-block!important;position:relative!important;margin-bottom:18px!important;padding-bottom:14px!important;font-size:15px!important;font-weight:700!important;letter-spacing:.045em!important;color:#f1f5f9!important}
.hero-eyebrow:after{content:""!important;position:absolute!important;bottom:0!important;width:42px!important;height:3px!important;border-radius:999px!important;background:#e51b35!important}
html[dir="rtl"] .hero-eyebrow:after{right:0!important}html[dir="ltr"] .hero-eyebrow:after{left:0!important}
.hero h1{max-width:570px!important;margin:0 0 18px!important;font-size:clamp(46px,4.1vw,62px)!important;line-height:1.28!important;font-weight:900!important;letter-spacing:-.025em!important;text-wrap:balance!important}
.hero p{max-width:555px!important;font-size:15px!important;line-height:1.9!important;color:#e4ebf4!important}
.hero-actions{margin-top:25px!important;gap:14px!important}.hero-actions .btn{min-width:235px!important;min-height:56px!important;padding:12px 24px!important;border-radius:7px!important;font-size:14px!important;font-weight:800!important}
.hero-actions .btn.red{background:#d91c34!important;box-shadow:0 8px 22px rgba(206,18,45,.2)!important}.hero-actions .btn.outline{background:rgba(4,24,54,.34)!important;border-color:rgba(255,255,255,.72)!important}
.hero-proof{margin-top:42px!important;gap:0!important}.proof{min-width:155px!important;padding-inline:20px!important;gap:11px!important}.proof:first-child{padding-inline-start:0!important}.proof-icon{width:38px!important;height:38px!important;border-color:rgba(255,255,255,.42)!important}.proof b{font-size:11px!important;line-height:1.45!important}.proof small{font-size:9px!important;line-height:1.5!important;color:#d2dbe7!important}
@media(max-width:1200px){html[dir="rtl"] .hero-copy{margin-left:8%!important;width:min(560px,54%)!important}html[dir="ltr"] .hero-copy{margin-right:8%!important;width:min(560px,54%)!important}}
@media(max-width:850px){html[dir="rtl"] .hero-copy,html[dir="ltr"] .hero-copy{width:min(610px,76%)!important;margin-inline:0!important;padding:54px 0 42px!important}.hero h1{font-size:clamp(40px,7vw,54px)!important}.hero-actions .btn{min-width:190px!important}}
@media(max-width:700px){html[dir="rtl"] .hero-copy,html[dir="ltr"] .hero-copy{width:100%!important;padding:48px 0 38px!important}.hero h1{font-size:38px!important;max-width:520px!important}.hero p{font-size:12px!important;max-width:500px!important}.hero-actions{display:grid!important;grid-template-columns:1fr 1fr!important;width:100%!important}.hero-actions .btn{min-width:0!important;width:100%!important;min-height:50px!important;font-size:12px!important}.hero-proof{margin-top:30px!important;display:grid!important;grid-template-columns:repeat(3,1fr)!important}.proof{min-width:0!important;padding-inline:8px!important;gap:7px!important}.proof-icon{width:31px!important;height:31px!important}}
@media(max-width:450px){.hero h1{font-size:33px!important}.hero-actions{grid-template-columns:1fr!important}.hero-proof{gap:3px!important}.proof{display:block!important;text-align:center!important;border-inline-end:0!important}.proof-icon{margin:0 auto 6px!important}}
</style>
CSS;

        if ($mode === self::MODE_FULL_BANNER) {
            $modeCss = <<<'CSS'
<style id="unifco-home-hero-mode">
.hero{min-height:0!important;display:flex!important;justify-content:center!important;align-items:flex-start!important;padding:22px 0!important;background:#fff!important;background-image:none!important}
.hero::before,.hero::after{display:none!important;content:none!important;background:none!important}
.hero-inner{display:none!important}
.hero-full-banner{position:relative!important;width:50%!important;max-width:1024px!important;min-width:720px!important;line-height:0!important;margin:0 auto!important;border-radius:0!important;overflow:hidden!important;box-shadow:none!important}
.hero-full-banner-image{display:block!important;width:100%!important;height:auto!important;max-width:none!important;object-fit:contain!important;object-position:center!important;margin:0!important;padding:0!important}
.hero-banner-hotspot{position:absolute!important;z-index:8!important;display:block!important;background:transparent!important;border:0!important;border-radius:8px!important;cursor:pointer!important;line-height:1!important;font-size:0!important;color:transparent!important;text-indent:-9999px!important;overflow:hidden!important}
.hero-banner-hotspot:focus-visible{outline:3px solid #fff!important;outline-offset:3px!important;box-shadow:0 0 0 5px rgba(206,18,45,.92)!important}
.hero-banner-hotspot--contact{left:15.4%!important;top:60.0%!important;width:23.0%!important;height:8.8%!important}
.hero-banner-hotspot--request{left:39.6%!important;top:60.0%!important;width:24.2%!important;height:8.8%!important}
.hero-approved-proofs{position:absolute!important;z-index:7!important;left:15.2%!important;top:68.3%!important;width:48.8%!important;min-height:12.4%!important;display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;direction:ltr!important;align-items:center!important;padding:1.1% 1.2%!important;box-sizing:border-box!important;background:linear-gradient(90deg,rgba(5,28,59,.96),rgba(6,31,65,.92))!important;color:#fff!important;line-height:1.25!important}
.hero-approved-proof{min-width:0!important;display:grid!important;grid-template-columns:auto 1fr!important;align-items:center!important;gap:7%!important;padding:0 7%!important;box-sizing:border-box!important;direction:rtl!important;text-align:right!important;border-left:1px solid rgba(255,255,255,.55)!important}
.hero-approved-proof:first-child{border-left:0!important}
.hero-approved-proof svg{width:31px!important;height:31px!important;min-width:31px!important;display:block!important;fill:none!important;stroke:#fff!important;stroke-width:1.8!important;stroke-linecap:round!important;stroke-linejoin:round!important}
.hero-approved-proof-copy{min-width:0!important;line-height:1.25!important}
.hero-approved-proof b{display:block!important;margin:0 0 4px!important;color:#fff!important;font-size:12px!important;font-weight:800!important;white-space:nowrap!important;line-height:1.3!important}
.hero-approved-proof small{display:block!important;color:#eef4fb!important;font-size:9px!important;font-weight:500!important;white-space:nowrap!important;line-height:1.3!important}
html[dir="ltr"] .hero-approved-proof{direction:ltr!important;text-align:left!important}
@media(max-width:1100px){.hero-full-banner{width:72%!important;min-width:0!important}.hero{padding:18px 0!important}.hero-approved-proof svg{width:28px!important;height:28px!important;min-width:28px!important}.hero-approved-proof b{font-size:11px!important}.hero-approved-proof small{font-size:8px!important}}
@media(max-width:820px){.hero-full-banner{width:94%!important;min-width:0!important}.hero{padding:12px 0!important}}
@media(max-width:700px){.hero-full-banner{width:100%!important}.hero{padding:0!important}.hero-banner-hotspot{min-height:34px}.hero-approved-proofs{left:10%!important;top:69%!important;width:57%!important;min-height:13%!important}.hero-approved-proof{gap:5%!important;padding:0 4%!important}.hero-approved-proof svg{width:24px!important;height:24px!important;min-width:24px!important}.hero-approved-proof b{font-size:9px!important}.hero-approved-proof small{font-size:7px!important}}
@media(max-width:460px){.hero-approved-proofs{left:8%!important;width:62%!important}.hero-approved-proof svg{width:20px!important;height:20px!important;min-width:20px!important}.hero-approved-proof b{font-size:8px!important}.hero-approved-proof small{font-size:6px!important}}
</style>
CSS;

            $requestUrl = htmlspecialchars((string) ($home['request_service_url'] ?? '/request-service'), ENT_QUOTES, 'UTF-8');
            $contactUrl = '#contact';
            $isArabic = ($home['lang'] ?? 'ar') === 'ar';
            $requestLabel = $isArabic ? 'طلب الخدمة' : 'Request Service';
            $contactLabel = $isArabic ? 'تواصل معنا' : 'Contact Us';

            // ONE FACILITY SHOP remains part of the approved artwork exactly as-is.
            // Only the old proof/icon area is covered and replaced with these approved icons.
            $proofs = $isArabic
                ? [
                    ['location', 'تغطية شاملة', 'جميع مدن المملكة'],
                    ['shield', 'الجودة المعتمدة', 'معايير عالمية'],
                    ['headset', 'دعم على مدار 24/7', 'فريق متخصص'],
                ]
                : [
                    ['location', 'Nationwide Coverage', 'Across the Kingdom'],
                    ['shield', 'Certified Quality', 'Global Standards'],
                    ['headset', '24/7 Support', 'Specialist Team'],
                ];

            $icons = [
                'location' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.6"/></svg>',
                'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 19 6v5c0 4.7-2.9 8.3-7 10-4.1-1.7-7-5.3-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/></svg>',
                'headset' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13v-2a8 8 0 0 1 16 0v2"/><path d="M4 13h3v6H5a1 1 0 0 1-1-1v-5Zm16 0h-3v6h2a1 1 0 0 0 1-1v-5Z"/><path d="M17 19c0 1.1-.9 2-2 2h-3"/></svg>',
            ];

            $proofHtml = '<div class="hero-approved-proofs" aria-label="'.htmlspecialchars($isArabic ? 'مزايا الخدمة' : 'Service highlights', ENT_QUOTES, 'UTF-8').'">';
            foreach ($proofs as [$icon, $title, $subtitle]) {
                $proofHtml .= '<div class="hero-approved-proof">'
                    .$icons[$icon]
                    .'<span class="hero-approved-proof-copy"><b>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</b><small>'.htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8').'</small></span>'
                    .'</div>';
            }
            $proofHtml .= '</div>';

            $banner = '<div class="hero-full-banner">'
                .'<img class="hero-full-banner-image" src="'.$safeHtmlImage.'" alt="UNIFCO Hero Banner">'
                .$proofHtml
                .'<a class="hero-banner-hotspot hero-banner-hotspot--contact" href="'.$contactUrl.'" aria-label="'.htmlspecialchars($contactLabel, ENT_QUOTES, 'UTF-8').'">'.$contactLabel.'</a>'
                .'<a class="hero-banner-hotspot hero-banner-hotspot--request" href="'.$requestUrl.'" aria-label="'.htmlspecialchars($requestLabel, ENT_QUOTES, 'UTF-8').'">'.$requestLabel.'</a>'
                .'</div>';
            $html = preg_replace('/(<section\s+class="hero"[^>]*>)/i', '$1'.$banner, $html, 1) ?? $html;
        } else {
            $modeCss = <<<CSS
<style id="unifco-home-hero-mode">
.hero{min-height:610px!important;display:flex!important;align-items:center!important;background:none!important;background-image:none!important}
.hero::before{content:""!important;display:block!important;position:absolute!important;inset:0!important;z-index:0!important;pointer-events:none!important;background-image:linear-gradient(90deg,rgba(3,22,48,.98) 0%,rgba(5,30,62,.91) 30%,rgba(5,30,62,.54) 50%,rgba(5,30,62,.06) 74%),url('{$safeCssImage}')!important;background-position:center center!important;background-size:cover!important;background-repeat:no-repeat!important}
.hero::after{z-index:1!important}
.hero-inner{min-height:610px!important;align-items:center!important;justify-content:flex-start!important}
@media(max-width:850px){.hero,.hero-inner{min-height:560px!important}}
@media(max-width:700px){.hero,.hero-inner{min-height:590px!important}}
@media(max-width:450px){.hero,.hero-inner{min-height:610px!important}}
.hero-full-banner,.hero-full-banner-image{display:none!important}
</style>
CSS;
        }

        return str_replace('</head>', $common.$modeCss."\n</head>", $html);
    }
}
