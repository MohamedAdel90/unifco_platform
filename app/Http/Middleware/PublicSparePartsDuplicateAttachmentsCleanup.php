<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicSparePartsDuplicateAttachmentsCleanup
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, '<html')) {
            return $response;
        }

        $styles = <<<'HTML'
<style id="unifco-spare-parts-duplicate-attachments-cleanup">
body.uf-new-spare-parts-mode .uf-attach-title,
body.uf-new-spare-parts-mode #uf-upload-rows{
    display:none!important;
}
</style>
HTML;

        $html = str_replace('</head>', $styles.'</head>', $html);
        $response->setContent($html);

        return $response;
    }
}
