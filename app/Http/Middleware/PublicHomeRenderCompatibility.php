<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as IlluminateView;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeRenderCompatibility
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('public.home')) {
            View::composer('public.partials.home-reference-layout', function (IlluminateView $view): void {
                $home = (array) ($view->getData()['home'] ?? []);

                // Keep the public homepage render-safe across qualified releases that
                // may still use the previous key names while the presentation layer
                // already expects the current schema.
                $home['eyebrow'] = $home['eyebrow'] ?? $home['hero_eyebrow'] ?? '';
                $home['hero_proof'] = $home['hero_proof'] ?? $home['hero_proofs'] ?? [];
                $home['showcase_kicker'] = $home['showcase_kicker'] ?? $home['projects_kicker'] ?? '';
                $home['showcase_title'] = $home['showcase_title'] ?? $home['projects_title'] ?? '';
                $home['showcase_text'] = $home['showcase_text'] ?? '';
                $home['showcase_metrics'] = $home['showcase_metrics'] ?? [];
                $home['showcase_projects'] = $home['showcase_projects'] ?? [];
                $home['showcase_clients'] = $home['showcase_clients'] ?? [];
                $home['clients_title'] = $home['clients_title'] ?? ($home['lang'] ?? 'ar') === 'ar' ? 'عملاؤنا' : 'Our Clients';
                $home['clients_text'] = $home['clients_text'] ?? '';
                $home['more_clients'] = $home['more_clients'] ?? '';
                $home['all_clients'] = $home['all_clients'] ?? (($home['lang'] ?? 'ar') === 'ar' ? 'عرض جميع العملاء' : 'View all clients');
                $home['carousel_previous'] = $home['carousel_previous'] ?? 'Previous';
                $home['carousel_next'] = $home['carousel_next'] ?? 'Next';
                $home['emergency_photo_alt'] = $home['emergency_photo_alt'] ?? '';
                $home['operations_support'] = $home['operations_support'] ?? '24/7';
                $home['contact_now'] = $home['contact_now'] ?? '';
                $home['email_us'] = $home['email_us'] ?? '';

                $view->with('home', $home);
            });
        }

        return $next($request);
    }
}
