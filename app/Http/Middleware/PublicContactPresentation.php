<?php

namespace App\Http\Middleware;

use App\Support\UnifcoContact;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicContactPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->user() || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $route = $request->route()?->getName();
        if (! in_array($route, [
            'public.home',
            'public.quote',
            'public.request-service',
            'public.emergency',
            'public.request.received',
            'public.service.detail',
        ], true)) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || str_contains($html, 'data-unifco-public-contact')) {
            return $response;
        }

        $contact = '<style data-unifco-public-contact>'
            .'.unifco-contact-fab{position:fixed;right:18px;bottom:18px;z-index:9998;display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}'
            .'.unifco-contact-fab a{display:inline-flex;align-items:center;gap:7px;padding:10px 13px;border-radius:999px;text-decoration:none;font:700 12px Inter,Arial,sans-serif;box-shadow:0 8px 24px rgba(3,31,76,.18)}'
            .'.unifco-contact-fab .wa{background:#147a48;color:#fff}.unifco-contact-fab .mail{background:#06275c;color:#fff}'
            .'@media(max-width:600px){.unifco-contact-fab{left:12px;right:12px;bottom:12px}.unifco-contact-fab a{flex:1;justify-content:center;min-width:0;font-size:11px}}'
            .'</style>'
            .'<div class="unifco-contact-fab" data-unifco-public-contact>'
            .'<a class="wa" href="'.e(UnifcoContact::whatsappUrl('Hello UNIFCO, I would like to inquire about your services.')).'" target="_blank" rel="noopener">WhatsApp '.e(UnifcoContact::WHATSAPP_DISPLAY).'</a>'
            .'<a class="mail" href="'.e(UnifcoContact::mailto()).'">'.e(UnifcoContact::EMAIL).'</a>'
            .'</div>';

        $pos = strripos($html, '</body>');
        $html = $pos === false ? $html.$contact : substr($html, 0, $pos).$contact.substr($html, $pos);
        $response->setContent($html);
        return $response;
    }
}
