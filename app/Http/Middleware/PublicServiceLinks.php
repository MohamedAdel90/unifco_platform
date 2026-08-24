<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicServiceLinks
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
        $map = [
            'Transformers · المحولات الكهربائية' => 'transformer-maintenance',
            'Transformers' => 'transformer-maintenance',
            'UPS Systems' => 'ups-systems',
            'Generators · المولدات' => 'generators',
            'Generators' => 'generators',
            'MV Systems · أنظمة الجهد المتوسط' => 'mv-systems',
            'MV Systems' => 'mv-systems',
            'Preventive Maintenance' => 'preventive-maintenance',
            'Corrective / Emergency Maintenance' => 'corrective-emergency-maintenance',
            'Inspection & Testing' => 'inspection-testing',
            'Maintenance Contracts' => 'maintenance-contracts',
            'Industrial / Commercial Electrical Services' => 'industrial-commercial-electrical',
            'Asset Management' => 'asset-management',
            'HVAC Systems' => 'hvac-systems',
            'MEP Services' => 'mep-services',
            'Facility Management' => 'facility-management',
        ];

        $html = preg_replace_callback('/<article class="service-card">.*?<\/article>/s', function (array $match) use ($map, $locale): string {
            $card = $match[0];
            if (! preg_match('/<div class="service-body"><b>(.*?)<\/b>/s', $card, $titleMatch)) {
                return $card;
            }

            $title = trim(strip_tags($titleMatch[1]));
            $slug = $map[$title] ?? null;
            if (! $slug) {
                return $card;
            }

            if ($slug === 'generators') {
                $card = preg_replace(
                    '/(<div class="service-image"><img src=")[^"]+(" alt=")/',
                    '$1/images/home/generator-maintenance-card.svg$2',
                    $card,
                    1
                ) ?? $card;
            }

            $url = route('public.services.show', ['slug' => $slug, 'lang' => $locale]);
            return preg_replace('/<a class="more" href="[^"]*">/', '<a class="more" href="'.e($url).'">', $card, 1) ?? $card;
        }, $html) ?? $html;

        $response->setContent($html);
        return $response;
    }
}
