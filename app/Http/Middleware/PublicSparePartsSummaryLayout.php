<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicSparePartsSummaryLayout
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</head>')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-spare-summary-left-layout">
/* Keep the approved request summary on the physical LEFT in both RTL and LTR. */
@media (min-width:1051px){
  .shell{
    direction:ltr !important;
    grid-template-columns:270px minmax(0,1fr) !important;
    grid-template-areas:"summary main" !important;
  }
  .shell>.summary{
    grid-area:summary !important;
    grid-column:1 !important;
    grid-row:1 !important;
    width:270px !important;
    align-self:start !important;
    justify-self:stretch !important;
    position:sticky !important;
    top:14px !important;
  }
  .shell>.main{
    grid-area:main !important;
    grid-column:2 !important;
    grid-row:1 !important;
    min-width:0 !important;
  }
  html[dir="rtl"] .shell>.summary,
  html[dir="rtl"] .shell>.main{direction:rtl !important;}
  html[dir="ltr"] .shell>.summary,
  html[dir="ltr"] .shell>.main{direction:ltr !important;}
}
@media (max-width:1050px){
  .shell{display:grid !important;grid-template-columns:1fr !important;direction:inherit !important;}
  .shell>.main{grid-column:1 !important;grid-row:1 !important;}
  .shell>.summary{grid-column:1 !important;grid-row:2 !important;width:auto !important;position:static !important;}
}
</style>
HTML;

        $response->setContent(str_replace('</head>', $style.'</head>', $html));

        return $response;
    }
}
