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

                $home['eyebrow'] = $home['eyebrow'] ?? $home['hero_eyebrow'] ?? '';
                $home['hero_proof'] = $home['hero_proof'] ?? $home['hero_proofs'] ?? [];
                $home['showcase_kicker'] = $home['showcase_kicker'] ?? $home['projects_kicker'] ?? '';
                $home['showcase_title'] = $home['showcase_title'] ?? $home['projects_title'] ?? '';
                $home['showcase_text'] = $home['showcase_text'] ?? '';
                $home['showcase_metrics'] = $home['showcase_metrics'] ?? [];
                $home['showcase_projects'] = $this->deduplicateProjects((array) ($home['showcase_projects'] ?? []));
                $home['showcase_clients'] = $this->deduplicateClients((array) ($home['showcase_clients'] ?? []));

                // HomepageContentService normally converts current CMS service rows
                // into the approved public tuple [title, description, href, image].
                // Keep this compatibility guard for older cached/current rows only.
                if (isset($home['services']) && is_array($home['services'])) {
                    $home['services'] = array_map(function ($service) {
                        if (! is_array($service)) {
                            return $service;
                        }

                        $isCmsTuple = isset($service[0])
                            && preg_match('/^\d{1,2}$/', (string) $service[0]) === 1;

                        if (! $isCmsTuple) {
                            return $service;
                        }

                        return [
                            (string) ($service[2] ?? ''),
                            (string) ($service[3] ?? ''),
                            route('public.services'),
                            (string) ($service[1] ?? ''),
                        ];
                    }, $home['services']);
                }

                $isArabic = ($home['lang'] ?? 'ar') === 'ar';
                $home['clients_title'] = $home['clients_title'] ?? ($isArabic ? 'عملاؤنا' : 'Our Clients');
                $home['clients_text'] = $home['clients_text'] ?? '';
                $home['more_clients'] = $home['more_clients'] ?? '';
                $home['all_clients'] = $home['all_clients'] ?? ($isArabic ? 'عرض جميع العملاء' : 'View all clients');
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

    private function deduplicateProjects(array $projects): array
    {
        $seen = [];
        $unique = [];

        foreach ($projects as $project) {
            if (! is_array($project)) {
                continue;
            }

            $key = implode('|', [
                trim((string) ($project['image'] ?? '')),
                trim((string) ($project['title'] ?? '')),
                trim((string) ($project['owner'] ?? '')),
                trim((string) ($project['location'] ?? '')),
            ]);

            if ($key === '|||') {
                continue;
            }
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $project;
        }

        return $unique;
    }

    private function deduplicateClients(array $clients): array
    {
        $seen = [];
        $unique = [];

        foreach ($clients as $client) {
            if (! is_array($client)) {
                continue;
            }

            $logo = trim((string) ($client[0] ?? ''));
            $name = trim((string) ($client[1] ?? ''));
            $key = $logo !== '' ? $logo : $name;

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $client;
        }

        return $unique;
    }
}
