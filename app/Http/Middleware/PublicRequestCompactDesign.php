<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
/* Compact approved service-request composition */
.intent-grid{gap:14px!important;max-width:100%!important}
.intent-grid>.pick span{min-height:66px!important;padding:10px 15px!important;display:grid!important;grid-template-columns:1fr 46px!important;grid-template-rows:auto auto!important;align-items:center!important;column-gap:12px!important}
.intent-grid>.pick span b{font-size:11.5px!important;align-self:end}.intent-grid>.pick span small{font-size:7.5px!important;align-self:start;margin-top:1px!important}
.intent-service-icon{grid-column:2;grid-row:1/3;width:42px;height:42px;display:grid;place-items:center;color:#08265a}
.intent-service-icon svg{width:38px;height:38px;fill:none;stroke:currentColor;stroke-width:1.65;stroke-linecap:round;stroke-linejoin:round}.intent-service-icon .accent{stroke:#df0b27}
.pick input:checked+span .intent-service-icon{color:#0b5eb8}.pick input:checked+span:after{content:'✓';position:absolute;top:7px;inset-inline-start:7px;width:18px;height:18px;border-radius:50%;display:grid;place-items:center;background:#0b5eb8;color:#fff;font-size:10px;font-weight:900}
.asset-qr-section{padding:14px 16px!important;border-radius:13px!important;margin-bottom:16px!important}.asset-qr-heading{margin-bottom:9px!important;align-items:center!important}.asset-qr-heading h2{font-size:14px!important;margin-bottom:3px!important}.asset-qr-heading p{font-size:8px!important;margin:0!important}.asset-shield{font-size:7px!important;padding:5px 7px!important}.asset-optional{font-size:7px!important;padding:3px 6px!important}.asset-qr-layout{grid-template-columns:minmax(190px,.9fr) 1.1fr!important;gap:12px!important}.asset-scan-card{min-height:92px!important;border-radius:11px!important;gap:2px!important}.asset-qr-icon{font-size:29px!important}.asset-scan-card b{font-size:10px!important}.asset-scan-card small{font-size:7px!important}.asset-manual{padding:11px 13px!important;border-radius:11px!important;gap:6px!important}.asset-manual>span{font-size:8px!important}.asset-manual-row input{min-height:38px!important;padding:8px 10px!important;font-size:9px!important}.asset-manual-row button{min-height:38px!important;padding:0 14px!important;font-size:8px!important}.asset-lookup-message{min-height:12px!important;font-size:7px!important}.asset-result{margin-top:9px!important}.asset-result-head{padding:8px 11px!important}.asset-result-grid>div{padding:7px 10px!important}
.attachment-enhanced{gap:7px!important;padding:9px 11px!important;border-radius:10px!important}.attachment-enhanced>.attachment-title{font-size:9.5px!important}.attachment-enhanced>.attachment-title small{font-size:7px!important}.attachment-actions{display:flex!important;justify-content:flex-start!important;gap:7px!important}.attachment-action{min-height:34px!important;width:auto!important;min-width:104px!important;padding:5px 10px!important;border-radius:7px!important;font-size:8px!important;gap:6px!important}.attachment-action svg{width:15px!important;height:15px!important}.attachment-action-text{display:flex!important;align-items:center!important;gap:4px!important}.attachment-action-text small{font-size:6.5px!important}.attachment-preview-item{min-height:70px!important}.attachment-preview-item img,.attachment-preview-file{height:72px!important}
@media(max-width:760px){.intent-grid{grid-template-columns:1fr!important}.intent-grid>.pick span{min-height:62px!important}.asset-qr-layout{grid-template-columns:1fr!important}.asset-scan-card{min-height:82px!important}.attachment-actions{display:grid!important;grid-template-columns:1fr 1fr!important}.attachment-action{width:100%!important;min-width:0!important}.attachment-action-text{display:grid!important}.pick input:checked+span:after{top:6px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="public-request-service-icons">
(()=>{
 const icons=[
  '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M11 10h20l7 7-20 20-9-9 20-20"/><circle class="accent" cx="31" cy="16" r="2.8"/><path class="accent" d="m11 30 7 7"/></svg>',
  '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="m10 37 12-12m4-4 11-11M7 41l6-2 24-24-4-4L9 35l-2 6Z"/><path class="accent" d="M12 9c3 1 5 3 5 6l17 17-6 6-17-17c-3 0-5-2-6-5l5 1 3-3-1-5Z"/></svg>',
  '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M10 27v-5c0-8 6-14 14-14s14 6 14 14v5"/><rect class="accent" x="7" y="24" width="7" height="12" rx="3"/><rect class="accent" x="34" y="24" width="7" height="12" rx="3"/><path d="M38 35c-2 5-6 6-11 6h-3"/><circle cx="22" cy="41" r="2"/></svg>'
 ];
 const picks=[...document.querySelectorAll('.intent-grid > .pick')].slice(0,3);
 picks.forEach((pick,i)=>{const span=pick.querySelector('span');if(!span||span.querySelector('.intent-service-icon'))return;const box=document.createElement('span');box.className='intent-service-icon';box.innerHTML=icons[i];span.appendChild(box)});
})();
</script>
HTML;
        $html = str_replace('</head>', $style."\n</head>", $html);
        $html = str_replace('</body>', $script."\n</body>", $html);
        $response->setContent($html);
        return $response;
    }
}
