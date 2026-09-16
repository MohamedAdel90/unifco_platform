<?php

namespace App\Providers;

use App\Models\ServiceRequest;
use App\Observers\ServiceRequestOperationsObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        ServiceRequest::observe(ServiceRequestOperationsObserver::class);

        View::composer('public.partials.home-reference-layout', function ($view): void {
            $data = $view->getData();
            $home = $data['home'] ?? [];
            if (! is_array($home)) $home = [];
            $home['eyebrow'] = $home['eyebrow'] ?? $home['hero_eyebrow'] ?? 'ONE FACILITY SHOP';
            $home['hero_proof'] = $home['hero_proof'] ?? $home['hero_proofs'] ?? [];
            $home['services_sub'] = $home['services_sub'] ?? $home['services_text'] ?? '';
            $home['services_button'] = $home['services_button'] ?? $home['all_services'] ?? '';
            $home['industries_button'] = $home['industries_button'] ?? $home['all_industries'] ?? '';
            $home['industries_kicker'] = $home['industries_kicker'] ?? (($home['lang'] ?? 'ar') === 'ar' ? 'القطاعات' : 'Industries');

            $normaliseTriplet = static function ($item, array $keys): array {
                if (! is_array($item)) return ['', (string) $item, ''];
                if (array_key_exists(0, $item)) return [(string) ($item[0] ?? ''),(string) ($item[1] ?? ''),(string) ($item[2] ?? '')];
                return [(string) ($item[$keys[0]] ?? $item['icon'] ?? ''),(string) ($item[$keys[1]] ?? $item['title'] ?? $item['label'] ?? ''),(string) ($item[$keys[2]] ?? $item['text'] ?? $item['description'] ?? '')];
            };
            $home['about_points'] = array_values(array_map(static fn ($item): array => $normaliseTriplet($item, ['icon','title','text']), is_array($home['about_points'] ?? null) ? $home['about_points'] : []));
            $home['stats'] = array_values(array_map(static function ($item): array {
                if (! is_array($item)) return [(string) $item, ''];
                if (array_key_exists(0, $item)) return [(string) ($item[0] ?? ''),(string) ($item[1] ?? ''),(string) ($item[2] ?? '')];
                return [(string) ($item['value'] ?? $item['number'] ?? $item['metric'] ?? ''),(string) ($item['label'] ?? $item['title'] ?? $item['text'] ?? ''),(string) ($item['icon'] ?? '')];
            }, is_array($home['stats'] ?? null) ? $home['stats'] : []));
            foreach (['hero_proof','capabilities','process','why'] as $key) $home[$key] = array_values(array_map(static fn ($item): array => $normaliseTriplet($item, ['icon','title','text']), is_array($home[$key] ?? null) ? $home[$key] : []));
            $home['services'] = array_values(array_map(static function ($service): array {
                if (! is_array($service)) return [(string) $service,'','#',''];
                if (! array_key_exists(0,$service)) return [(string) ($service['title'] ?? $service['name'] ?? ''),(string) ($service['text'] ?? $service['description'] ?? ''),(string) ($service['url'] ?? $service['href'] ?? '#'),(string) ($service['image'] ?? '')];
                if (isset($service[1]) && is_string($service[1]) && str_starts_with($service[1], '/')) return [(string) ($service[2] ?? $service[0] ?? ''),(string) ($service[3] ?? ''),route('public.services'),(string) ($service[1] ?? '')];
                return [(string) ($service[0] ?? ''),(string) ($service[1] ?? ''),(string) ($service[2] ?? '#'),(string) ($service[3] ?? '')];
            }, is_array($home['services'] ?? null) ? $home['services'] : []));
            $home['industries'] = array_values(array_map(static function ($industry): array {
                if (! is_array($industry)) return [(string) $industry,''];
                if (! array_key_exists(0,$industry)) return [(string) ($industry['title'] ?? $industry['name'] ?? ''),(string) ($industry['image'] ?? '')];
                if (isset($industry[0]) && is_string($industry[0]) && str_starts_with($industry[0], '/')) return [(string) ($industry[1] ?? ''),(string) $industry[0]];
                return [(string) ($industry[0] ?? ''),(string) ($industry[1] ?? '')];
            }, is_array($home['industries'] ?? null) ? $home['industries'] : []));
            $view->with('home',$home);
        });
    }
}
