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
<style id="unifco-unified-asset-issue-workspace-polish-v2">
.uf-asset-searchbar{display:grid!important;grid-template-columns:minmax(0,1fr) 124px 124px!important;gap:8px!important;align-items:center!important;direction:rtl!important}
.uf-asset-searchbar input{grid-column:1!important;min-width:0!important}
.uf-search-btn{grid-column:2!important;height:42px!important;border:1px solid #08295d!important;background:#08295d!important;color:#fff!important;border-radius:7px!important;font:900 11px Cairo!important;cursor:pointer!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important}
.uf-search-btn:hover{background:#0b3a7b!important;border-color:#0b3a7b!important}
.uf-qr-btn{grid-column:3!important;height:42px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important}
.uf-search-btn svg,.uf-qr-btn svg,.uf-title-icon svg,.uf-label-icon svg,.uf-attach-title svg,.uf-upload-btn svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round;flex:0 0 auto}
.uf-pane-copy b,.uf-detail-field>label,.uf-attach-title,.uf-upload-row b{display:flex!important;align-items:center!important;gap:7px!important}
.uf-title-icon,.uf-label-icon{display:inline-flex;align-items:center;justify-content:center;color:#1976e8}
.uf-detail-field>label .uf-label-icon{color:#153a71}
.uf-chip-row{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:8px!important}
.uf-chip[data-value="غير معروف"]{display:none!important}
.uf-chip[data-value="متوقفة"]{border-color:#efc1c6!important;background:#fff6f7!important;color:#c82935!important}
.uf-chip[data-value="تعمل جزئياً"],.uf-chip[data-value="تعمل مع وجود مشكلة"]{border-color:#f2d49a!important;background:#fff9ec!important;color:#bf7b06!important}
.uf-chip[data-value="تعمل"]{border-color:#b9e5ca!important;background:#f0fbf4!important;color:#15934c!important}
.uf-chip.active[data-value="متوقفة"]{background:#fde6e8!important;border-color:#e14a55!important;box-shadow:inset 0 0 0 1px #e14a55!important}
.uf-chip.active[data-value="تعمل جزئياً"],.uf-chip.active[data-value="تعمل مع وجود مشكلة"]{background:#fff0cf!important;border-color:#dfa323!important;box-shadow:inset 0 0 0 1px #dfa323!important}
.uf-chip.active[data-value="تعمل"]{background:#def6e7!important;border-color:#21a75d!important;box-shadow:inset 0 0 0 1px #21a75d!important}
.uf-upload-row{position:relative!important}
.uf-upload-btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:6px!important;min-width:120px!important}
.uf-upload-btn:disabled{opacity:.55!important;cursor:not-allowed!important}
.uf-files-summary.has-files{background:#eef9f2!important;border-color:#b9e3c7!important;color:#16733c!important}
@media(max-width:650px){.uf-asset-searchbar{grid-template-columns:1fr 1fr!important}.uf-asset-searchbar input{grid-column:1/-1!important}.uf-search-btn{grid-column:1!important}.uf-qr-btn{grid-column:2!important}.uf-chip-row{grid-template-columns:1fr!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-unified-asset-issue-workspace-polish-script-v2">
(()=>{
  const $=id=>document.getElementById(id);
  const icons={
    search:'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
    qr:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2M18 14h2M14 18h6M18 16v4"/></svg>',
    asset:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><circle cx="12" cy="12" r="5"/><path d="M8.5 8.5 6 6M18 18l-2.5-2.5M15.5 8.5 18 6M6 18l2.5-2.5"/></svg>',
    issue:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2.8 19h18.4z"/><path d="M12 9v4M12 17h.01"/></svg>',
    paperclip:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m20.5 11.5-8.9 8.9a5 5 0 0 1-7.1-7.1l9.6-9.6a3.5 3.5 0 0 1 5 5l-9.6 9.6a2 2 0 1 1-2.8-2.8l8.9-8.9"/></svg>',
    camera:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="14" r="3.5"/></svg>',
    upload:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>',
    status:'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    description:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>'
  };

  const addSearchButton=()=>{
    const bar=document.querySelector('.uf-asset-searchbar');
    const input=$('uf-asset-search');
    const qr=$('uf-qr');
    if(!bar||!input||!qr) return;

    let search=$('uf-search');
    if(!search){
      search=document.createElement('button');
      search.type='button'; search.id='uf-search'; search.className='uf-search-btn';
      search.innerHTML=icons.search+'<span>بحث</span>';
      search.addEventListener('click',()=>input.dispatchEvent(new Event('input',{bubbles:true})));
      input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();search.click();}});
    }
    if(!qr.querySelector('svg')) qr.innerHTML=icons.qr+'<span>مسح QR</span>';

    // Reorder only when needed. Re-appending existing nodes on every mutation caused an endless DOM loop.
    const expected=[input,search,qr];
    const actual=[...bar.children].filter(el=>expected.includes(el));
    const needsOrder=actual.length!==3 || actual.some((el,i)=>el!==expected[i]);
    if(needsOrder){
      expected.forEach(el=>bar.appendChild(el));
    }
  };

  const iconize=()=>{
    const assetTitle=document.querySelector('.uf-asset-pane .uf-pane-copy b');
    const detailsTitle=document.querySelector('.uf-details-pane .uf-pane-copy b');
    if(assetTitle&&!assetTitle.querySelector('.uf-title-icon')) assetTitle.insertAdjacentHTML('afterbegin','<span class="uf-title-icon">'+icons.asset+'</span>');
    if(detailsTitle&&!detailsTitle.querySelector('.uf-title-icon')) detailsTitle.insertAdjacentHTML('afterbegin','<span class="uf-title-icon">'+icons.issue+'</span>');
    document.querySelectorAll('.uf-detail-field>label').forEach(label=>{
      if(label.querySelector('.uf-label-icon')) return;
      const txt=label.textContent||'';
      const svg=/حالة المعدة/.test(txt)?icons.status:/نوع المشكلة/.test(txt)?icons.issue:icons.description;
      label.insertAdjacentHTML('afterbegin','<span class="uf-label-icon">'+svg+'</span>');
    });
    const attach=document.querySelector('.uf-attach-title');
    if(attach&&!attach.querySelector('svg')) attach.insertAdjacentHTML('afterbegin',icons.paperclip);
  };

  const normalizeStates=()=>{
    const stateButtons=[...document.querySelectorAll('[data-chip-name="equipment_state"]')];
    const row=stateButtons[0]?.closest('.uf-chip-row');
    if(!row) return;

    const oldPartial=stateButtons.find(x=>x.dataset.value==='تعمل مع وجود مشكلة');
    if(oldPartial){oldPartial.dataset.value='تعمل جزئياً';oldPartial.textContent='تعمل جزئياً';}

    let running=[...row.querySelectorAll('[data-chip-name="equipment_state"]')].find(x=>x.dataset.value==='تعمل');
    if(!running){
      running=document.createElement('button');
      running.type='button'; running.className='uf-chip'; running.dataset.chipName='equipment_state'; running.dataset.value='تعمل'; running.textContent='تعمل';
      row.appendChild(running);
    }

    row.querySelectorAll('[data-chip-name="equipment_state"]').forEach(btn=>{
      if(btn.dataset.ufPolishBound==='1') return;
      btn.dataset.ufPolishBound='1';
      btn.addEventListener('click',()=>{
        row.querySelectorAll('[data-chip-name="equipment_state"]').forEach(x=>x.classList.remove('active'));
        btn.classList.add('active');
        const hidden=$('equipment_state'); if(hidden) hidden.value=btn.dataset.value||'';
      });
    });
  };

  const fixAttachments=()=>{
    document.querySelectorAll('.uf-upload-row').forEach(row=>{
      const title=row.querySelector('b');
      if(title&&!title.querySelector('svg')) title.insertAdjacentHTML('afterbegin',icons.paperclip);

      row.querySelectorAll('.uf-upload-btn').forEach(btn=>{
        if(btn.dataset.ufPolishBound==='1') return;
        btn.dataset.ufPolishBound='1';
        const target=btn.dataset.ufFile;
        if(btn.classList.contains('camera')&&!btn.querySelector('svg')) btn.insertAdjacentHTML('afterbegin',icons.camera);
        if(btn.classList.contains('device')&&!btn.querySelector('svg')) btn.insertAdjacentHTML('afterbegin',icons.upload);
        btn.addEventListener('click',e=>{
          e.preventDefault(); e.stopPropagation();
          const input=$(target||'');
          if(!input) return;
          try{ if(typeof input.showPicker==='function') input.showPicker(); else input.click(); }
          catch(_){ input.click(); }
        },true);
      });

      row.querySelectorAll('.uf-upload-input').forEach(input=>{
        if(input.dataset.ufPolishChange==='1') return;
        input.dataset.ufPolishChange='1';
        input.addEventListener('change',()=>{
          const count=[...document.querySelectorAll('.uf-upload-input')].reduce((n,i)=>n+(i.files?.length||0),0);
          const summary=$('uf-files-summary');
          if(summary){
            summary.textContent=count?`تمت إضافة ${count} مرفق/مرفقات`:'لا توجد مرفقات مضافة حالياً';
            summary.classList.toggle('has-files',count>0);
          }
        });
      });
    });
  };

  const apply=()=>{addSearchButton();iconize();normalizeStates();fixAttachments();};
  const scheduleApply=()=>requestAnimationFrame(()=>requestAnimationFrame(apply));

  apply();

  // Re-apply only after controls that legitimately rebuild the dynamic details panel.
  document.addEventListener('change',e=>{
    if(['service-type','service-subtype','contract_no','site_id','asset-list'].includes(e.target?.id)) scheduleApply();
  });
  document.addEventListener('click',e=>{
    if(e.target?.closest('.uf-mode-btn')) scheduleApply();
  });
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
