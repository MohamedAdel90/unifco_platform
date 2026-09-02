<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as IlluminateView;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeRenderCompatibility
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('public.home')) {
            View::composer('public.partials.home-reference-layout', function (IlluminateView $view): void {
                $home = (array) ($view->getData()['home'] ?? []);

                $home['eyebrow'] = $home['eyebrow'] ?? $home['hero_eyebrow'] ?? '';
                $home['hero_proof'] = $home['hero_proof'] ?? $home['hero_proofs'] ?? [];

                $view->with('home', $home);
            });
        }

        return $next($request);
    }
}
