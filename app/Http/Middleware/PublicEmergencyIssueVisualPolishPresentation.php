<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicEmergencyIssueVisualPolishPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="routine-form"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-emergency-issue-visual-polish-v1">
/* Emergency card visual rhythm */
.uf-details-pane.uf-emergency-details{
  container-type:inline-size!important;
  padding:18px 20px!important;
}
.uf-details-pane.uf-emergency-details .uf-pane-head{
  margin-bottom:14px!important;
  padding-bottom:2px!important;
}
.uf-details-pane.uf-emergency-details .uf-pane-copy b{
  font-size:19px!important;
  line-height:1.45!important;
  letter-spacing:0!important;
}
.uf-details-pane.uf-emergency-details .uf-pane-copy small{
  margin-top:3px!important;
  font-size:9.5px!important;
  line-height:1.6!important;
}
.uf-emergency-fields{
  gap:15px!important;
}
.uf-emergency-description{
  min-width:0!important;
}
.uf-emergency-description label,.uf-emergency-block>label{
  min-height:24px!important;
  margin:0 0 7px!important;
  font-size:10.8px!important;
  line-height:1.55!important;
  white-space:normal!important;
}
.uf-emergency-description textarea{
  height:104px!important;
  min-height:104px!important;
  padding:12px 13px!important;
  line-height:1.8!important;
  border-radius:9px!important;
}
.uf-emergency-grid2{
  grid-template-columns:repeat(2,minmax(0,1fr))!important;
  gap:14px 18px!important;
  align-items:start!important;
}
.uf-emergency-block{
  min-width:0!important;
}
.uf-emergency-chips{
  grid-template-columns:repeat(3,minmax(0,1fr))!important;
  gap:6px!important;
  width:100%!important;
}
.uf-emergency-chips.two{
  grid-template-columns:repeat(2,minmax(0,1fr))!important;
}
.uf-emergency-chip{
  width:100%!important;
  min-width:0!important;
  height:42px!important;
  min-height:42px!important;
  padding:0 7px!important;
  border-radius:8px!important;
  font-size:8.9px!important;
  line-height:1.25!important;
  white-space:normal!important;
  text-align:center!important;
  overflow:hidden!important;
  box-sizing:border-box!important;
  gap:5px!important;
}
.uf-emergency-chip .em-ico{
  flex:0 0 auto!important;
  font-size:12px!important;
}
.uf-emergency-chip.active{
  transform:none!important;
  box-shadow:0 2px 7px rgba(7,31,77,.04),inset 0 0 0 1px currentColor!important;
}
.uf-emergency-chip:hover{
  transform:none!important;
}
.uf-emergency-priority{
  height:42px!important;
  border-radius:8px!important;
  font-size:10.5px!important;
}
.uf-emergency-priority-note{
  margin-top:7px!important;
  padding:0 5px!important;
  line-height:1.65!important;
  font-size:8.3px!important;
}
.uf-emergency-alert{
  margin-top:1px!important;
  padding:11px 13px!important;
  border-radius:9px!important;
  line-height:1.8!important;
  font-size:8.8px!important;
}
.uf-emergency-alert b{
  font-size:10.2px!important;
  margin-bottom:2px!important;
}
.uf-details-pane.uf-emergency-details .uf-attach-title{
  margin:2px 0 8px!important;
  padding-top:2px!important;
}
.uf-details-pane.uf-emergency-details .uf-upload-row{
  min-height:54px!important;
  margin-bottom:8px!important;
  padding:8px 10px!important;
  gap:8px!important;
  border-radius:8px!important;
}
.uf-details-pane.uf-emergency-details .uf-files-summary{
  margin-top:9px!important;
  min-height:36px!important;
  display:flex!important;
  align-items:center!important;
  justify-content:center!important;
}

/* The details pane is often only ~600px wide in the two-column request layout. */
@container (max-width:680px){
  .uf-emergency-grid2{
    grid-template-columns:1fr!important;
    gap:13px!important;
  }
  .uf-emergency-block>label{
    min-height:auto!important;
  }
}
@container (max-width:430px){
  .uf-details-pane.uf-emergency-details{padding:15px!important}
  .uf-emergency-chips,.uf-emergency-chips.two{
    grid-template-columns:1fr!important;
  }
  .uf-emergency-chip{
    height:40px!important;
    min-height:40px!important;
    font-size:9.4px!important;
  }
}
</style>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $response->setContent($html);

        return $response;
    }
}
