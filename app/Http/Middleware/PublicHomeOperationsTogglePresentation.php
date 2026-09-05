<?php

namespace App\Http\Middleware;

use App\Models\HomepageSection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeOperationsTogglePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $section = HomepageSection::query()->where('section_key', 'operations')->first();
        if (! $section) {
            return $response;
        }

        $data = $section->data_ar ?? [];
        $maintenanceEnabled = (bool) ($data['maintenance_enabled'] ?? true);
        $portalEnabled = (bool) ($data['portal_enabled'] ?? true);

        if ($maintenanceEnabled && $portalEnabled) {
            return $response;
        }

        $css = '<style id="unifco-operation-toggles">';
        if (! $maintenanceEnabled) {
            $css .= '.maintenance-card{display:none!important;}';
        }
        if (! $portalEnabled) {
            $css .= '.portal-card{display:none!important;}';
        }
        $css .= '</style>';

        $html = (string) $response->getContent();
        if ($html !== '' && str_contains($html, '</head>')) {
            $response->setContent(str_replace('</head>', $css.'</head>', $html));
        }

        return $response;
    }
}
