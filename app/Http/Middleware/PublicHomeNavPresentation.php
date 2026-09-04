<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeNavPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '') {
            return $response;
        }

        $html = preg_replace('/<style id="dynamic-brand-logo-presentation">.*?<\/style>/s', '', $html) ?? $html;
        $html = str_replace('<link rel="preload" as="image" href="/brand/unifco-logo-v2.webp">', '', $html);

        // Restore only the approved header/navigation presentation that was lost
        // when the legacy BrandingPresentation stylesheet was removed. Do not
        // reintroduce its service/project/client layout overrides.
        $navStyle = <<<'HTML'
<style id="public-home-nav-presentation">
.top .nav .brand-link{order:1;margin-right:0}
.top .nav .nav-actions{order:2}
.top .nav .nav-actions .lang{order:1}
.top .nav .nav-actions .btn:not(.red){order:2}
.top .nav .nav-actions .btn.red{order:3}
.top .nav .nav-links{order:3;margin-left:auto}
.top .nav .menu-toggle{order:4}
.public-primary-nav{gap:20px;overflow:visible;align-items:stretch}
.public-primary-nav>a{display:inline-flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;min-width:54px;padding:9px 2px 8px;font-size:11px;line-height:1.15;color:#20324e;text-align:center;transition:color .18s ease,transform .18s ease}
.public-primary-nav>a:hover{color:#071f4d;transform:translateY(-1px)}
.public-primary-nav .nav-icon{display:grid;place-items:center;width:21px;height:21px;color:currentColor}
.public-primary-nav .nav-icon svg{display:block;width:21px;height:21px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
.public-primary-nav .nav-label{display:block;white-space:nowrap;font-weight:800}
@media(max-width:1180px){.public-primary-nav{gap:12px}.public-primary-nav>a{font-size:10px;min-width:48px}.public-primary-nav .nav-icon,.public-primary-nav .nav-icon svg{width:18px;height:18px}}
</style>
HTML;
        if (! str_contains($html, 'id="public-home-nav-presentation"')) {
            $html = str_replace('</head>', $navStyle."\n</head>", $html);
        }

        $isArabic = str_contains($html, '<html lang="ar"');

        $labels = $isArabic ? [
            'home' => 'الرئيسية',
            'about' => 'تعرف علينا',
            'services' => 'الخدمات',
            'industries' => 'القطاعات',
            'projects' => 'المشاريع',
            'clients' => 'العملاء',
            'careers' => 'الوظائف',
            'contact' => 'تواصل معنا',
        ] : [
            'home' => 'Home',
            'about' => 'About Us',
            'services' => 'Services',
            'industries' => 'Industries',
            'projects' => 'Projects',
            'clients' => 'Clients',
            'careers' => 'Careers',
            'contact' => 'Contact Us',
        ];

        $icons = [
            'home' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5M9 21v-7h6v7"/></svg>',
            'about' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg>',
            'services' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.36a1.7 1.7 0 0 0-1 .64 1.7 1.7 0 0 0-.36 1.1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.24 15a1.7 1.7 0 0 0-.64-1 1.7 1.7 0 0 0-1.1-.36H2.4v-4h.09A1.7 1.7 0 0 0 4 8.6a1.7 1.7 0 0 0-.34-1.87l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8.36 4.24a1.7 1.7 0 0 0 1-.64 1.7 1.7 0 0 0 .36-1.1V2.4h4v.09A1.7 1.7 0 0 0 14.76 4a1.7 1.7 0 0 0 1.87-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.76 8.36c.15.38.38.72.68 1 .3.28.69.43 1.1.44h.06v4h-.09A1.7 1.7 0 0 0 20 14.84c-.16.05-.36.1-.6.16Z"/></svg>',
            'industries' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V9l5 3V9l5 3V5h4v16"/><path d="M8 16h1M12 16h1M17 10h2"/></svg>',
            'projects' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V7h6v14M10 21V3h6v18M16 21v-9h4v9"/></svg>',
            'clients' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M2 21v-2a6 6 0 0 1 12 0v2M12 21v-2a6 6 0 0 1 10-4.5"/></svg>',
            'careers' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/></svg>',
            'contact' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
        ];

        $item = static fn (string $href, string $key): string => '<a href="'.$href.'"><span class="nav-icon">'.$icons[$key].'</span><span class="nav-label">'.$labels[$key].'</span></a>';

        $navigation = '<nav class="nav-links public-primary-nav">'
            .$item('#home', 'home')
            .$item('#about', 'about')
            .$item('#services', 'services')
            .$item('#industries', 'industries')
            .$item('#projects', 'projects')
            .$item('#clients', 'clients')
            .$item('#careers', 'careers')
            .$item('#contact', 'contact')
            .'</nav>';

        $html = preg_replace('/<nav class="nav-links public-primary-nav">.*?<\/nav>/s', $navigation, $html, 1) ?? $html;

        $targetLocale = $isArabic ? 'en' : 'ar';
        $targetLabel = strtoupper($targetLocale);
        $languageUrl = route('public.home', ['lang' => $targetLocale]);

        $html = preg_replace(
            '/<a class="lang" href="[^"]*">(?:EN|AR)<\/a>/',
            '<a class="lang" href="'.$languageUrl.'" data-language-switch="'.$targetLocale.'">'.$targetLabel.'</a>',
            $html,
            1
        ) ?? $html;

        $html = preg_replace_callback(
            '/(<nav class="wrap mobile-menu" id="mobile-menu">.*?)(<a href="[^"]*">(?:EN|AR)<\/a>)(<\/nav>)/s',
            static fn (array $matches): string => $matches[1].'<a href="'.$languageUrl.'" data-language-switch="'.$targetLocale.'">'.$targetLabel.'</a>'.$matches[3],
            $html,
            1
        ) ?? $html;

        $response->setContent($html);

        return $response;
    }
}
