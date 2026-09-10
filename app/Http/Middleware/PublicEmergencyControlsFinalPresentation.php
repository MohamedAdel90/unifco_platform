<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicEmergencyControlsFinalPresentation
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
<style id="unifco-emergency-controls-final-v2">
/* Final emergency controls layout. Keep the agreed two-column layout until true phone widths. */
.uf-details-pane.uf-emergency-details .uf-emergency-grid2{
  grid-template-columns:repeat(2,minmax(0,1fr))!important;
  column-gap:18px!important;
  row-gap:14px!important;
  align-items:start!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-block{
  min-width:0!important;
  width:100%!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-block>label{
  width:100%!important;
  box-sizing:border-box!important;
  min-height:24px!important;
  margin:0 0 7px!important;
  justify-content:flex-start!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-chips{
  width:100%!important;
  display:grid!important;
  gap:7px!important;
  align-items:stretch!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-block:has([data-em-group="state"]) .uf-emergency-chips{
  grid-template-columns:repeat(3,minmax(0,1fr))!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-block:has([data-em-group="impact"]) .uf-emergency-chips{
  grid-template-columns:repeat(2,minmax(0,1fr))!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-block:has([data-em-group="safety"]) .uf-emergency-chips,
.uf-details-pane.uf-emergency-details .uf-emergency-block:has([data-em-group="site-status"]) .uf-emergency-chips{
  grid-template-columns:repeat(2,minmax(0,1fr))!important;
  width:100%!important;
}
.uf-details-pane.uf-emergency-details [data-em-group="safety"],
.uf-details-pane.uf-emergency-details [data-em-group="site-status"]{
  height:31px!important;
  min-height:31px!important;
  font-size:8.6px!important;
  border-radius:7px!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-block:has([data-em-group="started"]) .uf-emergency-chips{
  grid-template-columns:repeat(3,minmax(0,1fr))!important;
}
.uf-details-pane.uf-emergency-details [data-em-group="started"]{
  height:38px!important;
  min-height:38px!important;
  font-size:8.8px!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-block:has(.uf-emergency-priority)>label{
  width:100%!important;
  text-align:right!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-priority{
  width:50%!important;
  height:34px!important;
  min-height:34px!important;
  margin-inline:auto!important;
  border-radius:7px!important;
  font-size:9.5px!important;
  box-sizing:border-box!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-priority-note{
  width:100%!important;
  max-width:100%!important;
  margin:7px auto 0!important;
  text-align:center!important;
  line-height:1.65!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-chip.em-green.active{
  background:#ccefd9!important;
  border-color:#159653!important;
  color:#087738!important;
  box-shadow:inset 0 0 0 1px #159653!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-chip.em-amber.active{
  background:#ffe3a8!important;
  border-color:#d58b00!important;
  color:#995f00!important;
  box-shadow:inset 0 0 0 1px #d58b00!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-chip.em-red.active{
  background:#ffd2d8!important;
  border-color:#d51f36!important;
  color:#b61429!important;
  box-shadow:inset 0 0 0 1px #d51f36!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-chip.em-blue.active{
  background:#d6eaff!important;
  border-color:#176dca!important;
  color:#0b55ad!important;
  box-shadow:inset 0 0 0 1px #176dca!important;
}
@container (max-width:380px){
  .uf-details-pane.uf-emergency-details .uf-emergency-grid2{
    grid-template-columns:1fr!important;
    gap:13px!important;
  }
}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-emergency-controls-final-script-v2">
(()=>{
  const apply=()=>{
    const fields=document.getElementById('uf-emergency-fields');
    if(!fields)return false;

    fields.querySelector('[data-em-group="impact"][data-value="LIMITED"]')?.remove();

    const relabel={
      'منذ عدة ساعات':'عدة ساعات',
      'منذ عدة أيام':'عدة أيام'
    };
    fields.querySelectorAll('[data-em-group="started"]').forEach(btn=>{
      const old=btn.dataset.value||'';
      if(relabel[old]){
        btn.dataset.value=relabel[old];
        btn.childNodes.forEach(n=>{if(n.nodeType===3)n.textContent='';});
        btn.appendChild(document.createTextNode(' '+relabel[old]));
      }
    });
    return true;
  };

  if(!apply()){
    let tries=0;
    const timer=setInterval(()=>{if(apply()||++tries>60)clearInterval(timer)},50);
  }
  const observer=new MutationObserver(()=>apply());
  const start=()=>{
    const pane=document.querySelector('.uf-details-pane');
    if(!pane)return false;
    observer.observe(pane,{childList:true,subtree:true});
    apply();
    return true;
  };
  if(!start())setTimeout(start,100);
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
