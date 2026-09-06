<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeAboutLinkPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->routeIs('public.about')) {
            $requested = $request->query('lang');
            if (in_array($requested, ['ar', 'en'], true)) {
                $request->session()->put('public_locale', $requested);
            }
            $locale = in_array($requested, ['ar', 'en'], true)
                ? $requested
                : $request->session()->get('public_locale', 'ar');

            return response(view('public.about-unifco', compact('locale'))->render())
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        $isArabic = str_contains($html, '<html lang="ar"');
        $aboutUrl = route('public.about', ['lang' => $isArabic ? 'ar' : 'en']);

        $html = preg_replace('/<style id="unifco-company-profile-style">.*?<\/style>/s', '', $html) ?? $html;
        $html = preg_replace('/<div class="company-profile-overlay" id="company-profile-overlay".*?<script id="unifco-company-profile-script">.*?<\/script>/s', '', $html) ?? $html;
        $html = preg_replace('/<script id="unifco-company-profile-script">.*?<\/script>/s', '', $html) ?? $html;

        $html = str_replace('href="#" id="about-company-trigger"', 'href="'.$aboutUrl.'"', $html);
        $html = str_replace('id="about-company-trigger"', '', $html);

        $response->setContent($html);
        return $response;
    }
}
