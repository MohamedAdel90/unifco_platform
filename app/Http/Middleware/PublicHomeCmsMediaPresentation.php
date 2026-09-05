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

        $response->setContent($html);

        return $response;
    }
}
