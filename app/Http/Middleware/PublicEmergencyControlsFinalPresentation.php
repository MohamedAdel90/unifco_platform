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
<style id="unifco-emergency-controls-final-v3">
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
/* Approved compact semantic icons: icon shape only, no layout/content changes. */
.uf-details-pane.uf-emergency-details .em-ico{
  width:17px!important;
  height:17px!important;
  flex:0 0 17px!important;
  display:inline-grid!important;
  place-items:center!important;
  line-height:0!important;
  font-size:0!important;
}
.uf-details-pane.uf-emergency-details .em-ico svg,
.uf-details-pane.uf-emergency-details .em-alert-icon svg,
.uf-details-pane.uf-emergency-details .uf-emergency-priority .em-priority-icon svg{
  width:100%!important;
  height:100%!important;
  display:block!important;
}
.uf-details-pane.uf-emergency-details .em-alert-icon{
  width:18px!important;
  height:18px!important;
  flex:0 0 18px!important;
  display:inline-grid!important;
  place-items:center!important;
  line-height:0!important;
  font-size:0!important;
}
.uf-details-pane.uf-emergency-details .uf-emergency-priority .em-priority-icon{
  width:18px!important;
  height:18px!important;
  display:inline-grid!important;
  place-items:center!important;
  line-height:0!important;
}
@container (max-width:380px){
  .uf-details-pane.uf-emergency-details .uf-emergency-grid2{
    grid-template-columns:1fr!important;
    gap:13px!important;
  }
}
/* Phones need each question to own the full card width. The previous
   container-only breakpoint was bypassed when the pane inherited a wide
   layout container, leaving three controls squeezed into half a phone. */
@media (max-width:640px){
  .uf-details-pane.uf-emergency-details{
    padding:14px 12px!important;
  }
  .uf-details-pane.uf-emergency-details .uf-emergency-grid2{
    grid-template-columns:minmax(0,1fr)!important;
    gap:14px!important;
  }
  .uf-details-pane.uf-emergency-details .uf-emergency-block>label{
    min-height:0!important;
  }
  .uf-details-pane.uf-emergency-details .uf-emergency-chip{
    padding-inline:5px!important;
    font-size:8.5px!important;
    gap:4px!important;
    overflow:visible!important;
  }
  .uf-details-pane.uf-emergency-details .uf-emergency-priority{
    width:100%!important;
  }
}
@media (max-width:360px){
  .uf-details-pane.uf-emergency-details .uf-emergency-block:has([data-em-group="state"]) .uf-emergency-chips,
  .uf-details-pane.uf-emergency-details .uf-emergency-block:has([data-em-group="started"]) .uf-emergency-chips{
    grid-template-columns:1fr!important;
  }
}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-emergency-controls-final-script-v3">
(()=>{
  const icons={
    'state:RUNNING':'<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 5.3v13.4c0 .9 1 1.5 1.8 1l10.1-6.7a1.2 1.2 0 0 0 0-2L8.8 4.3A1.2 1.2 0 0 0 7 5.3Z"/></svg>',
    'state:PARTIAL':'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="4" height="16" rx="1.2" fill="currentColor"/><rect x="14" y="4" width="4" height="16" rx="1.2" fill="currentColor"/></svg>',
    'state:STOPPED':'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="5" width="14" height="14" rx="2" fill="currentColor"/></svg>',
    'impact:PARTIAL_SITE':'<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11 3.6 2.9 18a1.5 1.5 0 0 0 1.3 2.2h15.6a1.5 1.5 0 0 0 1.3-2.2L13 3.6a1.2 1.2 0 0 0-2 0Z"/><rect x="11" y="8" width="2" height="6.5" rx="1" fill="#fff"/><circle cx="12" cy="17.2" r="1.2" fill="#fff"/></svg>',
    'impact:SITE_DOWN':'<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11 3.6 2.9 18a1.5 1.5 0 0 0 1.3 2.2h15.6a1.5 1.5 0 0 0 1.3-2.2L13 3.6a1.2 1.2 0 0 0-2 0Z"/><rect x="11" y="8" width="2" height="6.5" rx="1" fill="#fff"/><circle cx="12" cy="17.2" r="1.2" fill="#fff"/></svg>',
    'safety:NO':'<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.8 20 6v5.9c0 4.8-3.1 8.2-8 9.8-4.9-1.6-8-5-8-9.8V6l8-3.2Z"/><path d="m8.2 12.1 2.4 2.4 5.2-5.3" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'safety:YES':'<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.8 20 6v5.9c0 4.8-3.1 8.2-8 9.8-4.9-1.6-8-5-8-9.8V6l8-3.2Z"/><rect x="11" y="7.5" width="2" height="6.7" rx="1" fill="#fff"/><circle cx="12" cy="17.1" r="1.15" fill="#fff"/></svg>',
    'site-status:FULL':'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="m7.8 12.2 2.6 2.7 5.8-6" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'site-status:DOWN':'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M8 12h8" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>',
    'started:الآن':'<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.4 2.8 6.5 13h4.8l-.8 8.2L17.5 11h-4.8l.7-8.2Z"/></svg>',
    'started:عدة ساعات':'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7.2V12l3.4 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'started:عدة أيام':'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5.5" width="16" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 3.8v3.6M16 3.8v3.6M4 9.5h16M8 13h2M12 13h2M16 13h1M8 16h2M12 16h2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>'
  };
  const alertIcon='<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11 3.6 2.9 18a1.5 1.5 0 0 0 1.3 2.2h15.6a1.5 1.5 0 0 0 1.3-2.2L13 3.6a1.2 1.2 0 0 0-2 0Z"/><rect x="11" y="8" width="2" height="6.5" rx="1" fill="#fff"/><circle cx="12" cy="17.2" r="1.2" fill="#fff"/></svg>';
  const priorityIcon='<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 4h6l1.2 2.2c.6 1.1 1 2.3 1 3.6v4.7h1.2a1.6 1.6 0 0 1 1.6 1.6V19H4v-2.9a1.6 1.6 0 0 1 1.6-1.6h1.2V9.8c0-1.3.4-2.5 1-3.6L9 4Z"/><path d="M8.5 10.1h7M7 21h10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 7.5 2.8 6M19 7.5 21.2 6M4.3 11H2M19.7 11H22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';

  const applyIcons=fields=>{
    fields.querySelectorAll('[data-em-group]').forEach(btn=>{
      const key=`${btn.dataset.emGroup||''}:${btn.dataset.value||''}`;
      const ico=btn.querySelector('.em-ico');
      if(ico&&icons[key]&&ico.dataset.semanticIcon!==key){
        ico.innerHTML=icons[key];
        ico.dataset.semanticIcon=key;
      }
    });
    const alert=fields.querySelector('.em-alert-icon');
    if(alert&&!alert.dataset.semanticIcon){alert.innerHTML=alertIcon;alert.dataset.semanticIcon='alert';}
    const priority=fields.querySelector('.uf-emergency-priority');
    if(priority&&!priority.querySelector('.em-priority-icon')){
      const first=priority.querySelector('span');
      if(first){first.className='em-priority-icon';first.innerHTML=priorityIcon;}
    }
  };

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
    applyIcons(fields);
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
