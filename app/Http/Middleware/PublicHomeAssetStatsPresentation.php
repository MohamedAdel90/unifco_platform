<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PublicHomeAssetStatsPresentation
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

        $managedAssets = 0;
        $servedSites = 0;

        try {
            if (Schema::hasTable('assets')) {
                $managedAssets = (int) DB::table('assets')->count();

                if (Schema::hasColumn('assets', 'customer_site_id')) {
                    $servedSites = (int) DB::table('assets')
                        ->whereNotNull('customer_site_id')
                        ->distinct()
                        ->count('customer_site_id');
                }
            }
        } catch (Throwable) {
            // Keep the homepage available during migrations or temporary DB issues.
        }

        $replacements = [
            'المواقع المخدومة' => $servedSites,
            'الأصول المدارة' => $managedAssets,
            'Sites Served' => $servedSites,
            'Assets Managed' => $managedAssets,
        ];

        foreach ($replacements as $label => $value) {
            $pattern = '/(<div class="stat"[^>]*>\s*<strong>)(.*?)(<\/strong>\s*<span>\s*'.preg_quote($label, '/').'\s*<\/span>)/us';
            $formattedValue = number_format($value);

            // Use a callback so numeric values can never be interpreted as part
            // of a regex backreference (for example "$1" + "10" => "$110").
            // This changes the number only and preserves the original stat HTML/CSS.
            $html = preg_replace_callback(
                $pattern,
                static fn (array $matches): string => $matches[1].$formattedValue.$matches[3],
                $html,
                1
            ) ?? $html;
        }

        $response->setContent($html);

        return $response;
    }
}
