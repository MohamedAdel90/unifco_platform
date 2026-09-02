<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeFinalCopyPresentation
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

        $html = str_replace(
            'دع UNIFCO تتولى التشغيل والصيانة بينما تركز أنت على أعمالك.',
            'دع UNIFCO تتولى التشغيل والصيانة <strong class="final-copy-emphasis">وتفرغ لتطوير أعمالك.</strong>',
            $html
        );

        if (str_contains($html, '</head>')) {
            $style = <<<'HTML'
<style id="unifco-final-copy-emphasis">
.final-copy-emphasis{font-weight:900;color:#fff}
</style>
HTML;
            $html = str_replace('</head>', $style."\n</head>", $html);
        }

        $response->setContent($html);
        return $response;
    }
}
