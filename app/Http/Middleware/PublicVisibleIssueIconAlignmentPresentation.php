<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicVisibleIssueIconAlignmentPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'unifco-unified-asset-issue-workspace-v1') || str_contains($html, 'unifco-visible-issue-icon-alignment-v1')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-visible-issue-icon-alignment-v1">
/* Scope intentionally limited to the visible issue-details pane. */
.uf-details-pane .uf-title-icon,
.uf-details-pane .uf-label-icon{
    position:static!important;
    inset:auto!important;
    top:auto!important;
    right:auto!important;
    bottom:auto!important;
    left:auto!important;
    transform:none!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    flex:0 0 auto!important;
    margin:0!important;
    pointer-events:none!important;
}
.uf-details-pane .uf-detail-field>label,
.uf-details-pane .uf-pane-copy b{
    display:flex!important;
    align-items:center!important;
    gap:7px!important;
}
</style>
HTML;

        $html = str_replace('</body>', $style.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
