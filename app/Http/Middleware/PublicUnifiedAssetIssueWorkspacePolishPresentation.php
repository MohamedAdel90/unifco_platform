<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicUnifiedAssetIssueWorkspacePolishPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'unifco-unified-asset-issue-workspace-v1')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-unified-asset-issue-workspace-polish-v6">
/* Steps 4/5 workspace: balanced panels, functional controls and attachment previews */
.uf-workspace{display:grid!important;grid-template-columns:minmax(0,3fr) minmax(360px,2fr)!important;grid-template-areas:"asset details"!important;gap:16px!important;align-items:stretch!important;margin:0 0 14px!important;direction:rtl!important}
.uf-workspace>.panel{height:100%!important;min-height:0!important;align-self:stretch!important;margin:0!important;padding:18px 20px!important;border:1px solid #dfe7f1!important;border-radius:14px!important;box-shadow:0 8px 24px rgba(7,31,77,.045)!important;background:#fff!important}
.uf-asset-pane{grid-area:asset!important}.uf-details-pane{grid-area:details!important}
.uf-pane-head{display:flex!important;align-items:flex-start!important;gap:10px!important;margin-bottom:15px!important}
.uf-pane-head .num{width:26px!important;height:26px!important;flex:0 0 26px!important;background:#0b2d60!important;font-size:10px!important}
.uf-pane-copy{min-width:0!important;flex:1!important}.uf-pane-copy b{display:flex!important;align-items:center!important;gap:8px!important;font-size:18px!important;font-weight:900!important;color:#08295d!important;line-height:1.45!important;margin:0!important}.uf-pane-copy small{display:block!important;margin-top:3px!important;font-size:9.5px!important;line-height:1.6!important;color:#8392a8!important;font-weight:600!important}
.uf-title-icon,.uf-label-icon,.uf-mode-icon,.uf-search-icon{display:inline-flex!important;align-items:center!important;justify-content:center!important;color:#1976e8!important;flex:0 0 auto!important}.uf-title-icon svg{width:21px!important;height:21px!important}.uf-label-icon svg{width:15px!important;height:15px!important}.uf-mode-icon svg{width:22px!important;height:22px!important}.uf-search-icon{position:absolute!important;right:13px!important;top:12px!important;z-index:2!important;color:#6f89aa!important;pointer-events:none!important}.uf-search-icon svg{width:18px!important;height:18px!important}.uf-title-icon svg,.uf-label-icon svg,.uf-mode-icon svg,.uf-search-icon svg,.uf-search-btn svg,.uf-qr-btn svg,.uf-upload-btn svg,.uf-attach-title svg,.uf-upload-row b svg{display:block!important;fill:none!important;stroke:currentColor!important;stroke-width:1.9!important;stroke-linecap:round!important;stroke-linejoin:round!important}.uf-search-btn svg,.uf-qr-btn svg,.uf-upload-btn svg,.uf-attach-title svg,.uf-upload-row b svg{width:15px!important;height:15px!important;flex:0 0 15px!important;max-width:15px!important;max-height:15px!important}
.uf-mode-grid{display:grid!important;grid-template-columns:1fr 1fr!important;gap:10px!important;margin-bottom:12px!important}.uf-mode{min-height:74px!important;border:1px solid #d4dfec!important;border-radius:10px!important;background:#fff!important;display:flex!important;align-items:center!important;justify-content:center!important;gap:12px!important;color:#102f5f!important;cursor:pointer!important;font-family:Cairo!important;font-weight:900!important;padding:10px 14px!important}.uf-mode:hover{border-color:#91b9e8!important;background:#fbfdff!important}.uf-mode.active{border:1.5px solid #2d7ff0!important;background:#f8fbff!important;box-shadow:inset 0 0 0 1px rgba(45,127,240,.05)!important}.uf-mode .dot{width:18px!important;height:18px!important;flex:0 0 18px!important;border:2px solid #8ba3c0!important;border-radius:50%!important;display:grid!important;place-items:center!important}.uf-mode.active .dot:after{content:""!important;width:8px!important;height:8px!important;border-radius:50%!important;background:#2380f4!important}.uf-mode-text{display:flex!important;flex-direction:column!important;text-align:right!important}.uf-mode-text strong{display:flex!important;align-items:center!important;gap:7px!important;font-size:12.5px!important;line-height:1.45!important}.uf-mode-text span{font-size:9px!important;color:#788ba5!important;font-weight:700!important;margin-top:2px!important}
.uf-contract-strip{display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:12px!important;min-height:44px!important;padding:9px 13px!important;border-radius:8px!important;background:#f1f6fc!important;margin-bottom:12px!important;color:#173866!important;font-size:10px!important;font-weight:800!important;white-space:nowrap!important;overflow:hidden!important}.uf-contract-strip strong{font-size:12px!important;color:#08295d!important}.uf-contract-project{display:inline-flex!important;align-items:center!important;gap:6px!important;min-width:0!important;color:#08295d!important;font-size:11px!important;font-weight:900!important}.uf-contract-project:before{content:""!important;width:1px!important;height:20px!important;background:#c8d6e8!important;margin-inline:4px 2px!important;flex:0 0 1px!important}.uf-contract-project span{overflow:hidden!important;text-overflow:ellipsis!important;white-space:nowrap!important;max-width:310px!important}.uf-contract-doc{display:inline-flex!important;color:#1674d1!important;flex:0 0 auto!important}.uf-contract-doc svg{width:15px!important;height:15px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.8!important}
.uf-asset-searchbar{display:grid!important;grid-template-columns:minmax(0,1fr) 124px 124px!important;gap:8px!important;align-items:center!important;direction:rtl!important;margin-bottom:10px!important;position:relative!important}.uf-asset-searchbar input{grid-column:1!important;min-width:0!important;height:42px!important;border-radius:7px!important;padding-inline:38px 13px!important;background:#fff!important}.uf-asset-searchbar:after{content:none!important}.uf-search-btn{grid-column:2!important;height:42px!important;border:1px solid #08295d!important;background:#08295d!important;color:#fff!important;border-radius:7px!important;font:900 10.5px Cairo!important;cursor:pointer!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important}.uf-search-btn:hover{background:#0b3a7b!important}.uf-qr-btn{grid-column:3!important;height:42px!important;border:1px solid #cbd9e9!important;background:#fff!important;color:#0a356b!important;border-radius:7px!important;font:900 10.5px Cairo!important;cursor:pointer!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important}
.uf-asset-count{font-size:10px!important;font-weight:900!important;color:#173866!important;margin:4px 0 8px!important}.uf-asset-results{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:9px!important;max-height:none!important;overflow:visible!important;padding:1px!important}.uf-asset-option{position:relative!important;border:1px solid #d7e2ef!important;border-radius:10px!important;padding:10px 11px 11px!important;background:#fff!important;cursor:pointer!important;min-height:154px!important;text-align:right!important;display:flex!important;flex-direction:column!important;justify-content:flex-start!important}.uf-asset-option.active{border:1.5px solid #2380f4!important;background:#f8fbff!important;box-shadow:inset 0 0 0 1px rgba(35,128,244,.04)!important}.uf-asset-option:before{content:""!important;width:16px!important;height:16px!important;border:2px solid #95abc5!important;border-radius:50%!important;position:absolute!important;left:11px!important;top:12px!important;background:#fff!important}.uf-asset-option.active:before{border-color:#2380f4!important;box-shadow:inset 0 0 0 4px #fff!important;background:#2380f4!important}.uf-asset-thumb{height:52px!important;display:flex!important;align-items:center!important;justify-content:center!important;margin:4px 0 7px!important;color:#1d64a8!important}.uf-asset-thumb svg{width:58px!important;height:46px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.25!important;stroke-linecap:round!important;stroke-linejoin:round!important}.uf-asset-option strong{display:block!important;color:#08295d!important;font-size:11px!important;margin-bottom:3px!important;text-align:center!important}.uf-asset-option span{display:block!important;color:#617895!important;font-size:8.8px!important;line-height:1.6!important;text-align:center!important}.uf-asset-option .code{display:block!important;background:transparent!important;color:#2f5f95!important;font-weight:900!important;font-size:10px!important;padding:0!important}.uf-empty-assets{padding:18px!important;border:1px dashed #c8d6e7!important;border-radius:9px!important;color:#72839b!important;font-size:10px!important;text-align:center!important;grid-column:1/-1!important;min-height:64px!important;display:grid!important;place-items:center!important}
.uf-selected-asset{display:none!important;margin-top:10px!important;padding:0!important;border:1px solid #d9e5f2!important;border-radius:10px!important;background:#fff!important;overflow:hidden!important}.uf-selected-asset.show{display:block!important}.uf-selected-title{font-size:11px!important;font-weight:900!important;color:#08295d!important;padding:9px 12px!important;border-bottom:1px solid #e1e9f2!important;background:#fbfdff!important}.uf-selected-grid{display:grid!important;grid-template-columns:repeat(3,1fr)!important;gap:0!important;padding:8px 10px!important}.uf-selected-grid div{font-size:8.5px!important;color:#8290a4!important;padding:5px 9px!important;border-left:1px solid #e2e9f1!important}.uf-selected-grid div:nth-child(3n){border-left:0!important}.uf-selected-grid b{display:block!important;margin-top:2px!important;font-size:10px!important;color:#173866!important}.uf-selected-grid:after{content:"حالة الضمان   ساري"!important;grid-column:1/-1!important;margin-top:7px!important;padding:7px 10px!important;border-radius:6px!important;background:#ecf9f0!important;color:#168447!important;font-size:9.5px!important;font-weight:900!important;text-align:right!important}
.uf-manual-grid{display:none!important;grid-template-columns:repeat(2,1fr)!important;gap:9px!important}.uf-manual-grid.show{display:grid!important}.uf-manual-grid .span2{grid-column:1/-1!important}
.uf-details-pane textarea{min-height:108px!important;height:108px!important}.uf-detail-field{margin-bottom:10px!important}.uf-detail-field>label{display:flex!important;align-items:center!important;gap:7px!important;margin-bottom:6px!important;font-size:10.5px!important;font-weight:900!important;color:#08295d!important}.uf-chip-row{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:8px!important}.uf-chip{min-height:36px!important;padding:0 9px!important;border:1px solid #d6e1ed!important;border-radius:7px!important;background:#fff!important;color:#24466f!important;font:800 9.5px Cairo!important;cursor:pointer!important}.uf-chip[data-value="غير معروف"]{display:none!important}.uf-chip[data-value="متوقفة"]{border-color:#f1c0c6!important;background:#fff5f6!important;color:#c82c38!important}.uf-chip[data-value="تعمل جزئياً"],.uf-chip[data-value="تعمل مع وجود مشكلة"]{border-color:#efd093!important;background:#fff8e9!important;color:#bd7902!important}.uf-chip[data-value="تعمل"]{border-color:#b8e1c7!important;background:#eefaf2!important;color:#168e49!important}.uf-chip.active[data-value="متوقفة"]{background:#fde5e8!important;border-color:#e54854!important;box-shadow:inset 0 0 0 1px #e54854!important}.uf-chip.active[data-value="تعمل جزئياً"],.uf-chip.active[data-value="تعمل مع وجود مشكلة"]{background:#fff0d0!important;border-color:#dca11d!important;box-shadow:inset 0 0 0 1px #dca11d!important}.uf-chip.active[data-value="تعمل"]{background:#def5e6!important;border-color:#20a359!important;box-shadow:inset 0 0 0 1px #20a359!important}.uf-attach-title{display:flex!important;align-items:center!important;gap:7px!important;font-size:11px!important;font-weight:900!important;color:#08295d!important;margin:13px 0 8px!important}.uf-upload-row{display:grid!important;grid-template-columns:minmax(120px,1fr) 126px 126px!important;gap:7px!important;align-items:center!important;border:1px dashed #cfdbea!important;border-radius:8px!important;padding:8px 9px!important;margin-bottom:7px!important;position:relative!important;min-height:52px!important}.uf-upload-row b{display:flex!important;align-items:center!important;gap:7px!important;font-size:9.5px!important;color:#173866!important}.uf-upload-row b svg{color:#173866!important}.uf-upload-btn{height:34px!important;padding:0 9px!important;border-radius:6px!important;font:900 9px Cairo!important;cursor:pointer!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:6px!important;min-width:0!important}.uf-upload-btn.camera{background:#071f4d!important;color:#fff!important;border:1px solid #071f4d!important}.uf-upload-btn.device{background:#176dca!important;color:#fff!important;border:1px solid #176dca!important}.uf-upload-input{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important}.uf-files-summary{margin-top:8px!important;padding:8px 10px!important;border:1px solid #dce6f1!important;border-radius:8px!important;background:#f8fbff!important;color:#73849c!important;font-size:8.8px!important;text-align:center!important}.uf-files-summary.has-files{background:#eef9f2!important;border-color:#b9e3c7!important;color:#16733c!important}
.uf-chip{transition:.18s ease!important}.uf-chip:hover{border-color:#8eb9ec!important;background:#f7fbff!important}.uf-chip.active{border-color:#2380f4!important;background:#eaf4ff!important;color:#1266bf!important;box-shadow:inset 0 0 0 1px #2380f4!important}
.uf-upload-row{transition:.18s ease!important}.uf-upload-row.has-files{border-style:solid!important;border-color:#9fc6ef!important;background:#fbfdff!important}.uf-file-count{display:inline-grid!important;place-items:center!important;width:22px!important;height:22px!important;border-radius:50%!important;background:#e7eff8!important;color:#5f7692!important;font-size:9px!important;font-weight:900!important;margin-inline-start:2px!important}.uf-upload-row.has-files .uf-file-count{background:#176dca!important;color:#fff!important}
.uf-upload-row .uf-upload-preview{display:none!important}.uf-files-summary.has-files{padding:10px!important;text-align:right!important}.uf-preview-summary-title{display:flex!important;align-items:center!important;gap:7px!important;margin-bottom:9px!important;color:#16733c!important;font-size:9.5px!important;font-weight:900!important}.uf-preview-summary-title svg{width:15px!important;height:15px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.9!important}.uf-preview-groups{display:grid!important;gap:8px!important}.uf-preview-group{padding:8px!important;border:1px solid #dce6f1!important;border-radius:8px!important;background:#fff!important}.uf-preview-group-title{display:flex!important;align-items:center!important;justify-content:space-between!important;margin-bottom:7px!important;color:#173866!important;font-size:9px!important;font-weight:900!important}.uf-preview-group-count{display:inline-grid!important;place-items:center!important;min-width:20px!important;height:20px!important;padding:0 5px!important;border-radius:10px!important;background:#176dca!important;color:#fff!important}.uf-preview-gallery{display:flex!important;gap:7px!important;align-items:center!important;overflow-x:auto!important;padding:3px 3px 2px!important}.uf-preview-item{width:54px!important;height:54px!important;flex:0 0 54px!important;border:1px solid #d2deeb!important;border-radius:7px!important;background:#f4f7fb!important;overflow:visible!important;position:relative!important;display:grid!important;place-items:center!important}.uf-preview-item img{width:100%!important;height:100%!important;object-fit:cover!important;display:block!important;border-radius:6px!important}.uf-preview-doc{width:100%!important;height:100%!important;padding:5px!important;color:#176dca!important;text-align:center!important;font-size:7.5px!important;font-weight:900!important;line-height:1.25!important;overflow:hidden!important;overflow-wrap:anywhere!important;border-radius:6px!important}.uf-preview-doc svg{width:20px!important;height:20px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.8!important;margin:auto auto 3px!important}.uf-preview-remove{position:absolute!important;top:2px!important;left:2px!important;z-index:3!important;width:18px!important;height:18px!important;padding:0!important;border:2px solid #fff!important;border-radius:50%!important;background:#d93646!important;color:#fff!important;font:900 13px/14px Arial,sans-serif!important;display:grid!important;place-items:center!important;cursor:pointer!important;box-shadow:0 2px 5px rgba(90,16,24,.28)!important}.uf-preview-remove:hover{background:#b91f30!important;transform:scale(1.07)!important}.uf-preview-remove:focus-visible{outline:2px solid #176dca!important;outline-offset:2px!important}.uf-preview-more{overflow:hidden!important;background:#eaf3fd!important;color:#176dca!important;font-size:10px!important;font-weight:900!important}
@media(max-width:1100px){.uf-workspace{grid-template-columns:1fr!important;grid-template-areas:"asset" "details"!important}.uf-workspace>.panel{height:auto!important}.uf-asset-results{grid-template-columns:repeat(2,1fr)!important}}
@media(max-width:650px){.uf-workspace>.panel{padding:14px!important}.uf-mode-grid,.uf-manual-grid{grid-template-columns:1fr!important}.uf-asset-searchbar{grid-template-columns:1fr 1fr!important}.uf-asset-searchbar input{grid-column:1/-1!important}.uf-search-btn{grid-column:1!important}.uf-qr-btn{grid-column:2!important}.uf-asset-results{grid-template-columns:1fr!important}.uf-selected-grid{grid-template-columns:1fr 1fr!important}.uf-selected-grid div{border-left:0!important}.uf-chip-row{grid-template-columns:1fr!important}.uf-upload-row{grid-template-columns:1fr 1fr!important}.uf-upload-row b{grid-column:1/-1!important}.uf-contract-strip{flex-wrap:wrap!important;white-space:normal!important}.uf-contract-project:before{display:none!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-unified-asset-issue-workspace-polish-script-v6">
(()=>{
  const $=id=>document.getElementById(id);
  const icons={
    search:'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
    qr:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2M18 14h2M14 18h6M18 16v4"/></svg>',
    asset:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><circle cx="12" cy="12" r="5"/><path d="M8.5 8.5 6 6M18 18l-2.5-2.5M15.5 8.5 18 6M6 18l2.5-2.5"/></svg>',
    issue:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2.8 19h18.4z"/><path d="M12 9v4M12 17h.01"/></svg>',
    paperclip:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8.5 12.5 6.7-6.7a3.2 3.2 0 0 1 4.5 4.5l-8.1 8.1a5 5 0 0 1-7.1-7.1l8-8"/></svg>',
    camera:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="14" r="3.5"/></svg>',
    upload:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>',
    status:'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    description:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>',
    time:'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    registered:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20V7l7-4 7 4v13"/><path d="M9 20v-5h6v5M9 9h.01M12 9h.01M15 9h.01"/></svg>',
    manual:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16M6 20V8h8v12M14 12h4v8"/><path d="M9 11h2M9 15h2M17 15h.01"/></svg>',
    doc:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 12h6M10 16h6"/></svg>',
    machine:'<svg viewBox="0 0 64 48" aria-hidden="true"><rect x="10" y="14" width="36" height="22" rx="4"/><path d="M46 20h8v10h-8M16 14V9h18v5M17 36v5M39 36v5M22 20h12M22 25h12M22 30h12"/><circle cx="16" cy="25" r="3"/></svg>'
  };

  const addSearchButton=()=>{
    const bar=document.querySelector('.uf-asset-searchbar'), input=$('uf-asset-search'), qr=$('uf-qr');
    if(!bar||!input||!qr)return;
    let search=$('uf-search');
    if(!search){
      search=document.createElement('button');search.type='button';search.id='uf-search';search.className='uf-search-btn';search.innerHTML=icons.search+'<span>بحث</span>';
      search.addEventListener('click',()=>input.dispatchEvent(new Event('input',{bubbles:true})));
      input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();search.click();}});
    }
    let searchIcon=bar.querySelector('.uf-search-icon');
    if(!searchIcon){searchIcon=document.createElement('span');searchIcon.className='uf-search-icon';searchIcon.innerHTML=icons.search;bar.appendChild(searchIcon);}
    qr.innerHTML=icons.qr+'<span>مسح QR</span>';
    const expected=[input,search,qr], actual=[...bar.children].filter(el=>expected.includes(el));
    if(actual.length!==3||actual.some((el,i)=>el!==expected[i])) expected.forEach(el=>bar.appendChild(el));
  };

  const iconize=()=>{
    const assetTitle=document.querySelector('.uf-asset-pane .uf-pane-copy b'), detailsTitle=document.querySelector('.uf-details-pane .uf-pane-copy b');
    if(assetTitle&&!assetTitle.querySelector('.uf-title-icon'))assetTitle.insertAdjacentHTML('afterbegin','<span class="uf-title-icon">'+icons.asset+'</span>');
    if(detailsTitle&&!detailsTitle.querySelector('.uf-title-icon'))detailsTitle.insertAdjacentHTML('afterbegin','<span class="uf-title-icon">'+icons.issue+'</span>');
    document.querySelectorAll('.uf-detail-field>label').forEach(label=>{
      if(label.querySelector('.uf-label-icon'))return;
      const txt=label.textContent||'', svg=/متى بدأت/.test(txt)?icons.time:/حالة المعدة/.test(txt)?icons.status:/نوع المشكلة/.test(txt)?icons.issue:icons.description;
      label.insertAdjacentHTML('afterbegin','<span class="uf-label-icon">'+svg+'</span>');
    });
    document.querySelectorAll('[data-uf-mode]').forEach(mode=>{
      const strong=mode.querySelector('strong');if(!strong||strong.querySelector('.uf-mode-icon'))return;
      strong.insertAdjacentHTML('afterbegin','<span class="uf-mode-icon">'+(mode.dataset.ufMode==='manual'?icons.manual:icons.registered)+'</span>');
    });
    const attach=document.querySelector('.uf-attach-title');if(attach&&!attach.querySelector('svg'))attach.insertAdjacentHTML('afterbegin',icons.paperclip);
  };

  const decorateContract=()=>{
    const strip=document.querySelector('.uf-contract-strip'), label=$('uf-contract-label'), title=$('contract_title');
    if(!strip||!label)return;
    if(!strip.querySelector('.uf-contract-doc'))strip.insertAdjacentHTML('afterbegin','<span class="uf-contract-doc">'+icons.doc+'</span>');
    let project=strip.querySelector('.uf-contract-project');
    if(!project){project=document.createElement('span');project.className='uf-contract-project';project.innerHTML='<b>المشروع:</b><span>—</span>';strip.appendChild(project);}
    const projectText=(title?.value||'').trim();project.querySelector('span').textContent=projectText||'—';
  };

  const decorateAssetCards=()=>{
    document.querySelectorAll('.uf-asset-option').forEach(card=>{
      if(card.querySelector('.uf-asset-thumb'))return;
      card.insertAdjacentHTML('afterbegin','<span class="uf-asset-thumb">'+icons.machine+'</span>');
    });
  };

  const normalizeStates=()=>{
    const row=document.querySelector('[data-chip-name="equipment_state"]')?.closest('.uf-chip-row');if(!row)return;
    const buttons=[...row.querySelectorAll('[data-chip-name="equipment_state"]')];
    const partialOld=buttons.find(x=>x.dataset.value==='تعمل مع وجود مشكلة');
    const partialNew=buttons.find(x=>x.dataset.value==='تعمل جزئياً');
    if(partialOld&&partialNew&&partialOld!==partialNew){partialOld.remove();}
    else if(partialOld){partialOld.dataset.value='تعمل جزئياً';partialOld.textContent='تعمل جزئياً';}
    let running=[...row.querySelectorAll('[data-chip-name="equipment_state"]')].find(x=>x.dataset.value==='تعمل');
    if(!running){running=document.createElement('button');running.type='button';running.className='uf-chip';running.dataset.chipName='equipment_state';running.dataset.value='تعمل';running.textContent='تعمل';row.appendChild(running);}
    row.querySelectorAll('[data-chip-name="equipment_state"]').forEach(btn=>{
      if(btn.dataset.ufPolishBound==='1')return;btn.dataset.ufPolishBound='1';
      btn.addEventListener('click',()=>{row.querySelectorAll('[data-chip-name="equipment_state"]').forEach(x=>x.classList.remove('active'));btn.classList.add('active');const hidden=$('equipment_state');if(hidden)hidden.value=btn.dataset.value||'';});
    });
  };

  const fixAttachments=()=>{
    document.querySelectorAll('.uf-upload-row').forEach(row=>{
      const title=row.querySelector('b');if(title&&!title.querySelector('svg'))title.insertAdjacentHTML('afterbegin',icons.paperclip);
      if(title&&!title.querySelector('.uf-file-count'))title.insertAdjacentHTML('beforeend','<span class="uf-file-count" aria-label="عدد المرفقات">0</span>');
      row.querySelectorAll('.uf-upload-btn').forEach(btn=>{
        if(btn.dataset.ufPolishBound==='1')return;btn.dataset.ufPolishBound='1';const target=btn.dataset.ufFile;
        if(btn.classList.contains('camera')&&!btn.querySelector('svg'))btn.insertAdjacentHTML('afterbegin',icons.camera);
        if(btn.classList.contains('device')&&!btn.querySelector('svg'))btn.insertAdjacentHTML('afterbegin',icons.upload);
        btn.addEventListener('click',e=>{e.preventDefault();e.stopImmediatePropagation();const input=$(btn.dataset.ufFile||target||'');if(!input)return;try{typeof input.showPicker==='function'?input.showPicker():input.click();}catch(_){input.click();}},true);
      });
      row.querySelectorAll('.uf-upload-input').forEach(input=>{
        input.multiple=true;
        if(input.dataset.ufPolishChange==='1')return;input.dataset.ufPolishChange='1';
        input.addEventListener('change',()=>{preserveInputFiles(input);renderAttachmentPreviews();});
      });
    });
    renderAttachmentPreviews();
  };

  let attachmentInputSequence=0;
  const preserveInputFiles=input=>{
    if(!(input.files?.length))return;
    const currentId=input.id,replacement=input.cloneNode(false);attachmentInputSequence+=1;
    replacement.id=currentId+'-more-'+attachmentInputSequence;replacement.value='';replacement.dataset.ufPolishChange='1';
    input.removeAttribute('id');input.dataset.ufArchived='1';input.after(replacement);
    const button=[...document.querySelectorAll('[data-uf-file]')].find(item=>item.dataset.ufFile===currentId);if(button)button.dataset.ufFile=replacement.id;
    replacement.addEventListener('change',()=>{preserveInputFiles(replacement);renderAttachmentPreviews();});
  };

  const attachmentTitle=row=>{
    const title=row.querySelector('b');if(!title)return 'مرفقات';
    return [...title.childNodes].filter(node=>node.nodeType===Node.TEXT_NODE).map(node=>node.textContent).join(' ').trim()||'مرفقات';
  };

  const removeAttachment=(input,index)=>{
    const files=[...(input.files||[])];if(!files[index]||typeof DataTransfer==='undefined')return;
    const transfer=new DataTransfer();files.forEach((file,fileIndex)=>{if(fileIndex!==index)transfer.items.add(file);});input.files=transfer.files;
    if(!input.files.length&&input.dataset.ufArchived==='1')input.remove();
    renderAttachmentPreviews();
  };

  const previewItem=entry=>{
    const {file,input,index}=entry,item=document.createElement('span');item.className='uf-preview-item';item.title=file.name;
    if(file.type.startsWith('image/')){const url=URL.createObjectURL(file),image=document.createElement('img');image.src=url;image.alt=file.name;item.appendChild(image);item._ufPreviewUrl=url;}
    else{item.innerHTML='<span class="uf-preview-doc">'+icons.doc+'<span>'+file.name.replace(/[<>&]/g,'')+'</span></span>';}
    const remove=document.createElement('button');remove.type='button';remove.className='uf-preview-remove';remove.textContent='×';remove.title='حذف المرفق';remove.setAttribute('aria-label','حذف المرفق '+file.name);remove.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();removeAttachment(input,index);});item.appendChild(remove);
    return item;
  };

  const renderAttachmentPreviews=()=>{
    const rows=[...document.querySelectorAll('.uf-upload-row')],summary=$('uf-files-summary');if(!summary)return;
    (summary._ufPreviewUrls||[]).forEach(url=>URL.revokeObjectURL(url));summary._ufPreviewUrls=[];
    const groups=[];let total=0;
    rows.forEach(row=>{
      const entries=[...row.querySelectorAll('.uf-upload-input')].flatMap(input=>[...(input.files||[])].map((file,index)=>({file,input,index}))),badge=row.querySelector('.uf-file-count');
      if(badge)badge.textContent=String(entries.length);row.classList.toggle('has-files',entries.length>0);total+=entries.length;
      if(entries.length)groups.push({title:attachmentTitle(row),entries});
    });
    summary.classList.toggle('has-files',total>0);
    if(!total){summary.textContent='لا توجد مرفقات مضافة حالياً';return;}
    summary.innerHTML='<div class="uf-preview-summary-title">'+icons.paperclip+'<span>معاينة المرفقات المضافة: '+total+'</span></div><div class="uf-preview-groups"></div>';
    const container=summary.querySelector('.uf-preview-groups');
    groups.forEach(group=>{
      const section=document.createElement('section');section.className='uf-preview-group';section.innerHTML='<div class="uf-preview-group-title"><span>'+group.title+'</span><span class="uf-preview-group-count">'+group.entries.length+'</span></div><div class="uf-preview-gallery"></div>';
      const gallery=section.querySelector('.uf-preview-gallery');group.entries.slice(0,6).forEach(entry=>{const item=previewItem(entry);if(item._ufPreviewUrl)summary._ufPreviewUrls.push(item._ufPreviewUrl);gallery.appendChild(item);});
      if(group.entries.length>6){const more=document.createElement('span');more.className='uf-preview-item uf-preview-more';more.textContent='+'+(group.entries.length-6);gallery.appendChild(more);}
      container.appendChild(section);
    });
  };

  const activateChip=btn=>{
    const name=btn.dataset.chipName;if(!name)return;
    document.querySelectorAll(`[data-chip-name="${name}"]`).forEach(item=>{const active=item===btn;item.classList.toggle('active',active);item.setAttribute('aria-pressed',active?'true':'false');});
    const hidden=$(name);if(hidden){hidden.value=btn.dataset.value||'';hidden.dispatchEvent(new Event('change',{bubbles:true}));}
  };

  const apply=()=>{addSearchButton();iconize();decorateContract();decorateAssetCards();normalizeStates();fixAttachments();};
  const scheduleApply=(delay=0)=>setTimeout(()=>requestAnimationFrame(apply),delay);
  apply();
  document.addEventListener('change',e=>{
    if(['service-type','service-subtype','contract_no','site_id','asset-list'].includes(e.target?.id)){scheduleApply(60);scheduleApply(350);}
  });
  document.addEventListener('input',e=>{if(e.target?.id==='contract_title')scheduleApply(0);});
  document.addEventListener('click',e=>{if(e.target?.closest('[data-uf-mode]'))scheduleApply(0);});
  document.addEventListener('click',e=>{const chip=e.target?.closest('[data-chip-name]');if(!chip)return;e.preventDefault();activateChip(chip);});
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
