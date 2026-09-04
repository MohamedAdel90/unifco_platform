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

    /**
     * The public project carousel must not show the same visual twice. Multiple
     * project records can legitimately carry different metadata while pointing
     * at the same uploaded/static image; users experience those as duplicates.
     */
    private function deduplicateProjects(array $projects): array
    {
        $seenImages = [];
        $seenContent = [];
        $unique = [];

        foreach ($projects as $project) {
            if (! is_array($project)) {
                continue;
            }

            $image = $this->normalizeMediaIdentity((string) ($project['image'] ?? ''));
            $content = $this->normalizeText(implode('|', [
                (string) ($project['title'] ?? ''),
                (string) ($project['owner'] ?? ''),
                (string) ($project['location'] ?? ''),
            ]));

            if ($image === '' && $content === '') {
                continue;
            }

            if (($image !== '' && isset($seenImages[$image])) || ($content !== '' && isset($seenContent[$content]))) {
                continue;
            }

            if ($image !== '') {
                $seenImages[$image] = true;
            }
            if ($content !== '') {
                $seenContent[$content] = true;
            }
            $unique[] = $project;
        }

        return $unique;
    }

    /**
     * A client is duplicated when either the same logo asset or the same client
     * name appears again. Checking both also catches copies of a logo uploaded
     * under a second URL/token.
     */
    private function deduplicateClients(array $clients): array
    {
        $seenLogos = [];
        $seenNames = [];
        $unique = [];

        foreach ($clients as $client) {
            if (! is_array($client)) {
                continue;
            }

            $logo = $this->normalizeMediaIdentity((string) ($client[0] ?? ''));
            $name = $this->normalizeText((string) ($client[1] ?? ''));

            if ($logo === '' && $name === '') {
                continue;
            }

            if (($logo !== '' && isset($seenLogos[$logo])) || ($name !== '' && isset($seenNames[$name]))) {
                continue;
            }

            if ($logo !== '') {
                $seenLogos[$logo] = true;
            }
            if ($name !== '') {
                $seenNames[$name] = true;
            }
            $unique[] = $client;
        }

        return $unique;
    }

    private function normalizeMediaIdentity(string $value): string
    {
        $value = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($value === '') {
            return '';
        }

        $path = parse_url($value, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : $value;
        $path = rawurldecode($path);
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        return mb_strtolower(rtrim($path, '/'));
    }

    private function normalizeText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($value);
    }
}
