<?php

namespace App\Http\Middleware;

use App\Services\HomepageContentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeCmsMediaPresentation
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

        $locale = str_contains($html, '<html lang="en"') ? 'en' : 'ar';
        $home = app(HomepageContentService::class)->getContent($locale);

        $aboutImage = trim((string) ($home['about_image'] ?? ''));
        if ($aboutImage === '') {
            $aboutImage = '/images/home/about-technician-v14.webp';
        }

        $profileImage = '';
        $profileRows = $home['about_profile_images'] ?? [];
        if (is_array($profileRows) && isset($profileRows[0]) && is_array($profileRows[0])) {
            $profileImage = trim((string) ($profileRows[0][0] ?? ''));
        }

        if ($profileImage === '') {
            $profileImage = $locale === 'en'
                ? '/images/home/unifco-about-card-en.webp'
                : '/images/home/unifco-about-card-ar.webp';
        }

        $safeAbout = htmlspecialchars($aboutImage, ENT_QUOTES, 'UTF-8');
        $safeProfile = htmlspecialchars($profileImage, ENT_QUOTES, 'UTF-8');

        $html = str_replace(
            '<img src="/images/home/about-technician-v14.webp" alt="UNIFCO">',
            '<img src="'.$safeAbout.'" alt="UNIFCO">',
            $html
        );

        $override = '<style id="unifco-cms-about-media">'
            .'#about .about-media{background-image:url(\''.$safeAbout.'\')!important;background-size:cover!important;background-position:center 34%!important}'
            .'.showcase-clients .client-card{min-height:120px!important;padding:8px 10px!important;background:#fff!important}'
            .'.showcase-clients .client-card img{width:100%!important;height:96px!important;max-height:96px!important;object-fit:contain!important}'
            .'.process-icon svg{width:31px!important;height:31px!important;stroke:#fff!important;fill:none!important;stroke-width:1.9!important;stroke-linecap:round!important;stroke-linejoin:round!important}'
            .'</style>';
        $html = str_replace('</head>', $override.'</head>', $html);

        if ($locale === 'en') {
            $html = str_replace(
                '<img src="/images/home/unifco-about-card-en.webp" alt="About UNIFCO Facilities Contracting">',
                '<img src="'.$safeProfile.'" alt="About UNIFCO Facilities Contracting">',
                $html
            );
        } else {
            $html = str_replace(
                '<img src="/images/unifco-facility-hero.jpg" alt="UNIFCO Facilities Management">',
                '<img src="'.$safeProfile.'" alt="UNIFCO Facilities Management">',
                $html
            );
        }

        $processIcons = <<<'HTML'
<script id="unifco-process-icons">
document.addEventListener('DOMContentLoaded', function () {
    const icons = [
        `<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M8 4.5h11l4 4V19"/><path d="M19 4.5V9h4"/><path d="M11 12.5l1.7 1.7 3-3.2M11 18l1.7 1.7 2.3-2.5M10 24h4"/><circle cx="22" cy="22" r="5"/><path d="m25.7 25.7 3.3 3.3"/></svg>`,
        `<svg viewBox="0 0 32 32" aria-hidden="true"><rect x="8" y="6" width="16" height="22" rx="2"/><path d="M12 6V4h8v2M12 13h4M12 19c2-3 5-3 8-6M17 21l3-2 2 3"/><circle cx="13" cy="23" r="1.5"/></svg>`,
        `<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M20.5 5.5a6 6 0 0 0-7.2 7.7L5.8 20.7a3 3 0 0 0 4.2 4.2l7.5-7.5a6 6 0 0 0 7.7-7.2l-3.6 3.6-3.4-.8-.8-3.4z"/><circle cx="23.5" cy="23.5" r="4.2"/><path d="M23.5 17.7v2M23.5 27.3v2M17.7 23.5h2M27.3 23.5h2"/></svg>`,
        `<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M26 11a11 11 0 1 0 1 12"/><path d="M26 6v5h-5"/><path d="M9 23v-5h4v5M15 23v-9h4v9M21 23v-6h4v6"/></svg>`
    ];

    document.querySelectorAll('.process-step .process-icon').forEach(function (node, index) {
        if (icons[index]) node.innerHTML = icons[index];
    });
});
</script>
HTML;
        $html = str_replace('</body>', $processIcons.'</body>', $html);

        $response->setContent($html);

        return $response;
    }
}
