<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeSpacingPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</head>')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-home-spacing-system">
/* Compact vertical rhythm for the public homepage without changing component geometry. */
.section{padding-top:42px!important;padding-bottom:42px!important}
.section-head{margin-bottom:20px!important}
.process-wrap{padding-top:36px!important;padding-bottom:36px!important}
.operations{padding-top:28px!important;padding-bottom:48px!important}

/* Dynamic showcase sections added by the public presentation layer. */
.unifco-showcase{padding-top:42px!important;padding-bottom:44px!important}
.unifco-clients{padding-top:30px!important;padding-bottom:30px!important}
.unifco-emergency{padding-top:14px!important;padding-bottom:28px!important}
.public-projects-section{padding-top:42px!important;padding-bottom:42px!important}

/* Keep headings compact while preserving readable hierarchy. */
.unifco-showcase-head{margin-bottom:22px!important}
.public-showcase-head{margin-bottom:20px!important}
.center-action{margin-top:12px!important}
.stats{margin-top:24px!important}
.about-points{margin-top:16px!important;margin-bottom:15px!important}

/* Preserve intentional breathing room around hero and final conversion area. */
.hero{margin:0!important}
.final-cta{margin-top:0!important}

@media(max-width:980px){
  .section{padding-top:36px!important;padding-bottom:36px!important}
  .process-wrap{padding-top:32px!important;padding-bottom:32px!important}
  .operations{padding-top:24px!important;padding-bottom:40px!important}
  .unifco-showcase{padding-top:36px!important;padding-bottom:38px!important}
}
@media(max-width:700px){
  .section{padding-top:30px!important;padding-bottom:30px!important}
  .section-head{margin-bottom:16px!important}
  .process-wrap{padding-top:28px!important;padding-bottom:28px!important}
  .operations{padding-top:20px!important;padding-bottom:34px!important}
  .unifco-showcase{padding-top:30px!important;padding-bottom:32px!important}
  .unifco-clients{padding-top:24px!important;padding-bottom:24px!important}
  .unifco-emergency{padding-top:10px!important;padding-bottom:22px!important}
}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style."\n</head>", $html));
        return $response;
    }
}
