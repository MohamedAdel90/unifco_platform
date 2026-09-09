<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHtmlResponseContentTypeGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $response;
        }

        if (! method_exists($response, 'getContent')) {
            return $response;
        }

        $content = (string) $response->getContent();
        if ($content === '') {
            return $response;
        }

        $trimmed = ltrim($content);
        $isHtml = str_starts_with(strtolower($trimmed), '<!doctype html')
            || str_starts_with(strtolower($trimmed), '<html')
            || str_contains(strtolower(substr($trimmed, 0, 500)), '<html');

        if (! $isHtml) {
            return $response;
        }

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        $disposition = (string) $response->headers->get('Content-Disposition', '');
        if (stripos($disposition, 'attachment') !== false) {
            $response->headers->remove('Content-Disposition');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
