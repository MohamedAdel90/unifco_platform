<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Restored design state prior to the latest compact-reference experiment.
class PublicRequestCompactDesign
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->is('request-service')) return $response;
        $type = strtolower((string) $response->headers->get('Content-Type'));
        if (! str_contains($type, 'text/html')) return $response;
        $html = (string) $response->getContent();
        if ($html === '' || str_contains($html, 'id="public-request-compact-design"')) return $response;

        $style = <<<'HTML'
<style id="public-request-compact-design">
.intent-grid{display:grid!important;grid-template-columns:repeat(3,minmax(210px,285px))!important;justify-content:center!important;gap:12px!important;max-width:920px!important;margin-inline:auto!important}
.intent-grid>.pick{width:100%!important;max-width:285px!important}
.intent-grid>.pick span{position:relative;min-height:48px!important;height:48px!important;padding:6px 9px!important;display:grid!important;grid-template-columns:30px minmax(0,1fr)!important;grid-template-rows:auto auto!important;align-items:center!important;column-gap:8px!important;direction:ltr!important;border-radius:9px!important}
.intent-grid>.pick span b{grid-column:2;grid-row:1;font-size:9.6px!important;line-height:1.05!important;align-self:end;text-align:right!important;direction:rtl!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important}
.intent-grid>.pick span small{grid-column:2;grid-row:2;font-size:6px!important;line-height:1!important;align-self:start;margin-top:2px!important;text-align:right!important;direction:ltr!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;color:#8793a4!important}
.intent-service-icon{grid-column:1;grid-row:1/3;width:27px;height:27px;display:grid;place-items:center;color:#08265a;justify-self:center}
.intent-service-icon svg{display:block;width:27px;height:27px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}.intent-service-icon .accent{stroke:#df0b27!important}.intent-service-icon .accent-fill{fill:#df0b27!important;stroke:#df0b27!important}
.pick input:checked+span .intent-service-icon{color:#08265a!important}.pick input:checked+span:after{content:'✓';position:absolute;top:4px;right:5px;width:14px;height:14px;border-radius:50%;display:grid;place-items:center;background:#0b66c3;color:#fff;font-size:7px;font-weight:900;z-index:2}.pick input:checked+span{border-color:#6da4e7!important;box-shadow:0 0 0 2px rgba(11,102,195,.05)!important;background:#f8fbff!important}
.subtypes{margin-top:6px!important;gap:6px!important;width:285px!important;max-width:285px!important}.subpick span{min-height:30px!important;height:30px!important;padding:4px 7px!important;font-size:7.8px!important;border-radius:7px!important}
.asset-qr-section{max-width:920px!important;margin-inline:auto!important;padding:8px 10px!important;border-radius:10px!important;margin-bottom:12px!important}.asset-qr-heading{margin-bottom:5px!important;align-items:center!important;gap:7px!important}.asset-qr-heading h2{font-size:11px!important;margin-bottom:1px!important}.asset-qr-heading p{font-size:6.4px!important;margin:0!important;line-height:1.25!important}.asset-shield{font-size:5.5px!important;padding:3px 5px!important;border-radius:5px!important}.asset-optional{font-size:5.5px!important;padding:2px 4px!important}.asset-qr-layout{grid-template-columns:minmax(160px,.95fr) 1.05fr!important;gap:8px!important}.asset-scan-card{min-height:58px!important;height:58px!important;border-radius:8px!important;gap:0!important;padding:4px 6px!important}.asset-qr-icon{font-size:19px!important}.asset-scan-card b{font-size:7.8px!important}.asset-scan-card small{font-size:5.5px!important}.asset-manual{padding:6px 8px!important;border-radius:8px!important;gap:3px!important}.asset-manual>span{font-size:6.5px!important}.asset-manual-row{gap:5px!important}.asset-manual-row input{min-height:28px!important;height:28px!important;padding:4px 7px!important;font-size:7.2px!important;border-radius:6px!important}.asset-manual-row button{min-height:28px!important;height:28px!important;padding:0 9px!important;font-size:6.5px!important;border-radius:6px!important}.asset-lookup-message{min-height:7px!important;font-size:5.5px!important}.asset-result{margin-top:5px!important;border-radius:8px!important}.asset-result-head{padding:5px 7px!important}.asset-check{width:18px!important;height:18px!important;font-size:8px!important}.asset-result-head b{font-size:7.2px!important}.asset-result-head small{font-size:5.5px!important}.asset-result-grid>div{padding:4px 6px!important}.asset-result-grid span{font-size:5.5px!important}.asset-result-grid strong{font-size:7.2px!important;margin-top:1px!important}
.attachment-enhanced{gap:4px!important;padding:6px 8px!important;border-radius:8px!important}.attachment-enhanced>.attachment-title{font-size:8px!important;gap:5px!important}.attachment-enhanced>.attachment-title small{font-size:5.5px!important}.attachment-actions{display:flex!important;justify-content:flex-start!important;gap:4px!important}.attachment-action{min-height:25px!important;height:25px!important;width:auto!important;min-width:72px!important;padding:2px 7px!important;border-radius:5px!important;font-size:6.4px!important;gap:3px!important}.attachment-action svg{width:11px!important;height:11px!important}.attachment-action-text{display:flex!important;align-items:center!important;gap:2px!important}.attachment-action-text small{font-size:5px!important}.attachment-preview{gap:5px!important}.attachment-preview-item{min-height:52px!important;border-radius:6px!important}.attachment-preview-item img,.attachment-preview-file{height:52px!important}.attachment-preview-file{font-size:5.5px!important;padding:5px!important}.attachment-remove{width:17px!important;height:17px!important;font-size:10px!important}.attachment-count{font-size:5.5px!important}
@media(max-width:980px){.intent-grid{grid-template-columns:repeat(3,minmax(180px,1fr))!important;max-width:100%!important}.intent-grid>.pick{max-width:none!important}.subtypes{width:100%!important;max-width:none!important}.asset-qr-section{max-width:100%!important}}
@media(max-width:760px){.intent-grid{grid-template-columns:1fr!important}.intent-grid>.pick span{min-height:48px!important;height:48px!important}.asset-qr-layout{grid-template-columns:1fr!important}.asset-scan-card{min-height:54px!important;height:54px!important}.attachment-actions{display:grid!important;grid-template-columns:1fr 1fr!important}.attachment-action{width:100%!important;min-width:0!important}.attachment-action-text{display:grid!important}.pick input:checked+span:after{top:4px;right:5px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="public-request-service-icons">
(()=>{
 const icons={
  QUOTATION:'<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M8.5 20.5 24 5h13.5a5 5 0 0 1 5 5v13.5L27 39 8.5 20.5Z"/><circle class="accent" cx="34.5" cy="13" r="3.1"/><path class="accent" d="M12.5 24.5 23 35"/></svg>',
  SERVICE_REQUEST:'<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M9 39 22.5 25.5M26 22l13-13M6.5 41.5l6.5-2.2L40.5 12 36 7.5 8.7 35l-2.2 6.5Z"/><path class="accent" d="M11 8c3.8.8 6.3 3.2 6.4 6.8l17.2 17.1-6.7 6.7-17.2-17.1C7.2 21.3 4.8 18.8 4 15l5.4 1.5 3.1-3.1L11 8Z"/></svg>',
  CONSULTATION:'<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M10 27v-5C10 13.7 16.3 7 24 7s14 6.7 14 15v5"/><rect class="accent" x="6.5" y="24" width="7" height="12.5" rx="3"/><rect class="accent" x="34.5" y="24" width="7" height="12.5" rx="3"/><path d="M38 35.5c-1.8 4.5-6.1 6-11.5 6h-2"/><circle class="accent-fill" cx="22" cy="41.5" r="2"/></svg>'
 };
 document.querySelectorAll('.intent-grid > .pick').forEach(pick=>{
   const input=pick.querySelector('input[name="request_intent"]'),span=pick.querySelector(':scope > span');
   if(!input||!span)return;
   let icon=span.querySelector('.intent-service-icon');
   if(!icon){icon=document.createElement('span');icon.className='intent-service-icon';span.prepend(icon)}
   icon.innerHTML=icons[input.value]||'';
 });
})();
</script>
HTML;
        $html = str_replace('</head>', $style."\n</head>", $html);
        $html = str_replace('</body>', $script."\n</body>", $html);
        $response->setContent($html);
        return $response;
    }
}
