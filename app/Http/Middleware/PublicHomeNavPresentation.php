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
        $response->setContent($html);

        return $response;
    }
}
