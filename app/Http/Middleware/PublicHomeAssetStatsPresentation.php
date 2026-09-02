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
            // Keep the public homepage available during migrations or temporary DB issues.
        }

        $isArabic = str_contains($html, '<html lang="ar"');
        $assetCount = number_format($managedAssets);
        $siteCount = number_format($servedSites);

        $pinIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>';
        $assetIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="m4 12 8 4.5 8-4.5M4 16.5 12 21l8-4.5"/></svg>';
        $slaIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>';
        $supportIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';

        if ($isArabic) {
            $stats = '<div class="stats home-live-stats">'
                .'<div class="stat"><span class="stat-icon">'.$pinIcon.'</span><strong>'.$siteCount.'</strong><span>المواقع المخدومة</span></div>'
                .'<div class="stat"><span class="stat-icon">'.$assetIcon.'</span><strong>'.$assetCount.'</strong><span>الأصول المدارة</span></div>'
                .'<div class="stat"><span class="stat-icon">'.$slaIcon.'</span><strong>98%</strong><span>الالتزام بالـ SLA</span></div>'
                .'<div class="stat"><span class="stat-icon">'.$supportIcon.'</span><strong>24/7</strong><span>دعم العمليات</span></div>'
                .'</div>';
        } else {
            $stats = '<div class="stats home-live-stats">'
                .'<div class="stat"><span class="stat-icon">'.$pinIcon.'</span><strong>'.$siteCount.'</strong><span>Sites Served</span></div>'
                .'<div class="stat"><span class="stat-icon">'.$assetIcon.'</span><strong>'.$assetCount.'</strong><span>Assets Managed</span></div>'
                .'<div class="stat"><span class="stat-icon">'.$slaIcon.'</span><strong>98%</strong><span>SLA Compliance</span></div>'
                .'<div class="stat"><span class="stat-icon">'.$supportIcon.'</span><strong>24/7</strong><span>Operations Support</span></div>'
                .'</div>';
        }

        // Always restore the complete four-item stats bar. Homepage CMS content may
        // otherwise shorten the array and remove Sites Served from the public page.
        $html = preg_replace(
            '/<div class="stats(?:\s+[^"]*)?">(?:\s*<div class="stat"[^>]*>.*?<\/div>\s*)+<\/div>/us',
            $stats,
            $html,
            1
        ) ?? $html;

        if (str_contains($html, '</head>')) {
            $style = <<<'HTML'
<style id="unifco-home-live-stats-icons">
.home-live-stats .stat{display:grid!important;grid-template-columns:auto auto!important;grid-template-areas:"icon value" "label label"!important;justify-content:center!important;align-items:center!important;column-gap:9px!important;row-gap:5px!important;padding:16px 12px!important}
.home-live-stats .stat-icon{grid-area:icon!important;width:27px!important;height:27px!important;border:1px solid rgba(255,255,255,.34)!important;border-radius:50%!important;display:grid!important;place-items:center!important;color:#fff!important}
.home-live-stats .stat-icon svg{width:15px!important;height:15px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.7!important;stroke-linecap:round!important;stroke-linejoin:round!important}
.home-live-stats .stat strong{grid-area:value!important;margin:0!important;font-size:27px!important;line-height:1!important}
.home-live-stats .stat>span:last-child{grid-area:label!important;display:block!important;font-size:10px!important;color:#d7e2f0!important}
@media(max-width:700px){.home-live-stats .stat{padding:14px 8px!important}.home-live-stats .stat-icon{width:24px!important;height:24px!important}.home-live-stats .stat strong{font-size:23px!important}}
</style>
HTML;
            $html = str_replace('</head>', $style."\n</head>", $html);
        }

        $response->setContent($html);

        return $response;
    }
}
