<?php

namespace App\Http\Middleware;

use App\Services\HomepageContentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeOperationsCmsMedia
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

        $maintenance = trim((string) ($home['maintenance_image'] ?? ''));
        $portal = trim((string) ($home['portal_image'] ?? ''));

        if ($maintenance === '') {
            $maintenance = '/images/home/about-technician-v14.webp';
        }
        if ($portal === '') {
            $portal = '/images/home/client-portal-v14.webp';
        }

        $safeMaintenance = htmlspecialchars($maintenance, ENT_QUOTES, 'UTF-8');
        $safePortal = htmlspecialchars($portal, ENT_QUOTES, 'UTF-8');

        $html = preg_replace(
            '/(<div class="maintenance-photo"><img src=")[^"]+("[^>]*>)/',
            '$1'.$safeMaintenance.'$2',
            $html,
            1
        ) ?? $html;

        $html = preg_replace(
            '/(<img class="portal-device" src=")[^"]+("[^>]*>)/',
            '$1'.$safePortal.'$2',
            $html,
            1
        ) ?? $html;

        $response->setContent($html);

        return $response;
    }
}
